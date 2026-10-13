<?php
declare(strict_types=1);

require_once __DIR__ . '/PriceCalculator.php';
require_once __DIR__ . '/OrderStatusService.php';

/**
 * Pago simulado: no se cobra nada ni se usa ninguna pasarela real.
 * Para usar un pago real solo habría que cambiar simulate() por la llamada al banco.
 */
final class PaymentService
{
    private const APPROVED_CARD = '4242424242424242';
    private const REJECTED_CARD = '4000000000000002';

    public function __construct(private OrderRepository $orders = new OrderRepository())
    {
    }

    // $method: tarjeta_simulada (con card_number) o bizum_simulado (con phone)
    public function pay(string $code, string $method, array $data): array
    {
        [$approved, $last] = $this->simulate($method, $data);

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $order = $this->orders->findForPayment($code);
            if ($order === null) {
                throw new CheckoutException('El pedido no existe', 404);
            }
            if ($order['status'] !== 'pendiente_pago') {
                throw new CheckoutException('Este pedido ya está pagado o cancelado', 409);
            }

            // Solo se guardan los 4 últimos números, nunca la tarjeta entera
            $status = $approved ? 'approved' : 'rejected';
            $reference = 'SIM-' . strtoupper(bin2hex(random_bytes(4))) . '-' . $last;
            $this->orders->addPayment((int) $order['id'], $method, $status, $order['total'], $reference);

            // Primero el evento del pago y después el del cambio de estado, para que se lean en orden
            logEvent('payment.simulated', 'order', (int) $order['id'], [
                'code'   => $order['code'],
                'method' => $method,
                'status' => $status,
                'amount' => (float) $order['total'],
            ]);

            if ($approved) {
                // Mismo cambio de estado que en la administración: valida, guarda historial y evento
                (new OrderStatusService())->changeStatus((int) $order['id'], 'pagado');
            }

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return [
            'code'      => $order['code'],
            'status'    => $approved ? 'pagado' : 'pendiente_pago',
            'approved'  => $approved,
            'reference' => $reference,
        ];
    }

    // Devuelve [aprobado, últimos 4 dígitos] o lanza un error si los datos no valen
    private function simulate(string $method, array $data): array
    {
        if ($method === 'tarjeta_simulada') {
            $card = preg_replace('/\s+/', '', (string) ($data['card_number'] ?? ''));
            if ($card === self::APPROVED_CARD) {
                return [true, substr($card, -4)];
            }
            if ($card === self::REJECTED_CARD) {
                return [false, substr($card, -4)];
            }
            throw new CheckoutException('Usa una de las tarjetas de prueba', 400);
        }

        if ($method === 'bizum_simulado') {
            $phone = preg_replace('/\s+/', '', (string) ($data['phone'] ?? ''));
            if (!preg_match('/^[67]\d{8}$/', $phone)) {
                throw new CheckoutException('El teléfono de Bizum no es válido', 400);
            }
            return [true, substr($phone, -4)]; // el Bizum simulado siempre se aprueba
        }

        throw new CheckoutException('Método de pago no válido', 400);
    }
}
