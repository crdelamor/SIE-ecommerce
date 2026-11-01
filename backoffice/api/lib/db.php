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
            ]
        );
    }

    return $pdo;
}
