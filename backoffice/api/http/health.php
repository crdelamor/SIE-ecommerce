<?php
declare(strict_types=1);

// GET /bo-api/health - comprueba el despliegue y la base de datos. Sin clave ni datos sensibles.
$router->get('/health', function (): void {
    try {
        db()->query('SELECT 1');
        $database = 'ok';
    } catch (Throwable $e) {
        error_log((string) $e);
        $database = 'error';
    }

    jsonResponse([
        'status'   => $database === 'ok' ? 'ok' : 'error',
        'database' => $database,
        'time'     => date(DATE_ATOM),
    ], $database === 'ok' ? 200 : 503);
});
