<?php
declare(strict_types=1);

/**
 * Configuración y conexión PDO a la base de datos del back-office.
 * Usad siempre consultas preparadas: db()->prepare(...) + execute([...]).
 */

function config(): array
{
    static $config = null;

    if ($config === null) {
        $configFile = __DIR__ . '/../../config.php';
        if (!is_file($configFile)) {
            throw new RuntimeException('Falta backoffice/config.php: copia config.example.php.');
        }
        $config = require $configFile;
    }

    return $config;
}

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $c = config()['db'];

        $pdo = new PDO(
            "mysql:host={$c['host']};port={$c['port']};dbname={$c['name']};charset=utf8mb4",
            $c['user'],
            $c['password'],
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                // NOW() y DEFAULT CURRENT_TIMESTAMP en hora de Madrid, como las fechas que llegan de la tienda.
                // El MySQL del servidor va en UTC. date('P') usa la zona de index.php (cambia sola en verano).
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET time_zone = '" . date('P') . "'",
            ]
        );
    }

    return $pdo;
}

/** Error 1062 de MySQL: se ha saltado un UNIQUE (dos peticiones a la vez con el mismo dato). */
function isDuplicateKey(PDOException $e): bool
{
    return ($e->errorInfo[1] ?? null) === 1062;
}
