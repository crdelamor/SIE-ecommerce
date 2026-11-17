<?php
declare(strict_types=1);

/**
 * Arranque de las páginas de public/ (login y cuadro de mando).
 * Fuera de la raíz web; las páginas la cargan con require.
 */

require __DIR__ . '/db.php';
require __DIR__ . '/session.php';

// Antes de conectar: db.php fija la zona de la sesión de MySQL con date('P')
date_default_timezone_set('Europe/Madrid');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

/** Escapa un texto para HTML. */
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/** Redirige al login si no hay gestor con sesión. */
function requireManagerPage(): array
{
    $manager = currentManager();
    if ($manager === null) {
        header('Location: login.php');
        exit;
    }

    return $manager;
}
