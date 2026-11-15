<?php
declare(strict_types=1);

/**
 * Solo para local, con el servidor de PHP:  php -S localhost:8080 router.php  (desde backoffice/)
 * Hace lo mismo que Apache en el Dockerfile: /bo-api/... va a la API y el resto se sirve desde public/.
 */

$path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

if (str_starts_with($path, '/bo-api')) {
    require __DIR__ . '/api/index.php';
    return true;
}

$file = realpath(__DIR__ . '/public' . $path);
$public = realpath(__DIR__ . '/public');
if ($file !== false && str_starts_with($file, $public) && is_file($file)) {
    if (str_ends_with($file, '.php')) {
        require $file;
        return true;
    }
    return false; // estáticos (CSS, JS): los sirve el propio servidor de PHP
}

if ($path === '/') {
    require __DIR__ . '/public/index.php';
    return true;
}

http_response_code(404);
echo 'No encontrado';
return true;
