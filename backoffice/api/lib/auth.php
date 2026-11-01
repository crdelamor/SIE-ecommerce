<?php
declare(strict_types=1);

/**
 * Autenticación de la API: n8n manda la clave en la cabecera X-Api-Key.
 * El login de las pantallas (sesión del gestor) se añade con las pantallas.
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
