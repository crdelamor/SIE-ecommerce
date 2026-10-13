<?php
declare(strict_types=1);

/**
 * Punto de entrada único de la API. Todas las peticiones a /api/... llegan aquí
 * (en el hosting gracias a .htaccess).
 */

require __DIR__ . '/lib/response.php';
require __DIR__ . '/lib/request.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/events.php';
require __DIR__ . '/lib/router.php';
require __DIR__ . '/lib/query.php';

date_default_timezone_set('Europe/Madrid');

// Cualquier error no controlado se devuelve como JSON 500, sin mostrar detalles internos
set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    jsonError('Error interno del servidor', 500);
});

$router = new Router();

// "Hola mundo" para comprobar el despliegue: GET /api/health
$router->get('/health', function (): void {
    jsonResponse(['status' => 'ok']);
});

// Cada módulo registra sus rutas en su fichero de api/http/ (products.php, orders.php...)
foreach (glob(__DIR__ . '/http/*.php') as $routesFile) {
    require $routesFile;
}

// '/api/products/3?x=1'  ->  '/products/3'
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$basePath = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/');
if ($basePath !== '' && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}
$path = '/' . trim($path, '/');

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
