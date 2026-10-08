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
     * Transiciones válidas: estado actual => estados a los que puede pasar.
     * Las del contrato, más las de incidencia acordadas por el grupo: en una tienda de
     * cartas la incidencia suele aparecer al recibir el pedido (peor estado, otro idioma,
     * carta dañada), así que también se llega desde enviado y entregado. Se sale de ella
     * según cómo se resuelva: reenvío (en_preparacion), reembolso (cancelado) o sin
     * reenvío (entregado). Solo cancelado es final.
     */
    private const TRANSITIONS = [
        'pendiente_pago' => ['pagado', 'cancelado', 'incidencia'],
        'pagado'         => ['en_preparacion', 'cancelado', 'incidencia'],
        'en_preparacion' => ['enviado', 'cancelado', 'incidencia'],
        'enviado'        => ['entregado', 'incidencia'],
        'entregado'      => ['incidencia'],
        'cancelado'      => [],
        'incidencia'     => ['en_preparacion', 'cancelado', 'entregado'],
    ];

    // Nombre del evento acordado por el grupo (el contrato no lo fijaba)
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
     * Primera fila del historial de un pedido recién creado (de nada a pendiente_pago).
     * No registra order.status_changed: el alta ya la registra el evento order.created.
     * Se llama dentro de la transacción que crea el pedido.
     */
    public function recordCreated(int $orderId): void
    {
        $this->orders->addStatusHistory($orderId, null, 'pendiente_pago');
    }

    /**
     * Cambia el estado del pedido si la transición es válida. En una sola transacción:
     * actualiza orders.status, añade la fila al historial, devuelve el stock si se
     * cancela y registra el evento. Si algo falla, no se guarda nada.
     *
     * $adminId es null cuando el cambio no lo hace un administrador (p. ej. el pago
     * simulado de la tienda). Si ya hay una transacción abierta (la del pago), el cambio
     * se hace dentro de ella y es quien la abrió el que hace commit o rollback.
     *
     * @throws OrderStatusException 422 estado inexistente · 404 pedido inexistente · 409 transición no permitida
     */
    public function changeStatus(int $orderId, string $newStatus, ?int $adminId = null): void
    {
        if (!self::isValidStatus($newStatus)) {
            throw new OrderStatusException("El estado '$newStatus' no existe", 422);
        }

        $pdo = db();
        // PDO no admite transacciones anidadas: solo se abre una si no hay ya otra
        $ownTransaction = !$pdo->inTransaction();
        if ($ownTransaction) {
            $pdo->beginTransaction();
        }
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

            // El stock se resta al crear el pedido: al cancelarlo, venga de donde venga, se devuelve
            $restocked = $newStatus === 'cancelado';
            if ($restocked) {
                $this->orders->restockOrderItems($orderId);
            }

            logEvent(self::EVENT_TYPE, 'order', $orderId, [
                'code'      => $order['code'],
                'from'      => $fromStatus,
                'to'        => $newStatus,
                'admin_id'  => $adminId,
                'restocked' => $restocked,
            ]);

            if ($ownTransaction) {
                $pdo->commit();
            }
        } catch (Throwable $e) {
            if ($ownTransaction) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }
}
