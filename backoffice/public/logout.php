<?php
declare(strict_types=1);

require __DIR__ . '/../api/lib/page.php';

// Solo por POST y con el token del formulario: un enlace de otra web no cierra la sesión
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isValidCsrf($_POST['csrf'] ?? null)) {
    logoutManager();
}
header('Location: login.php');
exit;
