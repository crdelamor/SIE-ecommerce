<?php
declare(strict_types=1);

// Rutas del flujo de compra (A)

require_once __DIR__ . '/../repositories/OrderRepository.php';
require_once __DIR__ . '/../services/PriceCalculator.php';
require_once __DIR__ . '/../services/OrderService.php';
require_once __DIR__ . '/../services/PaymentService.php';

// Lee el carrito del body: [{ "product_id": 3, "quantity": 2 }, ...] -> [3 => 2, ...]
function readCartItems(array $body): array
{
    $items = $body['items'] ?? null;
    if (!is_array($items) || count($items) === 0 || count($items) > 50) {
        jsonError('El carrito está vacío o no es válido', 400);
    }

    $cart = [];
    foreach ($items as $item) {
        $id = $item['product_id'] ?? null;
        $quantity = $item['quantity'] ?? null;
        if (!is_int($id) || $id < 1 || !is_int($quantity) || $quantity < 1 || $quantity > 99) {
            jsonError('Hay un producto del carrito que no es válido', 400);
        }
        $cart[$id] = ($cart[$id] ?? 0) + $quantity;
    }
    return $cart;
}

// Cupón opcional: null si no viene o está vacío
function readCoupon(array $body): ?string
{
    $coupon = $body['coupon'] ?? null;
    if ($coupon !== null && !is_string($coupon)) {
        jsonError('El cupón no es válido', 400);
    }
    $coupon = $coupon === null ? '' : trim($coupon);
    return $coupon === '' ? null : $coupon;
}

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

// POST /checkout/quote  { "items": [{ "product_id": 3, "quantity": 2 }], "coupon": "BIENVENIDA10" }
$router->post('/checkout/quote', function (): void {
    $body = readJsonBody();
    $cart = readCartItems($body);
    $coupon = readCoupon($body);

    try {
        jsonResponse((new PriceCalculator())->quote($cart, $coupon));
    } catch (CheckoutException $e) {
        jsonError($e->getMessage(), $e->getCode());
    }
});

// POST /orders  { "items": [...], "coupon": "...", "customer": { "name", "email", "phone", "address", "city", "postal_code" },
//                 "billing": null o { "name", "address", "city", "postal_code" } si se factura a otra dirección }
$router->post('/orders', function (): void {
    $body = readJsonBody();
    $cart = readCartItems($body);
    $coupon = readCoupon($body);
    $customer = is_array($body['customer'] ?? null) ? $body['customer'] : [];
    $billing = is_array($body['billing'] ?? null) ? $body['billing'] : null;

    try {
        $order = (new OrderService())->create($cart, $coupon, $customer, $billing);
    } catch (CheckoutException $e) {
        jsonError($e->getMessage(), $e->getCode());
    }
    jsonResponse(['order' => $order], 201);
});

// POST /orders/PED-000123/pay  { "method": "tarjeta_simulada", "card_number": "4242 4242 4242 4242" }
//                              { "method": "bizum_simulado", "phone": "600111222" }
// Si se rechaza responde 200 con "approved": false y el pedido sigue en pendiente_pago
$router->post('/orders/{code}/pay', function (array $params): void {
    $body = readJsonBody();
    $method = is_string($body['method'] ?? null) ? $body['method'] : '';

    try {
        $payment = (new PaymentService())->pay($params['code'], $method, $body);
    } catch (CheckoutException $e) {
        jsonError($e->getMessage(), $e->getCode());
    }
    jsonResponse(['payment' => $payment]);
});

// GET /orders/PED-000123  ->  datos para la página de confirmación (sin datos personales)
$router->get('/orders/{code}', function (array $params): void {
    $order = (new OrderRepository())->findSummary($params['code']);
    if ($order === null) {
        jsonError('El pedido no existe', 404);
    }
    jsonResponse(['order' => $order]);
});
