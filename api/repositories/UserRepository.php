<?php
declare(strict_types=1);

/**
 * Acceso a la tabla users (búsqueda del administrador por email).
 */
final class UserRepository
{
    /** Devuelve el usuario con su hash de contraseña, o null si el email no existe. */
    public function findByEmail(string $email): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, email, password_hash, role FROM users WHERE email = :email LIMIT 1'
        );
        $stmt->execute(['email' => $email]);

        return $stmt->fetch() ?: null;
    }
}
