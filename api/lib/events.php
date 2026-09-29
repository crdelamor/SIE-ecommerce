<?php
declare(strict_types=1);

/**
 * Registro de eventos. TODOS los eventos se guardan con logEvent().
 *
 * Ejemplo:
 *   logEvent('order.created', 'order', $orderId, ['code' => $code, 'total' => $total]);
 */
function logEvent(string $type, ?string $entityType = null, ?int $entityId = null, array $payload = []): void
{
    $stmt = db()->prepare(
        'INSERT INTO events (type, entity_type, entity_id, payload, session_id, created_at)
         VALUES (:type, :entity_type, :entity_id, :payload, :session_id, NOW())'
    );

    $stmt->execute([
        'type'        => $type,
        'entity_type' => $entityType,
        'entity_id'   => $entityId,
        'payload'     => json_encode($payload, JSON_UNESCAPED_UNICODE),
        // Identificador anónimo de la visita que enviará el front en la cabecera X-Session-Id
        'session_id'  => isset($_SERVER['HTTP_X_SESSION_ID']) ? substr($_SERVER['HTTP_X_SESSION_ID'], 0, 64) : null,
    ]);
}
