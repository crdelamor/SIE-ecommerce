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
require_once __DIR__ . '/../services/AuthService.php';
require_once __DIR__ . '/../services/OrderStatusService.php';

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

// ?status=pagado (opcional)  ->  200 { "orders": [ { id, code, created_at, customer_name, total, status } ] }
$router->get('/admin/orders', function (): void {
    AuthService::requireAdmin();

    $status = $_GET['status'] ?? null;
    if ($status === '') {
        $status = null; // ?status= vacío = todos
    }
    if ($status !== null && (!is_string($status) || !OrderStatusService::isValidStatus($status))) {
        jsonError('Estado no válido', 400);
    }

    jsonResponse(['orders' => (new AdminOrderRepository())->findAll($status)]);
});
