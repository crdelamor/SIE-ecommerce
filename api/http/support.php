<?php
declare(strict_types=1);

/**
 * Formulario público de soporte (C).
 *
 *   POST /support   Guarda la incidencia y registra support.requested
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
