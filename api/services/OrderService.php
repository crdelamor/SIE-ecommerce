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

    public function create(array $cart, ?string $coupon, array $customer): array
    {
        $customer = $this->validateCustomer($customer);

        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Los totales se vuelven a calcular aquí con los precios de la base de datos
            $quote = $this->calculator->quote($cart, $coupon);

            // Se reserva el stock al crear el pedido (si se cancela, OrderStatusService lo devuelve)
            foreach ($cart as $productId => $quantity) {
                if (!$this->orders->decreaseStock($productId, $quantity)) {
                    throw new CheckoutException('Alguien acaba de comprar uno de tus productos y ya no queda stock', 409);
                }
            }

            $customerId = $this->orders->createCustomer($customer);
            $order = $this->orders->createOrder($customerId, $quote);
            (new OrderStatusService())->recordCreated($order['id']);

            logEvent('order.created', 'order', $order['id'], [
                'code'  => $order['code'],
                'total' => $quote['total'],
                'items' => count($quote['items']),
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return ['code' => $order['code'], 'status' => 'pendiente_pago', 'total' => $quote['total']];
    }

    // Misma validación que el formulario del checkout, porque la del navegador se puede saltar
    private function validateCustomer(array $data): array
    {
        $text = fn (string $field): string => is_string($data[$field] ?? null) ? trim($data[$field]) : '';

        $customer = [
            'name'        => $text('name'),
            'email'       => $text('email'),
            'phone'       => $text('phone'),
            'address'     => $text('address'),
            'city'        => $text('city'),
            'postal_code' => $text('postal_code'),
        ];

        if ($customer['name'] === '' || mb_strlen($customer['name']) > 120) {
            throw new CheckoutException('Escribe tu nombre', 400);
        }
        if (!filter_var($customer['email'], FILTER_VALIDATE_EMAIL) || strlen($customer['email']) > 150) {
            throw new CheckoutException('El email no es válido', 400);
        }
        if ($customer['address'] === '' || mb_strlen($customer['address']) > 200) {
            throw new CheckoutException('Escribe tu dirección', 400);
        }
        if (!preg_match('/^\d{5}$/', $customer['postal_code'])) {
            throw new CheckoutException('El código postal tiene que tener 5 números', 400);
        }
        if (mb_strlen($customer['city']) > 100 || mb_strlen($customer['phone']) > 30) {
            throw new CheckoutException('La ciudad o el teléfono son demasiado largos', 400);
        }

        // Los opcionales vacíos se guardan como NULL
        $customer['phone'] = $customer['phone'] === '' ? null : $customer['phone'];
        $customer['city'] = $customer['city'] === '' ? null : $customer['city'];
        return $customer;
    }
}
