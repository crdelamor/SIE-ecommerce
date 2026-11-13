<?php
declare(strict_types=1);

/**
 * Registro de integración: una fila por ejecución de n8n (también los reintentos).
 * El flujo "Reintentos" lee de aquí los eventos pendientes.
 */

const LOG_RESULTS = ['ok', 'error', 'duplicado', 'pendiente'];

// POST /bo-api/integration-log - registra el resultado de una ejecución
$router->post('/integration-log', function (): void {
    $body = readJsonBody();

    $errors = [];
    $eventId = $body['event_id'] ?? null;
    if ($eventId !== null && !isPositiveInt($eventId)) {
        $errors[] = 'event_id';
    }
    if (!isText($body['event_type'] ?? null, 50)) {
        $errors[] = 'event_type';
    }
    if (!isText($body['workflow'] ?? null, 50)) {
        $errors[] = 'workflow';
    }
    if (!in_array($body['result'] ?? null, LOG_RESULTS, true)) {
        $errors[] = 'result';
    }
    if (isset($body['error_type']) && !isText($body['error_type'], 50)) {
        $errors[] = 'error_type';
    }
    if (isset($body['message']) && !is_string($body['message'])) {
        $errors[] = 'message';
    }
    if (isset($body['payload']) && !is_array($body['payload'])) {
        $errors[] = 'payload';
    }
    $executionId = $body['n8n_execution_id'] ?? null;
    if ($executionId !== null && !is_string($executionId) && !is_int($executionId)) {
        $errors[] = 'n8n_execution_id';
    }
    if ($errors !== []) {
        jsonValidationError($errors);
    }

    db()->prepare(
        'INSERT INTO bo_integration_log
             (event_id, event_type, workflow, result, error_type, message, payload, n8n_execution_id)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $eventId,
        $body['event_type'],
        $body['workflow'],
        $body['result'],
        $body['error_type'] ?? null,
        isset($body['message']) ? mb_substr($body['message'], 0, 255) : null,
        isset($body['payload']) ? json_encode($body['payload'], JSON_UNESCAPED_UNICODE) : null,
        $executionId === null ? null : mb_substr((string) $executionId, 0, 50),
    ]);

    jsonResponse(['id' => (int) db()->lastInsertId()], 201);
});

// GET /bo-api/integration-log/processed?after_id=N - event_id ya procesados (último resultado ok o duplicado).
// Con esta lista el flujo "Reintentos" detecta los eventos que se perdieron por el camino.
$router->get('/integration-log/processed', function (): void {
    $afterId = filter_var($_GET['after_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
    if ($afterId === false) {
        jsonValidationError(['after_id']);
    }
    $limit = filter_var($_GET['limit'] ?? 500, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 1000]]);
    if ($limit === false) {
        jsonValidationError(['limit']);
    }

    $stmt = db()->prepare(
        "SELECT l.event_id
         FROM bo_integration_log l
         WHERE l.event_id > ?
           AND l.id = (SELECT MAX(l2.id) FROM bo_integration_log l2 WHERE l2.event_id = l.event_id)
           AND l.result IN ('ok', 'duplicado')
         ORDER BY l.event_id
         LIMIT ?"
    );
    $stmt->bindValue(1, $afterId, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    jsonResponse(['event_ids' => array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN))]);
});

// GET /bo-api/integration-log?result=pendiente&limit=50
$router->get('/integration-log', function (): void {
    $result = $_GET['result'] ?? null;
    if (!in_array($result, LOG_RESULTS, true)) {
        jsonValidationError(['result']);
    }
    $limit = filter_var($_GET['limit'] ?? 50, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 200]]);
    if ($limit === false) {
        jsonValidationError(['limit']);
    }

    $sql = 'SELECT id, event_id, event_type, workflow, result, error_type, message, payload, n8n_execution_id, created_at
            FROM bo_integration_log l
            WHERE l.result = ?';
    // Un pendiente que ya se reintentó con otro resultado no se vuelve a devolver
    if ($result === 'pendiente') {
        $sql .= ' AND (l.event_id IS NULL
                       OR l.id = (SELECT MAX(l2.id) FROM bo_integration_log l2 WHERE l2.event_id = l.event_id))';
    }
    // En orden de evento, para reintentarlos en el mismo orden en que pasaron
    $sql .= ' ORDER BY l.event_id IS NULL, l.event_id, l.id LIMIT ?';

    $stmt = db()->prepare($sql);
    $stmt->bindValue(1, $result);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();

    $items = array_map(fn (array $row): array => [
        'id'               => (int) $row['id'],
        'event_id'         => $row['event_id'] === null ? null : (int) $row['event_id'],
        'event_type'       => $row['event_type'],
        'workflow'         => $row['workflow'],
        'result'           => $row['result'],
        'error_type'       => $row['error_type'],
        'message'          => $row['message'],
        'payload'          => $row['payload'] === null ? null : json_decode($row['payload'], true),
        'n8n_execution_id' => $row['n8n_execution_id'],
        'created_at'       => fromDbDate($row['created_at']),
    ], $stmt->fetchAll());

    jsonResponse(['items' => $items]);
});
