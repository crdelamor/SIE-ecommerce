<?php
declare(strict_types=1);

/**
 * Propuestas del agente de IA (herramienta registrar_propuesta) y decisión humana.
 * El agente solo propone: toda propuesta empieza en pendiente_revision.
 */

// Pasos permitidos de final_status: estado actual => estados a los que puede pasar
const PROPOSAL_TRANSITIONS = [
    'pendiente_revision' => ['aprobado', 'rechazado'],
    'aprobado'           => ['ejecutado_simulado'],
    'rechazado'          => [],
    'ejecutado_simulado' => [],
];

function findProposal(int $id): ?array
{
    $stmt = db()->prepare('SELECT id, final_status, reviewed_by, reviewed_at FROM bo_agent_proposals WHERE id = ?');
    $stmt->execute([$id]);

    return $stmt->fetch() ?: null;
}

// POST /bo-api/proposals - guarda la salida estructurada del agente
$router->post('/proposals', function (): void {
    $body = readJsonBody();

    $errors = [];
    if (!isPositiveInt($body['ticket_id'] ?? null)) {
        $errors[] = 'ticket_id';
    }
    if (!isText($body['case_type'] ?? null, 50)) {
        $errors[] = 'case_type';
    }
    if (!is_array($body['consulted_data'] ?? null)) {
        $errors[] = 'consulted_data';
    }
    if (!isText($body['proposed_action'] ?? null, 5000)) {
        $errors[] = 'proposed_action';
    }
    if (!isText($body['justification'] ?? null, 5000)) {
        $errors[] = 'justification';
    }
    $confidence = $body['confidence'] ?? null;
    if (!(is_int($confidence) || is_float($confidence)) || $confidence < 0 || $confidence > 1) {
        $errors[] = 'confidence';
    }
    if (isset($body['risks']) && !is_string($body['risks'])) {
        $errors[] = 'risks';
    }
    if ($errors !== []) {
        jsonValidationError($errors);
    }

    $stmt = db()->prepare('SELECT 1 FROM bo_tickets WHERE id = ?');
    $stmt->execute([$body['ticket_id']]);
    if ($stmt->fetchColumn() === false) {
        jsonError('El ticket no existe en el back-office', 404);
    }

    // final_status no se lee del cuerpo: lo decide una persona después
    db()->prepare(
        'INSERT INTO bo_agent_proposals
             (ticket_id, case_type, consulted_data, proposed_action, justification, confidence, risks)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    )->execute([
        $body['ticket_id'],
        $body['case_type'],
        json_encode($body['consulted_data'], JSON_UNESCAPED_UNICODE),
        $body['proposed_action'],
        $body['justification'],
        round((float) $confidence, 2),
        $body['risks'] ?? null,
    ]);

    jsonResponse([
        'id'           => (int) db()->lastInsertId(),
        'ticket_id'    => $body['ticket_id'],
        'final_status' => 'pendiente_revision',
    ], 201);
});

// PATCH /bo-api/proposals/{id} - decisión humana (Aprobar / Rechazar) y ejecución
$router->patch('/proposals/{id}', function (array $params): void {
    $body = readJsonBody();

    $newStatus = $body['final_status'] ?? null;
    $isReview = in_array($newStatus, ['aprobado', 'rechazado'], true);
    $errors = [];
    if (!$isReview && $newStatus !== 'ejecutado_simulado') {
        $errors[] = 'final_status';
    }
    if ($isReview && !isText($body['reviewed_by'] ?? null, 150)) {
        $errors[] = 'reviewed_by';
    }
    if ($errors !== []) {
        jsonValidationError($errors);
    }

    $proposal = findProposal((int) $params['id']);
    if ($proposal === null) {
        jsonError('La propuesta no existe', 404);
    }
    if (!in_array($newStatus, PROPOSAL_TRANSITIONS[$proposal['final_status']], true)) {
        jsonResponse([
            'error'  => "No se puede pasar de {$proposal['final_status']} a $newStatus",
            'fields' => ['final_status'],
        ], 422);
    }

    if ($isReview) {
        $stmt = db()->prepare(
            'UPDATE bo_agent_proposals SET final_status = ?, reviewed_by = ?, reviewed_at = NOW()
             WHERE id = ? AND final_status = ?'
        );
        $stmt->execute([$newStatus, trim($body['reviewed_by']), $proposal['id'], $proposal['final_status']]);
    } else {
        $stmt = db()->prepare('UPDATE bo_agent_proposals SET final_status = ? WHERE id = ? AND final_status = ?');
        $stmt->execute([$newStatus, $proposal['id'], $proposal['final_status']]);
    }
    // Si otra petición la cambió a la vez, no se pisa su decisión
    if ($stmt->rowCount() === 0) {
        jsonResponse(['error' => 'La propuesta ha cambiado mientras tanto', 'fields' => ['final_status']], 422);
    }

    $proposal = findProposal((int) $proposal['id']);
    jsonResponse([
        'id'           => (int) $proposal['id'],
        'final_status' => $proposal['final_status'],
        'reviewed_by'  => $proposal['reviewed_by'],
        'reviewed_at'  => fromDbDate($proposal['reviewed_at']),
    ]);
});
