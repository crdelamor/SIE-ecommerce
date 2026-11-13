<?php
declare(strict_types=1);

/**
 * Punto de entrada único de la API del back-office. Todas las peticiones a
 * /bo-api/... llegan aquí (en Apache gracias a .htaccess).
 * No incluye nada de /api: la tienda es otro sistema.
 */

require __DIR__ . '/lib/http.php';
require __DIR__ . '/lib/db.php';
require __DIR__ . '/lib/auth.php';
require __DIR__ . '/lib/router.php';

date_default_timezone_set('Europe/Madrid');

// Cualquier error no controlado se devuelve como JSON 500, sin mostrar detalles internos
set_exception_handler(function (Throwable $e): void {
    error_log((string) $e);
    jsonError('Error interno del servidor', 500);
});

$router = new Router();

// Cada módulo registra sus rutas en su fichero de http/ (health.php, orders.php...)
foreach (glob(__DIR__ . '/http/*.php') as $routesFile) {
    require $routesFile;
}

// '/bo-api/orders/PED-000014?x=1'  ->  '/orders/PED-000014'
$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$prefix = strpos($path, '/bo-api');
if ($prefix !== false) {
    $path = substr($path, $prefix + strlen('/bo-api'));
}
$path = '/' . trim($path, '/');

// Todo pide X-Api-Key menos el health
if ($path !== '/health') {
    requireApiKey();
}

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $path);
