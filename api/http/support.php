<?php
declare(strict_types=1);

/**
 * Formulario público de soporte (C).
 *
 *   POST /support   Guarda la incidencia y registra support.requested
 *   GET  /integration/tickets/{id}   Datos de la incidencia para n8n (Tarea 2, con X-Api-Key)
 */

require_once __DIR__ . '/../repositories/SupportTicketRepository.php';
require_once __DIR__ . '/../services/SupportService.php';

// Body: { name, email, order_code (opcional), reason, message }
// -> 201 { "ticket_id": 5, "status": "abierta" }
// -> 422 { "error": "Revisa los campos marcados", "fields": { "email": "..." } }
// readJsonBody() está en http/admin.php (se carga antes); Celia la moverá a lib/request.php
$router->post('/support', function (): void {
    try {
        $ticketId = (new SupportService())->create(readJsonBody());
    } catch (SupportValidationException $e) {
        jsonResponse(['error' => $e->getMessage(), 'fields' => $e->fields], 422);
    }

    jsonResponse(['ticket_id' => $ticketId, 'status' => 'abierta'], 201);
});

// -> 200 { id, status, subject, message, opened_at, order_code, customer { name, email } } · 404 si no existe
// Lo llama el flujo "Incidencias" de n8n. requireIntegrationKey() está en http/integration.php.
$router->get('/integration/tickets/{id}', function (array $params): void {
    requireIntegrationKey();

    $id = $params['id'] ?? '';
    $ticket = ctype_digit($id) ? (new SupportTicketRepository())->findForIntegration((int) $id) : null;
    if ($ticket === null) {
        jsonError('La incidencia no existe', 404);
    }

    jsonResponse([
        'id'         => (int) $ticket['id'],
        'status'     => $ticket['status'],
        'subject'    => $ticket['subject'],
        'message'    => $ticket['message'],
        'opened_at'  => (new DateTimeImmutable($ticket['created_at']))->format(DATE_ATOM),
        'order_code' => $ticket['order_code'],
        'customer'   => ['name' => $ticket['name'], 'email' => $ticket['email']],
    ]);
});
