<?php
declare(strict_types=1);

/**
 * Sesión del gestor (login de las pantallas con bo_users).
 * La usan las páginas de public/ y, en la API, GET /bo-api/metrics.
 */

const SESSION_IDLE_SECONDS = 2 * 60 * 60;

// Hash de una contraseña aleatoria: si el email no existe se comprueba contra este,
// para que la respuesta tarde lo mismo y no delate qué emails son de gestores
const DUMMY_PASSWORD_HASH = '$2y$10$7flwf/esJ9h0F1ebhLUbeeAO0VosYUYF9XYJyJMdUh4dYSpkidaAy';

function startManagerSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    // Detrás de Caddy la petición llega por http: el proxy avisa con X-Forwarded-Proto
    $https = ($_SERVER['HTTPS'] ?? '') === 'on' || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';

    session_name('bo_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** El gestor con sesión iniciada, o null. La sesión caduca tras 2 horas sin actividad. */
function currentManager(): ?array
{
    startManagerSession();

    $manager = $_SESSION['manager'] ?? null;
    if (!is_array($manager)) {
        return null;
    }
    if (time() - (int) ($_SESSION['last_seen'] ?? 0) > SESSION_IDLE_SECONDS) {
        logoutManager();
        return null;
    }
    $_SESSION['last_seen'] = time();

    return $manager;
}

function loginManager(string $email, string $password): bool
{
    $stmt = db()->prepare('SELECT id, email, password_hash FROM bo_users WHERE email = ?');
    $stmt->execute([trim($email)]);
    $user = $stmt->fetch() ?: null;

    $valid = password_verify($password, $user['password_hash'] ?? DUMMY_PASSWORD_HASH);
    if ($user === null || !$valid) {
        return false;
    }

    startManagerSession();
    session_regenerate_id(true);
    $_SESSION['manager'] = ['id' => (int) $user['id'], 'email' => $user['email']];
    $_SESSION['last_seen'] = time();

    return true;
}

function logoutManager(): void
{
    startManagerSession();
    $_SESSION = [];
    session_destroy();
    setcookie(session_name(), '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
}

/** Token de los formularios (login y salir), guardado en la sesión. */
function csrfToken(): string
{
    startManagerSession();

    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function isValidCsrf(mixed $token): bool
{
    return is_string($token) && hash_equals(csrfToken(), $token);
}
