<?php
declare(strict_types=1);

/**
 * Conexión PDO a MySQL compartida por toda la petición.
 * Usad siempre consultas preparadas: db()->prepare(...) + execute([...]).
 */
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $configFile = __DIR__ . '/../config.php';
        if (!is_file($configFile)) {
            throw new RuntimeException('Falta api/config.php: copia config.example.php y rellénalo.');
        }
        $c = (require $configFile)['db'];

        $pdo = new PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4",
            $c['user'],
            $c['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }

    return $pdo;
}
