<?php
declare(strict_types=1);

require_once __DIR__ . '/PriceCalculator.php';
require_once __DIR__ . '/OrderStatusService.php';
require_once __DIR__ . '/../repositories/AdminOrderRepository.php';

/**
 * Alta del pedido. Todo va en una transacción: si algo falla no se guarda nada
 * (ni el cliente, ni el pedido, ni se toca el stock).
 */
final class OrderService
{
    public function __construct(
        private OrderRepository $orders = new OrderRepository(),
        private PriceCalculator $calculator = new PriceCalculator(),
    ) {
    }

    // $billing es null si la facturación va a la misma dirección que el envío.
    // $replaces es el código de un pedido anterior del mismo cliente que se quedó sin pagar
    // (pago rechazado y luego cambió algún dato): se cancela para que no se quede reservando stock.
    public function create(array $cart, ?string $coupon, array $customer, ?array $billing = null, ?string $replaces = null): array
    {
        $customer = $this->validateCustomer($customer);
        $billing = $billing === null ? null : $this->validateBilling($billing);

        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Primero se cancela el pedido anterior, así su stock ya está libre para el nuevo
            $replaced = $replaces === null ? null : $this->cancelReplaced($replaces, $customer['email']);

            // Los totales se vuelven a calcular aquí con los precios de la base de datos
            $quote = $this->calculator->quote($cart, $coupon);

            // Se reserva el stock al crear el pedido (si se cancela, OrderStatusService lo devuelve)
            foreach ($cart as $productId => $quantity) {
                if (!$this->orders->decreaseStock($productId, $quantity)) {
                    throw new CheckoutException('Alguien acaba de comprar uno de tus productos y ya no queda stock', 409);
                }
            }

            $customerId = $this->orders->createCustomer($customer);
            $order = $this->orders->createOrder($customerId, $quote, $billing);
            (new OrderStatusService())->recordCreated($order['id']);

            logEvent('order.created', 'order', $order['id'], [
                'code'     => $order['code'],
                'total'    => $quote['total'],
                'items'    => count($quote['items']),
                'replaces' => $replaced,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return ['code' => $order['code'], 'status' => 'pendiente_pago', 'total' => $quote['total']];
    }

    // Cancela el pedido anterior solo si sigue sin pagar y es del mismo email (para que nadie
    // cancele pedidos de otros adivinando el código). Si no se cumple, no hace nada.
    private function cancelReplaced(string $code, string $email): ?string
    {
        $old = $this->orders->findToReplace($code);
        if ($old === null || $old['status'] !== 'pendiente_pago' || strcasecmp($old['email'], $email) !== 0) {
            return null;
        }
        (new OrderStatusService())->changeStatus((int) $old['id'], 'cancelado');
        return $code;
    }

    // Misma validación que el formulario del checkout, porque la del navegador se puede saltar
    private function validateCustomer(array $data): array
    {
        $customer = $this->validateAddress($data);
        $customer['email'] = $this->text($data, 'email');
        $customer['phone'] = $this->text($data, 'phone');

        if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL) || strlen($customer['email']) > 150) {
            throw new CheckoutException('El email no es válido', 400);
        }
        if (mb_strlen($customer['phone']) > 30) {
            throw new CheckoutException('El teléfono es demasiado largo', 400);
        }
        $customer['phone'] = $customer['phone'] === '' ? null : $customer['phone'];
        return $customer;
    }

    // Otra dirección de facturación: mismos campos que la de envío, sin email ni teléfono
    private function validateBilling(array $data): array
    {
        return $this->validateAddress($data);
    }

    // Nombre, dirección, ciudad (opcional) y código postal
    private function validateAddress(array $data): array
    {
        $address = [
            'name'        => $this->text($data, 'name'),
            'address'     => $this->text($data, 'address'),
            'city'        => $this->text($data, 'city'),
            'postal_code' => $this->text($data, 'postal_code'),
        ];

        if ($address['name'] === '' || mb_strlen($address['name']) > 120) {
            throw new CheckoutException('Escribe el nombre', 400);
        }
        if ($address['address'] === '' || mb_strlen($address['address']) > 200) {
            throw new CheckoutException('Escribe la dirección', 400);
        }
        if (!preg_match('/^\d{5}$/', $address['postal_code'])) {
            throw new CheckoutException('El código postal tiene que tener 5 números', 400);
        }
        if (mb_strlen($address['city']) > 100) {
            throw new CheckoutException('La ciudad es demasiado larga', 400);
        }
        $address['city'] = $address['city'] === '' ? null : $address['city'];
        return $address;
    }

    private function text(array $data, string $field): string
    {
        return is_string($data[$field] ?? null) ? trim($data[$field]) : '';
    }
}
