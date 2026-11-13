<?php
declare(strict_types=1);

/**
 * Incidencias: alta desde el flujo "Incidencias" (escenario 3) y cambios de
 * estado, prioridad (clasificar_incidencia) o chat de Telegram (flujo "Telegram").
 */

const TICKET_STATUSES = ['abierta', 'en_curso', 'cerrada'];
const TICKET_PRIORITIES = ['baja', 'media', 'alta'];

function findTicketByShopId(int $shopTicketId): ?array
{
    $stmt = db()->prepare('SELECT id, shop_ticket_id FROM bo_tickets WHERE shop_ticket_id = ?');
    $stmt->execute([$shopTicketId]);

    return $stmt->fetch() ?: null;
}

function ticketDuplicateResponse(array $ticket): void
{
    jsonResponse([
        'id'             => (int) $ticket['id'],
        'shop_ticket_id' => (int) $ticket['shop_ticket_id'],
        'result'         => 'duplicado',
    ]);
}

// POST /bo-api/tickets - crea el ticket asociado al cliente y, si lo hay, al pedido
$router->post('/tickets', function (): void {
    $body = readJsonBody();

    $errors = [];
    if (!isPositiveInt($body['event_id'] ?? null)) {
        $errors[] = 'event_id';
    }
    if (!isPositiveInt($body['shop_ticket_id'] ?? null)) {
        $errors[] = 'shop_ticket_id';
    }
    $customer = is_array($body['customer'] ?? null) ? $body['customer'] : [];
    if (!isText($customer['name'] ?? null, 120)) {
        $errors[] = 'customer.name';
    }
    if (!isValidEmail($customer['email'] ?? null)) {
        $errors[] = 'customer.email';
    }
    if (isset($body['order_code']) && !isText($body['order_code'], 20)) {
        $errors[] = 'order_code';
    }
    if (!isText($body['reason'] ?? null, 200)) {
        $errors[] = 'reason';
    }
    $openedAt = toDbDate($body['opened_at'] ?? null);
    if ($openedAt === null) {
        $errors[] = 'opened_at';
    }
    if ($errors !== []) {
        jsonValidationError($errors);
    }

    $existing = findTicketByShopId($body['shop_ticket_id']);
    if ($existing !== null) {
        ticketDuplicateResponse($existing);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $customerId = findOrCreateCustomer(trim($customer['name']), $customer['email']);
        $pdo->prepare(
            'INSERT INTO bo_tickets (shop_ticket_id, customer_id, order_code, reason, opened_at) VALUES (?, ?, ?, ?, ?)'
        )->execute([
            $body['shop_ticket_id'],
            $customerId,
            isset($body['order_code']) ? trim($body['order_code']) : null,
            trim($body['reason']),
            $openedAt,
        ]);
        $ticketId = (int) $pdo->lastInsertId();
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        if (isDuplicateKey($e) && ($existing = findTicketByShopId($body['shop_ticket_id'])) !== null) {
            ticketDuplicateResponse($existing);
        }
        throw $e;
    }

    jsonResponse(['id' => $ticketId, 'shop_ticket_id' => $body['shop_ticket_id'], 'result' => 'creado'], 201);
});

// PATCH /bo-api/tickets/{id} - estado, prioridad o chat de Telegram
$router->patch('/tickets/{id}', function (array $params): void {
    $body = readJsonBody();

    $fields = ['status', 'priority', 'telegram_chat_id'];
    if (array_intersect($fields, array_keys($body)) === []) {
        jsonValidationError($fields);
    }
    $errors = [];
    if (array_key_exists('status', $body) && !in_array($body['status'], TICKET_STATUSES, true)) {
        $errors[] = 'status';
    }
    if (array_key_exists('priority', $body) && !in_array($body['priority'], TICKET_PRIORITIES, true)) {
        $errors[] = 'priority';
    }
    // Telegram da el id del chat como número; se guarda como texto
    $chatId = $body['telegram_chat_id'] ?? null;
    if (is_int($chatId)) {
        $chatId = (string) $chatId;
    }
    if (array_key_exists('telegram_chat_id', $body) && !isText($chatId, 32)) {
        $errors[] = 'telegram_chat_id';
    }
    if ($errors !== []) {
        jsonValidationError($errors);
    }

    $stmt = db()->prepare('SELECT id, customer_id, status, priority, closed_at FROM bo_tickets WHERE id = ?');
    $stmt->execute([(int) $params['id']]);
    $ticket = $stmt->fetch();
    if (!$ticket) {
        jsonError('El ticket no existe en el back-office', 404);
    }

    $status = $body['status'] ?? $ticket['status'];
    $priority = $body['priority'] ?? $ticket['priority'];
    // closed_at se pone al cerrar y se borra si se reabre
    $closedAt = match (true) {
        $status !== 'cerrada'           => null,
        $ticket['status'] !== 'cerrada' => date('Y-m-d H:i:s'),
        default                         => $ticket['closed_at'],
    };

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('UPDATE bo_tickets SET status = ?, priority = ?, closed_at = ? WHERE id = ?')
            ->execute([$status, $priority, $closedAt, $ticket['id']]);
        // El chat se guarda en el cliente del ticket (tabla bo_customers)
        if ($chatId !== null) {
            $pdo->prepare('UPDATE bo_customers SET telegram_chat_id = ? WHERE id = ?')
                ->execute([$chatId, $ticket['customer_id']]);
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }

    jsonResponse([
        'id'        => (int) $ticket['id'],
        'status'    => $status,
        'priority'  => $priority,
        'closed_at' => fromDbDate($closedAt),
        'result'    => 'actualizado',
    ]);
});
