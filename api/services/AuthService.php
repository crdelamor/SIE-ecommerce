<?php
declare(strict_types=1);

/**
 * Login y logout del administrador con sesión de PHP, y comprobación de
 * permisos (requireAdmin) para las rutas /admin/*.
 */
final class AuthService
{
    private const SESSION_NAME = 'sie_admin';

    // Hash de una cadena aleatoria: se comprueba cuando el email no existe para que la
    // respuesta tarde lo mismo y no se pueda averiguar qué emails están registrados.
    // Mismo coste (10) que el hash del administrador en seed.sql.
    private const DUMMY_HASH = '$2y$10$gZrxeMHMsh0NAUHM7DUZzeyQ92Z2fEp7A8l9LmqmuDb1NKgOq7wou';

    public function __construct(private UserRepository $users = new UserRepository())
    {
    }

    /**
     * Comprueba email y contraseña. Si son correctos abre la sesión y devuelve el
     * usuario (sin el hash); si no, devuelve null sin decir cuál de los dos falló.
     */
    public function login(string $email, string $password): ?array
    {
        $user = $this->users->findByEmail($email);
        $passwordOk = password_verify($password, $user['password_hash'] ?? self::DUMMY_HASH);

        if ($user === null || !$passwordOk || $user['role'] !== 'admin') {
            return null;
        }

        self::startSession();
        // Id de sesión nuevo al entrar: evita la fijación de sesión
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int) $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];

        return ['id' => (int) $user['id'], 'email' => $user['email'], 'role' => $user['role']];
    }

    /** Vacía la sesión, borra la cookie del navegador y destruye la sesión en el servidor. */
    public function logout(): void
    {
        self::startSession();
        $_SESSION = [];

        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'  => time() - 3600,
            'path'     => $params['path'],
            'secure'   => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'],
        ]);

        session_destroy();
    }

    /**
     * Guard de las rutas /admin/*: corta la petición con 401 si no hay sesión y con
     * 403 si el usuario no es administrador. Si todo va bien, devuelve el usuario.
     */
    public static function requireAdmin(): array
    {
        self::startSession();
        $user = [
            'id'    => $_SESSION['user_id'] ?? null,
            'email' => $_SESSION['email'] ?? null,
            'role'  => $_SESSION['role'] ?? null,
        ];
        // Solo se lee la sesión: se libera ya para no bloquear otras peticiones del mismo navegador
        session_write_close();

        if ($user['id'] === null) {
            jsonError('Tienes que iniciar sesión', 401);
        }
        if ($user['role'] !== 'admin') {
            jsonError('No tienes permiso para acceder a la administración', 403);
        }

        return $user;
    }

    /** Arranca la sesión con una cookie que JavaScript no puede leer y que no se envía desde otras webs. */
    public static function startSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // Solo acepta ids de sesión creados por el servidor
        ini_set('session.use_strict_mode', '1');
        session_name(self::SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,                       // hasta cerrar el navegador
            'path'     => '/',
            'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
            'httponly' => true,
            'samesite' => 'Strict',
        ]);
        session_start();
    }
}
