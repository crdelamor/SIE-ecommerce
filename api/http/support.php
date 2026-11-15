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

/**
 * Firma del enlace de Telegram de una incidencia (docs/integracion.md, apartado 7).
 * Así nadie puede enlazar su chat a la incidencia de otro cambiando el número.
 * Sin webhook_secret en config.php no hay firma ni enlace.
 */
function telegramSignature(int $ticketId): ?string
{
    $secret = integrationConfig()['webhook_secret'] ?? '';
    return $secret === '' ? null : substr(hash_hmac('sha256', 'telegram:' . $ticketId, $secret), 0, 16);
}

// Body: { name, email, order_code (opcional), reason, message }
// -> 201 { "ticket_id": 5, "status": "abierta", "telegram_url": "https://t.me/<bot>?start=5-<firma>" o null }
// -> 422 { "error": "Revisa los campos marcados", "fields": { "email": "..." } }
// readJsonBody() está en http/admin.php (se carga antes); Celia la moverá a lib/request.php
$router->post('/support', function (): void {
    try {
        $ticketId = (new SupportService())->create(readJsonBody());
    } catch (SupportValidationException $e) {
        jsonResponse(['error' => $e->getMessage(), 'fields' => $e->fields], 422);
    }

    $bot = integrationConfig()['telegram_bot'] ?? '';
    $signature = telegramSignature($ticketId);
    $telegramUrl = $bot !== '' && $signature !== null ? "https://t.me/$bot?start=$ticketId-$signature" : null;

    jsonResponse(['ticket_id' => $ticketId, 'status' => 'abierta', 'telegram_url' => $telegramUrl], 201);
});

// -> 200 { id, status, subject, message, opened_at, order_code, customer { name, email } } · 404 si no existe
// Con ?firma=... (flujo "Telegram") -> 403 si la firma no es la de esa incidencia.
// Lo llaman los flujos "Incidencias" y "Telegram" de n8n. requireIntegrationKey() está en http/integration.php.
$router->get('/integration/tickets/{id}', function (array $params): void {
    requireIntegrationKey();

    $id = $params['id'] ?? '';
    $ticket = ctype_digit($id) ? (new SupportTicketRepository())->findForIntegration((int) $id) : null;
    if ($ticket === null) {
        jsonError('La incidencia no existe', 404);
    }

    $firma = $_GET['firma'] ?? null;
    if ($firma !== null) {
        $expected = telegramSignature((int) $ticket['id']);
        if ($expected === null || !is_string($firma) || !hash_equals($expected, $firma)) {
            jsonError('La firma del enlace no es válida', 403);
        }
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
