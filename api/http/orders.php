<?php
declare(strict_types=1);

// Rutas del flujo de compra (A)

// Eventos que puede mandar el navegador. Los demás (order.created, payment.simulated)
// solo los registra el servidor.
const BROWSER_EVENTS = [
    'cart.item_added'  => 'product',
    'checkout.started' => null,
];

// POST /events  { "type": "cart.item_added", "entity_id": 3, "payload": { "quantity": 1 } }
$router->post('/events', function (): void {
    $body = readJsonBody();
    $type = $body['type'] ?? '';

    if (!is_string($type) || !array_key_exists($type, BROWSER_EVENTS)) {
        jsonError('Tipo de evento no permitido', 400);
    }

    $entityType = BROWSER_EVENTS[$type];
    $entityId = null;
    if ($entityType !== null) {
        $entityId = $body['entity_id'] ?? null;
        if (!is_int($entityId) || $entityId < 1) {
            jsonError('Falta el id del producto', 400);
        }
    }

    $payload = $body['payload'] ?? [];
    if (!is_array($payload) || strlen(json_encode($payload)) > 1000) {
        jsonError('Datos del evento no válidos', 400);
    }

    logEvent($type, $entityType, $entityId, $payload);
    jsonResponse(['status' => 'ok'], 201);
});
