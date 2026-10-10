<?php
declare(strict_types=1);

/**
 * Rutas de administración (C). Todas las /admin/* llaman primero a requireAdmin().
 *
 *   POST  /auth/login
 *   POST  /auth/logout
 *   GET   /admin/orders?status=
 *   GET   /admin/orders/{id}
 *   PATCH /admin/orders/{id}/status
 *   GET   /admin/support
 */

// index.php solo carga lib/ y http/: los servicios y repositorios se cargan aquí
require_once __DIR__ . '/../repositories/UserRepository.php';
require_once __DIR__ . '/../repositories/AdminOrderRepository.php';
require_once __DIR__ . '/../repositories/SupportTicketRepository.php';
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/OrderStatusService.php';
require_once __DIR__ . '/../services/SupportService.php';

/** Lee el cuerpo JSON de la petición; responde 400 si no es un objeto JSON válido. */
function readJsonBody(): array
{
    $data = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($data)) {
        jsonError('El cuerpo de la petición debe ser un JSON válido', 400);
    }

    return $data;
}

// Body: { "email": "...", "password": "..." }  ->  200 { "user": { id, email, role } }
$router->post('/auth/login', function (): void {
    $body = readJsonBody();
    $email = is_string($body['email'] ?? null) ? trim($body['email']) : '';
    $password = is_string($body['password'] ?? null) ? $body['password'] : '';

    if ($email === '' || $password === '') {
        jsonError('El email y la contraseña son obligatorios', 400);
    }

    $user = (new AuthService())->login($email, $password);
    if ($user === null) {
        // Mismo mensaje si falla el email o la contraseña
        jsonError('Email o contraseña incorrectos', 401);
    }

    jsonResponse(['user' => $user]);
});

$router->post('/auth/logout', function (): void {
    (new AuthService())->logout();
    jsonResponse(['status' => 'ok']);
});

// Parámetros opcionales: ?status=pagado&from=2026-09-01&to=2026-09-30&q=PED-0001&page=2
// -> 200 { "orders": [ { id, code, created_at, customer_name, total, status } ], "total", "page", "per_page" }
$router->get('/admin/orders', function (): void {
    AuthService::requireAdmin();

    $filters = [
        'status' => queryParam('status'),
        'from'   => queryDate('from'),
        'to'     => queryDate('to'),
        'q'      => queryParam('q'),
    ];

    if ($filters['status'] !== null && !OrderStatusService::isValidStatus($filters['status'])) {
        jsonError('Estado no válido', 400);
    }
    // Las dos fechas tienen el mismo formato, así que se pueden comparar como texto
    if ($filters['from'] !== null && $filters['to'] !== null && $filters['from'] > $filters['to']) {
        jsonError("La fecha 'from' no puede ser posterior a 'to'", 400);
    }
    if ($filters['q'] !== null && mb_strlen($filters['q']) > 100) {
        jsonError('La búsqueda no puede tener más de 100 caracteres', 400);
    }

    $page = queryPage();
    $result = (new AdminOrderRepository())->findPage(
        array_filter($filters, fn ($value) => $value !== null),
        ADMIN_PER_PAGE,
        ($page - 1) * ADMIN_PER_PAGE
    );

    jsonResponse($result + ['page' => $page, 'per_page' => ADMIN_PER_PAGE]);
});

/** Id del pedido de la ruta como entero positivo; responde 400 si no lo es. */
function routeOrderId(array $params): int
{
    if (!ctype_digit($params['id']) || (int) $params['id'] < 1) {
        jsonError('Identificador de pedido no válido', 400);
    }

    return (int) $params['id'];
}

/** Responde con la ficha completa del pedido y las transiciones que permite su estado. */
function respondWithOrder(int $id): void
{
    $order = (new AdminOrderRepository())->findById($id);
    if ($order === null) {
        jsonError('Pedido no encontrado', 404);
    }

    // Lo decide el servidor: la pantalla solo enseña botones para estas transiciones
    $order['allowed_transitions'] = OrderStatusService::allowedTransitions($order['status']);

    jsonResponse(['order' => $order]);
}

// -> 200 { "order": { ..., customer, items, payments, history, allowed_transitions } } · 404 si no existe
$router->get('/admin/orders/{id}', function (array $params): void {
    AuthService::requireAdmin();
    respondWithOrder(routeOrderId($params));
});

// Body: { "status": "enviado" }  ->  200 { "order": { ... } } con el estado ya cambiado
// 400 body no válido · 404 no existe · 409 transición no permitida · 422 estado inexistente
$router->patch('/admin/orders/{id}/status', function (array $params): void {
    $admin = AuthService::requireAdmin();
    $id = routeOrderId($params);

    $body = readJsonBody();
    $status = $body['status'] ?? null;
    if (!is_string($status) || trim($status) === '') {
        jsonError("Falta el nuevo estado ('status')", 400);
    }

    try {
        (new OrderStatusService())->changeStatus($id, trim($status), (int) $admin['id']);
    } catch (OrderStatusException $e) {
        jsonError($e->getMessage(), $e->getCode());
    }

    respondWithOrder($id);
});

// ?status=abierta&page=2 (opcionales)
// -> 200 { "tickets": [ { id, created_at, name, email, subject, message, status, order: { id, code } | null } ], "total", "page", "per_page" }
$router->get('/admin/support', function (): void {
    AuthService::requireAdmin();

    $status = queryParam('status');
    if ($status !== null && !in_array($status, SupportService::TICKET_STATUSES, true)) {
        jsonError('Estado no válido', 400);
    }

    $page = queryPage();
    $result = (new SupportTicketRepository())->findPage($status, ADMIN_PER_PAGE, ($page - 1) * ADMIN_PER_PAGE);

    jsonResponse($result + ['page' => $page, 'per_page' => ADMIN_PER_PAGE]);
});
