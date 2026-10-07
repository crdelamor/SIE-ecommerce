<?php
declare(strict_types=1);

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

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }

    /** Estados a los que puede pasar un pedido que está en $status. */
    public static function allowedTransitions(string $status): array
    {
        return self::TRANSITIONS[$status] ?? [];
    }
}
