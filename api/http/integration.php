<?php
declare(strict_types=1);

/**
 * Rutas de integración con n8n (A). Solo las llama n8n, con la cabecera X-Api-Key.
 *
 *   GET /integration/orders/{code}
 *   GET /integration/events?after_id=&limit=
 *   GET /integration/funnel?from=&to=
 */

// lib/integration.php ya lo carga events.php (con IntegrationRepository)
require_once __DIR__ . '/../repositories/AdminOrderRepository.php';

/** 401 si falta la clave de integración o no coincide (comparación en tiempo constante). */
function requireIntegrationKey(): void
{
    $expected = integrationConfig()['api_key'] ?? '';
    $given = $_SERVER['HTTP_X_API_KEY'] ?? '';
    if ($expected === '' || !is_string($given) || !hash_equals($expected, $given)) {
        jsonError('Falta la cabecera X-Api-Key o no es válida', 401);
    }
}

// -> 200 { code, status, total, placed_at, customer { name, email }, lines, payments } · 404 si no existe
// Sin dirección, teléfono ni referencia del pago: el back-office no los necesita.
$router->get('/integration/orders/{code}', function (array $params): void {
    requireIntegrationKey();

    $code = $params['code'] ?? '';
    $id = preg_match('/^PED-\d{6,}$/', $code) === 1 ? (new IntegrationRepository())->findOrderIdByCode($code) : null;
    $order = $id === null ? null : (new AdminOrderRepository())->findById($id);
    if ($order === null) {
        jsonError('El pedido no existe', 404);
    }

    jsonResponse([
        'code'      => $order['code'],
        'status'    => $order['status'],
        'total'     => $order['total'],
        'placed_at' => (new DateTimeImmutable($order['created_at']))->format(DATE_ATOM),
        'customer'  => ['name' => $order['customer']['name'], 'email' => $order['customer']['email']],
        // Los datos TCG son los del producto actual: order_items no los copia al comprar
        'lines'     => array_map(fn (array $item): array => [
            'product_name'   => $item['product_name'],
            'game'           => $item['game'],
            'set_name'       => $item['set_name'],
            'card_condition' => $item['card_condition'],
            'language'       => $item['language'],
            'is_foil'        => $item['is_foil'] ?? false,
            'quantity'       => $item['quantity'],
            'unit_price'     => $item['unit_price'],
            'line_total'     => $item['line_total'],
        ], $order['items']),
        'payments'  => array_map(fn (array $payment): array => [
            'method'     => $payment['method'],
            'status'     => $payment['status'],
            'amount'     => $payment['amount'],
            'created_at' => (new DateTimeImmutable($payment['created_at']))->format(DATE_ATOM),
        ], $order['payments']),
    ]);
});

// ?after_id=812&limit=100  ->  200 { "events": [ sobre, ... ] } del más antiguo al más reciente
// Para el flujo "Reintentos": recupera los eventos que no llegaron por el webhook.
$router->get('/integration/events', function (): void {
    requireIntegrationKey();

    $afterId = queryParam('after_id') ?? '';
    $limit = queryParam('limit') ?? '100';
    if (!ctype_digit($afterId) || !ctype_digit($limit) || (int) $limit < 1 || (int) $limit > 500) {
        jsonError('after_id es obligatorio y limit va de 1 a 500', 400);
    }

    jsonResponse(['events' => (new IntegrationRepository())->findEventsAfter((int) $afterId, (int) $limit)]);
});

// ?from=2026-11-01&to=2026-11-30 (opcionales)  ->  200 { from, to, steps: [ { type, events, sessions }, ... ] }
// Para la métrica de conversión del cuadro de mando: los eventos de navegación no salen de la tienda.
$router->get('/integration/funnel', function (): void {
    requireIntegrationKey();

    $from = queryParam('from') ?? '2000-01-01';
    $to = queryParam('to') ?? date('Y-m-d');
    foreach ([$from, $to] as $date) {
        if (DateTimeImmutable::createFromFormat('!Y-m-d', $date)?->format('Y-m-d') !== $date) {
            jsonError('from y to van en formato AAAA-MM-DD', 400);
        }
    }

    jsonResponse(['from' => $from, 'to' => $to, 'steps' => (new IntegrationRepository())->funnel($from, $to)]);
});
