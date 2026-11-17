<?php
declare(strict_types=1);

require __DIR__ . '/../api/lib/page.php';

if (currentManager() !== null) {
    header('Location: ./');
    exit;
}

$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = is_string($_POST['email'] ?? null) ? $_POST['email'] : '';
    $password = is_string($_POST['password'] ?? null) ? $_POST['password'] : '';

    if (!isValidCsrf($_POST['csrf'] ?? null)) {
        $error = 'El formulario ha caducado. Vuelve a intentarlo.';
    } elseif (loginManager($email, $password)) {
        header('Location: ./');
        exit;
    } else {
        $error = 'El correo o la contraseña no son correctos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Entrar · Back-office The Black Lotus TCG</title>
    <link rel="stylesheet" href="assets/cuadro-mando.css">
</head>
<body class="login">
    <p class="prototype">Prototipo académico - sin actividad comercial real</p>
    <main class="login-box">
        <h1>Back-office</h1>
        <p class="muted">The Black Lotus TCG · acceso del gestor</p>
        <?php if ($error !== ''): ?>
            <p class="form-error" role="alert"><?= e($error) ?></p>
        <?php endif; ?>
        <form method="post" action="login.php">
            <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
            <label for="email">Correo</label>
            <input id="email" name="email" type="email" autocomplete="username" required value="<?= e($email) ?>">
            <label for="password">Contraseña</label>
            <input id="password" name="password" type="password" autocomplete="current-password" required>
            <button type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>
