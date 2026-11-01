<?php
declare(strict_types=1);

/**
 * Pedidos: alta desde n8n (escenario 1), cambio de estado (escenario 2) y
 * consulta para la herramienta consultar_pedido del agente.
 */

const ORDER_STATUSES = ['pendiente_pago', 'pagado', 'en_preparacion', 'enviado', 'entregado', 'cancelado', 'incidencia'];
const CARD_CONDITIONS = ['NM', 'LP', 'MP'];

/** Devuelve la lista de campos que faltan o no son válidos en el alta del pedido. */
function validateNewOrder(array $body): array
{
    $errors = [];

    if (!isPositiveInt($body['event_id'] ?? null)) {
        $errors[] = 'event_id';
    }
    if (!isText($body['code'] ?? null, 20)) {
        $errors[] = 'code';
    }
    $customer = is_array($body['customer'] ?? null) ? $body['customer'] : [];
    if (!isText($customer['name'] ?? null, 120)) {
        $errors[] = 'customer.name';
    }
    $email = $customer['email'] ?? null;
    if (!is_string($email) || mb_strlen($email) > 150 || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
        $errors[] = 'customer.email';
    }
    if (!in_array($body['status'] ?? null, ORDER_STATUSES, true)) {
        $errors[] = 'status';
    }
    if (!isAmount($body['total'] ?? null)) {
        $errors[] = 'total';
    }
    if (toDbDate($body['placed_at'] ?? null) === null) {
        $errors[] = 'placed_at';
    }

    $lines = $body['lines'] ?? null;
    if (!is_array($lines) || $lines === [] || !array_is_list($lines)) {
        $errors[] = 'lines';
        return $errors;
    }
    foreach ($lines as $i => $line) {
        $prefix = "lines[$i].";
        if (!is_array($line)) {
            $errors[] = "lines[$i]";
            continue;
        }
        if (!isText($line['product_name'] ?? null, 150)) {
            $errors[] = $prefix . 'product_name';
        }
        if (!isText($line['game'] ?? null, 50)) {
            $errors[] = $prefix . 'game';
        }
        if (isset($line['set_name']) && !isText($line['set_name'], 100)) {
            $errors[] = $prefix . 'set_name';
        }
        if (isset($line['card_condition']) && !in_array($line['card_condition'], CARD_CONDITIONS, true)) {
            $errors[] = $prefix . 'card_condition';
        }
        if (isset($line['language']) && !isText($line['language'], 30)) {
            $errors[] = $prefix . 'language';
        }
        if (isset($line['is_foil']) && !is_bool($line['is_foil'])) {
            $errors[] = $prefix . 'is_foil';
        }
        if (!isPositiveInt($line['quantity'] ?? null)) {
            $errors[] = $prefix . 'quantity';
        }
        if (!isAmount($line['unit_price'] ?? null)) {
            $errors[] = $prefix . 'unit_price';
        }
        if (!isAmount($line['line_total'] ?? null)) {
            $errors[] = $prefix . 'line_total';
        }
    }

    return $errors;
}

/** Id del cliente con ese email; si no existe, lo crea. */
function findOrCreateCustomer(string $name, string $email): int
{
    $stmt = db()->prepare('SELECT id FROM bo_customers WHERE email = ?');
    $stmt->execute([$email]);
    $id = $stmt->fetchColumn();
    if ($id !== false) {
        return (int) $id;
    }

    db()->prepare('INSERT INTO bo_customers (name, email) VALUES (?, ?)')->execute([$name, $email]);

    return (int) db()->lastInsertId();
}

function findOrderByCode(string $code): ?array
{
    $stmt = db()->prepare('SELECT id, code, status FROM bo_orders WHERE code = ?');
    $stmt->execute([$code]);

    return $stmt->fetch() ?: null;
}

// POST /bo-api/orders - crea el pedido con su cliente y sus líneas
$router->post('/orders', function (): void {
    $body = readJsonBody();
    $errors = validateNewOrder($body);
    if ($errors !== []) {
        jsonValidationError($errors);
    }

    $code = trim($body['code']);
    $existing = findOrderByCode($code);
    if ($existing !== null) {
        jsonResponse(['id' => (int) $existing['id'], 'code' => $code, 'result' => 'duplicado']);
    }

    $placedAt = toDbDate($body['placed_at']);
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $customerId = findOrCreateCustomer(trim($body['customer']['name']), $body['customer']['email']);

        $pdo->prepare(
            'INSERT INTO bo_orders (code, customer_id, status, total, placed_at) VALUES (?, ?, ?, ?, ?)'
        )->execute([$code, $customerId, $body['status'], $body['total'], $placedAt]);
        $orderId = (int) $pdo->lastInsertId();

        $insertLine = $pdo->prepare(
            'INSERT INTO bo_order_lines (order_id, product_name, game, set_name, card_condition, language,
                                         is_foil, quantity, unit_price, line_total)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($body['lines'] as $line) {
            $insertLine->execute([
                $orderId,
                $line['product_name'],
                $line['game'],
                $line['set_name'] ?? null,
                $line['card_condition'] ?? null,
                $line['language'] ?? null,
                ($line['is_foil'] ?? false) ? 1 : 0,
                $line['quantity'],
                $line['unit_price'],
                $line['line_total'],
            ]);
        }

        // Estado inicial en el historial, con la fecha del pedido
        $pdo->prepare(
            'INSERT INTO bo_order_status_log (order_id, from_status, to_status, changed_at, event_id)
             VALUES (?, NULL, ?, ?, ?)'
        )->execute([$orderId, $body['status'], $placedAt, $body['event_id']]);

        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        if (isDuplicateKey($e) && ($existing = findOrderByCode($code)) !== null) {
            jsonResponse(['id' => (int) $existing['id'], 'code' => $code, 'result' => 'duplicado']);
        }
        throw $e;
    }

    jsonResponse(['id' => $orderId, 'code' => $code, 'result' => 'creado'], 201);
});

// PATCH /bo-api/orders/{code}/status - cambia el estado y lo guarda en el historial
$router->patch('/orders/{code}/status', function (array $params): void {
    $body = readJsonBody();

    $errors = [];
    if (!isPositiveInt($body['event_id'] ?? null)) {
        $errors[] = 'event_id';
    }
    if (!in_array($body['status'] ?? null, ORDER_STATUSES, true)) {
        $errors[] = 'status';
    }
    $changedAt = isset($body['changed_at']) ? toDbDate($body['changed_at']) : date('Y-m-d H:i:s');
    if ($changedAt === null) {
        $errors[] = 'changed_at';
    }
    if ($errors !== []) {
        jsonValidationError($errors);
    }

    $order = findOrderByCode($params['code']);
    if ($order === null) {
        jsonError('El pedido no existe en el back-office', 404);
    }
    $orderId = (int) $order['id'];

    // Idempotencia: el mismo evento no se aplica dos veces
    $stmt = db()->prepare('SELECT 1 FROM bo_order_status_log WHERE order_id = ? AND event_id = ?');
    $stmt->execute([$orderId, $body['event_id']]);
    if ($stmt->fetchColumn() !== false) {
        jsonResponse(['code' => $order['code'], 'result' => 'duplicado']);
    }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        // Bloquea el pedido para que dos cambios a la vez no se pisen
        $stmt = $pdo->prepare('SELECT status FROM bo_orders WHERE id = ? FOR UPDATE');
        $stmt->execute([$orderId]);
        $currentStatus = $stmt->fetchColumn();

        $stmt = $pdo->prepare('SELECT MAX(changed_at) FROM bo_order_status_log WHERE order_id = ?');
        $stmt->execute([$orderId]);
        $lastChange = $stmt->fetchColumn();

        // Evento desordenado (un reintento que llega tarde): se guarda en el
        // historial, pero no devuelve el pedido a un estado anterior
        $outdated = $lastChange !== null && $changedAt < $lastChange;
        if ($outdated) {
            $stmt = $pdo->prepare(
                'SELECT to_status FROM bo_order_status_log
                 WHERE order_id = ? AND changed_at <= ? ORDER BY changed_at DESC, id DESC LIMIT 1'
            );
            $stmt->execute([$orderId, $changedAt]);
            $fromStatus = $stmt->fetchColumn() ?: null;
        } else {
            $fromStatus = $currentStatus;
            $pdo->prepare('UPDATE bo_orders SET status = ? WHERE id = ?')->execute([$body['status'], $orderId]);
        }

        $pdo->prepare(
            'INSERT INTO bo_order_status_log (order_id, from_status, to_status, changed_at, event_id)
             VALUES (?, ?, ?, ?, ?)'
        )->execute([$orderId, $fromStatus, $body['status'], $changedAt, $body['event_id']]);
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        if (isDuplicateKey($e)) {
            jsonResponse(['code' => $order['code'], 'result' => 'duplicado']);
        }
        throw $e;
    }

    jsonResponse([
        'code'        => $order['code'],
        'from_status' => $fromStatus,
        'to_status'   => $body['status'],
        'result'      => $outdated ? 'desfasado' : 'actualizado',
    ]);
});

// GET /bo-api/orders/{code} - pedido con líneas e historial, sin el email del cliente
$router->get('/orders/{code}', function (array $params): void {
    $stmt = db()->prepare(
        'SELECT o.id, o.code, o.customer_id, c.name AS customer_name, o.status, o.total, o.placed_at, o.synced_at
         FROM bo_orders o
         JOIN bo_customers c ON c.id = o.customer_id
         WHERE o.code = ?'
    );
    $stmt->execute([$params['code']]);
    $order = $stmt->fetch();
    if (!$order) {
        jsonError('El pedido no existe en el back-office', 404);
    }

    $stmt = db()->prepare(
        'SELECT product_name, game, set_name, card_condition, language, is_foil, quantity, unit_price, line_total
         FROM bo_order_lines WHERE order_id = ? ORDER BY id'
    );
    $stmt->execute([$order['id']]);
    $lines = array_map(fn (array $l): array => [
        'product_name'   => $l['product_name'],
        'game'           => $l['game'],
        'set_name'       => $l['set_name'],
        'card_condition' => $l['card_condition'],
        'language'       => $l['language'],
        'is_foil'        => (bool) $l['is_foil'],
        'quantity'       => (int) $l['quantity'],
        'unit_price'     => (float) $l['unit_price'],
        'line_total'     => (float) $l['line_total'],
    ], $stmt->fetchAll());

    $stmt = db()->prepare(
        'SELECT from_status, to_status, changed_at, event_id
         FROM bo_order_status_log WHERE order_id = ? ORDER BY changed_at, id'
    );
    $stmt->execute([$order['id']]);
    $history = array_map(fn (array $h): array => [
        'from_status' => $h['from_status'],
        'to_status'   => $h['to_status'],
        'changed_at'  => fromDbDate($h['changed_at']),
        'event_id'    => $h['event_id'] === null ? null : (int) $h['event_id'],
    ], $stmt->fetchAll());

    jsonResponse([
        'id'            => (int) $order['id'],
        'code'          => $order['code'],
        'customer_id'   => (int) $order['customer_id'],
        'customer_name' => $order['customer_name'],
        'status'        => $order['status'],
        'total'         => (float) $order['total'],
        'placed_at'     => fromDbDate($order['placed_at']),
        'synced_at'     => fromDbDate($order['synced_at']),
        'lines'         => $lines,
        'history'       => $history,
    ]);
});
