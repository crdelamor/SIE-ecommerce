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

    public static function isValidStatus(string $status): bool
    {
        return in_array($status, self::STATUSES, true);
    }
}
