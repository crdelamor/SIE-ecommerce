<?php
declare(strict_types=1);

/**
 * Clientes: se crean al llegar su primer pedido o ticket y se identifican por email.
 * El historial de incidencias es la herramienta consultar_historial_cliente del agente.
 */

function isValidEmail(mixed $email): bool
{
    return is_string($email) && mb_strlen($email) <= 150 && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Id del cliente con ese email; si no existe, lo crea.
 * Se hace en una sola sentencia para que dos altas a la vez con el mismo email
 * no choquen con el UNIQUE: si ya existe, LAST_INSERT_ID(id) devuelve el suyo.
 */
function findOrCreateCustomer(string $name, string $email): int
{
    db()->prepare(
        'INSERT INTO bo_customers (name, email) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)'
    )->execute([$name, $email]);

    return (int) db()->lastInsertId();
}

// GET /bo-api/customers/{id}/tickets - incidencias anteriores, sin email ni chat de Telegram
$router->get('/customers/{id}/tickets', function (array $params): void {
    $stmt = db()->prepare('SELECT id FROM bo_customers WHERE id = ?');
    $stmt->execute([(int) $params['id']]);
    if ($stmt->fetchColumn() === false) {
        jsonError('El cliente no existe en el back-office', 404);
    }

    $stmt = db()->prepare(
        'SELECT id, shop_ticket_id, order_code, reason, status, priority, opened_at, closed_at
         FROM bo_tickets WHERE customer_id = ? ORDER BY opened_at DESC, id DESC'
    );
    $stmt->execute([(int) $params['id']]);

    $tickets = array_map(fn (array $t): array => [
        'id'             => (int) $t['id'],
        'shop_ticket_id' => (int) $t['shop_ticket_id'],
        'order_code'     => $t['order_code'],
        'reason'         => $t['reason'],
        'status'         => $t['status'],
        'priority'       => $t['priority'],
        'opened_at'      => fromDbDate($t['opened_at']),
        'closed_at'      => fromDbDate($t['closed_at']),
    ], $stmt->fetchAll());

    jsonResponse(['customer_id' => (int) $params['id'], 'tickets' => $tickets]);
});
