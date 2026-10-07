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

/** Parámetro de la URL como texto; null si no viene o está vacío. Un array (?x[]=) es un 400. */
function queryParam(string $name): ?string
{
    $value = $_GET[$name] ?? null;
    if ($value !== null && !is_string($value)) {
        jsonError("El parámetro '$name' no es válido", 400);
    }
    $value = $value === null ? null : trim($value);

    return $value === '' ? null : $value;
}

/** Fecha 'AAAA-MM-DD' real (rechaza '2026-02-30' o '30/09/2026'); responde 400 si no lo es. */
function queryDate(string $name): ?string
{
    $value = queryParam($name);
    if ($value === null) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if ($date === false || $date->format('Y-m-d') !== $value) {
        jsonError("La fecha '$name' debe tener el formato AAAA-MM-DD", 400);
    }

    return $value;
}

const ORDERS_PER_PAGE = 20;

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

    $page = queryParam('page') ?? '1';
    if (!ctype_digit($page) || (int) $page < 1 || (int) $page > 100000) {
        jsonError('Página no válida', 400);
    }
    $page = (int) $page;

    $result = (new AdminOrderRepository())->findPage(
        array_filter($filters, fn ($value) => $value !== null),
        ORDERS_PER_PAGE,
        ($page - 1) * ORDERS_PER_PAGE
    );

    jsonResponse($result + ['page' => $page, 'per_page' => ORDERS_PER_PAGE]);
});
