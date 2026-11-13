<?php
/**
 * Plantilla de configuración del back-office.
 * Cópiala como backoffice/config.php (está en el .gitignore).
 * Las claves se leen de variables de entorno: en la nube se ponen en el panel
 * de la plataforma y en local se pueden escribir aquí los valores por defecto.
 * Nunca subas config.php a GitHub ni lo pegues en una IA.
 */
return [
    'db' => [
        'host'     => getenv('BO_DB_HOST') ?: 'localhost',
        'port'     => (int) (getenv('BO_DB_PORT') ?: 3306),
        'name'     => getenv('BO_DB_NAME') ?: 'blacklotus_bo',
        'user'     => getenv('BO_DB_USER') ?: 'blacklotus_bo',
        'password' => getenv('BO_DB_PASSWORD') ?: '',
    ],
    // Clave que manda n8n en la cabecera X-Api-Key. Distinta de la de la tienda.
    'api_key' => getenv('BO_API_KEY') ?: '',
];
