<?php
declare(strict_types=1);

/** Error al cambiar el estado; el código es el HTTP que debe devolver la API (404, 409, 422). */
final class OrderStatusException extends RuntimeException
{
}

/**
 * Cambios de estado del pedido: solo transiciones válidas, guardadas en
 * order_status_history y registradas con logEvent().
 */
final class OrderStatusService
{
    /** Estados del pedido del contrato (mismos valores que el ENUM de orders.status). */
    public const STATUSES = [
        'pendiente_pago',
        'pagado',
        'en_preparacion',
        'enviado',
        'entregado',
        'cancelado',
        'incidencia',
    ];

    /**
     * Transiciones válidas del contrato: estado actual => estados a los que puede pasar.
     * entregado y cancelado son finales. Las de incidencia están pendientes de acordar
     * con el grupo: hasta entonces no puede salir de ese estado.
     */
    private const TRANSITIONS = [
        'pendiente_pago' => ['pagado', 'cancelado', 'incidencia'],
        'pagado'         => ['en_preparacion', 'cancelado', 'incidencia'],
        'en_preparacion' => ['enviado', 'cancelado', 'incidencia'],
        'enviado'        => ['entregado'],
        'entregado'      => [],
        'cancelado'      => [],
        'incidencia'     => [], // TODO(grupo): transiciones desde incidencia
    ];

    // Propuesta de nombre del evento, pendiente de confirmar con el grupo
    private const EVENT_TYPE = 'order.status_changed';

    public function __construct(private AdminOrderRepository $orders = new AdminOrderRepository())
    {
    }

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }

    /** Estados a los que puede pasar un pedido que está en $status. */
    public static function allowedTransitions(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }

    /**
     * Cambia el estado del pedido si la transición es válida. En una sola transacción:
     * actualiza orders.status, añade la fila al historial y registra el evento.
     * Si algo falla, no se guarda nada.
     *
     * @throws OrderStatusException 422 estado inexistente · 404 pedido inexistente · 409 transición no permitida
     */
    public function changeStatus(int $orderId, string $newStatus, int $adminId): void
    {
        if (!self::isValidStatus($newStatus)) {
            throw new OrderStatusException("El estado '$newStatus' no existe", 422);
        }

        $pdo = db();
        $pdo->beginTransaction();
        try {
            // Bloquea el pedido: la transición se valida contra el estado real en este momento
            $order = $this->orders->lockForStatusChange($orderId);
            if ($order === null) {
                throw new OrderStatusException('Pedido no encontrado', 404);
            }

            $fromStatus = $order['status'];
            if (!in_array($newStatus, self::allowedTransitions($fromStatus), true)) {
                throw new OrderStatusException("Un pedido en '$fromStatus' no puede pasar a '$newStatus'", 409);
            }

            $this->orders->updateStatus($orderId, $newStatus);
            $this->orders->addStatusHistory($orderId, $fromStatus, $newStatus);
            logEvent(self::EVENT_TYPE, 'order', $orderId, [
                'code'     => $order['code'],
                'from'     => $fromStatus,
                'to'       => $newStatus,
                'admin_id' => $adminId,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}
