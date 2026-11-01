<?php
/**
 * Plantilla de configuración.
 * Cópiala como api/config.php (está en el .gitignore) y rellena los datos de MySQL.
 * Nunca subas config.php a GitHub ni lo pegues en una IA.
 */
return [
    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'name'     => 'sie_ecommerce',
        'user'     => 'root',
        'password' => '',
    ],
    // Correo de confirmación del pedido (SMTP de una cuenta del dominio).
    // Con 'enabled' => false la tienda funciona igual y no se envía nada.
    'mail' => [
        'enabled'   => false,
        'host'      => 'smtp.tudominio.com',
        'port'      => 587,
        'secure'    => 'tls',            // STARTTLS, como pide DonDominio
        'user'      => 'pedidos@tudominio.com',
        'password'  => '',
        'from'      => 'pedidos@tudominio.com',
        'from_name' => 'The Black Lotus TCG',
        'site_url'  => 'https://www.tudominio.com',   // para el enlace a la confirmación
    ],
    // Integración con n8n (Tarea 2). Sin 'webhook_url' no se envía nada y la tienda funciona igual.
    'integration' => [
        'webhook_url'    => '',   // URL de producción del nodo Webhook de n8n (https://...)
        'webhook_secret' => '',   // va en la cabecera X-Webhook-Secret; el mismo valor en n8n
        'api_key'        => '',   // la que manda n8n en X-Api-Key a /api/integration/*
    ],
];
