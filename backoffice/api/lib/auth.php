<?php
declare(strict_types=1);

/**
 * Autenticación de la API: n8n manda la clave en la cabecera X-Api-Key.
 * El cuadro de mando también acepta la sesión del gestor (lib/session.php).
 */

function requireApiKey(): void
{
    $expected = (string) (config()['api_key'] ?? '');
    $received = (string) ($_SERVER['HTTP_X_API_KEY'] ?? '');

    // Sin clave configurada no se abre la API
    if ($expected === '' || !hash_equals($expected, $received)) {
        jsonError('Falta la cabecera X-Api-Key o no es válida', 401);
    }
}

/** Clave de la API o, si no viene la cabecera, la sesión del gestor (cuadro de mando). */
function requireApiKeyOrManager(): void
{
    if (isset($_SERVER['HTTP_X_API_KEY']) || currentManager() === null) {
        requireApiKey();
    }
}
