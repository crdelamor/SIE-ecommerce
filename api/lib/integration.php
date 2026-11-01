<?php
declare(strict_types=1);

/**
 * Integración con n8n (Tarea 2, A).
 *
 * logEvent() deja en cola los eventos integrados y, al terminar la petición, se envían al
 * webhook de n8n con el sobre común (docs/integracion.md). Si n8n no responde en 2 s la compra
 * sigue igual: el evento ya está en `events` y el flujo "Reintentos" lo recupera con
 * GET /api/integration/events?after_id=.
 */

require_once __DIR__ . '/../repositories/IntegrationRepository.php';

// Los de navegación (product.viewed, cart.item_added, checkout.started) se quedan en la tienda
const INTEGRATED_EVENTS = ['order.created', 'payment.simulated', 'order.status_changed', 'support.requested'];

/** Sección 'integration' de config.php; vacía si no está (entonces no se envía nada). */
function integrationConfig(): array
{
    static $config = null;
    return $config ??= (require __DIR__ . '/../config.php')['integration'] ?? [];
}

/** La llama logEvent() después del INSERT. Los eventos que no se integran se ignoran. */
function queueIntegrationEvent(int $eventId, string $type): void
{
    static $ids = null;
    if (!in_array($type, INTEGRATED_EVENTS, true) || empty(integrationConfig()['webhook_url'])) {
        return;
    }
    if ($ids === null) {
        $ids = [];
        // Se envían al terminar la petición, cuando las transacciones ya se han confirmado
        register_shutdown_function(function () use (&$ids): void {
            sendIntegrationEvents($ids);
        });
    }
    $ids[] = $eventId;
}

/**
 * Envía al webhook los eventos de la cola que existen en la base de datos: los de una
 * transacción deshecha no están, así que nunca se avisa de una compra que no se hizo.
 */
function sendIntegrationEvents(array $ids): void
{
    try {
        // Una transacción abierta al terminar es un error fatal a medias: no se envía nada
        if (db()->inTransaction()) {
            return;
        }
        if (function_exists('fastcgi_finish_request')) {
            fastcgi_finish_request(); // el cliente recibe su respuesta sin esperar a n8n
        }

        $config = integrationConfig();
        foreach ((new IntegrationRepository())->findEventsByIds($ids) as $event) {
            $sent = @file_get_contents($config['webhook_url'], false, stream_context_create(['http' => [
                'method'  => 'POST',
                'header'  => "Content-Type: application/json\r\nX-Webhook-Secret: " . ($config['webhook_secret'] ?? '') . "\r\n",
                'content' => json_encode($event, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'timeout' => 2,
            ]]));
            if ($sent === false) {
                error_log("Integración: n8n no ha recibido el evento {$event['event_id']} ({$event['type']})");
            }
        }
    } catch (Throwable $e) {
        // La respuesta ya se ha enviado: solo queda apuntarlo
        error_log('Integración: ' . $e->getMessage());
    }
}
