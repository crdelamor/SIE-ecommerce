-- ============================================================
-- schema.sql - Modelo de datos del eCommerce
-- Asignatura: Soluciones Informáticas para la Empresa (SIE)
-- Práctica 1
-- Autor: José Gómez Reinaldos (alumno)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Borramos tablas para poder reimportar el fichero
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS tickets_soporte;
DROP TABLE IF EXISTS historial_estados_pedido;
DROP TABLE IF EXISTS pagos;
DROP TABLE IF EXISTS lineas_pedido;
DROP TABLE IF EXISTS pedidos;
DROP TABLE IF EXISTS usuarios;
DROP TABLE IF EXISTS clientes;
DROP TABLE IF EXISTS productos;
DROP TABLE IF EXISTS categorias;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- DATOS MAESTROS
-- ------------------------------------------------------------

CREATE TABLE categorias (
    id      INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre  VARCHAR(100) NOT NULL,
    slug    VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categorias_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE productos (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    categoria_id  INT UNSIGNED NOT NULL,
    nombre        VARCHAR(150) NOT NULL,
    descripcion   TEXT NULL,
    precio        DECIMAL(10,2) NOT NULL,               -- sin IVA
    tasa_iva      DECIMAL(4,2)  NOT NULL DEFAULT 21.00, -- 21.00, 10.00 o 4.00
    stock         INT UNSIGNED  NOT NULL DEFAULT 0,
    imagen_url    VARCHAR(255) NULL,
    activo        TINYINT(1) NOT NULL DEFAULT 1,
    PRIMARY KEY (id),
    KEY idx_productos_categoria (categoria_id),
    KEY idx_productos_activo (activo),
    CONSTRAINT fk_productos_categoria
        FOREIGN KEY (categoria_id) REFERENCES categorias (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_productos_precio   CHECK (precio >= 0),
    CONSTRAINT chk_productos_tasa_iva CHECK (tasa_iva IN (21.00, 10.00, 4.00))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE clientes (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    nombre         VARCHAR(120) NOT NULL,
    email          VARCHAR(150) NOT NULL,
    telefono       VARCHAR(30) NULL,
    direccion      VARCHAR(200) NOT NULL,
    ciudad         VARCHAR(100) NULL,
    codigo_postal  CHAR(5) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_clientes_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email            VARCHAR(150) NOT NULL,
    hash_contrasena  VARCHAR(255) NOT NULL,             -- password_hash() de PHP
    rol              ENUM('admin') NOT NULL DEFAULT 'admin',
    PRIMARY KEY (id),
    UNIQUE KEY uq_usuarios_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- DATOS TRANSACCIONALES
-- ------------------------------------------------------------

CREATE TABLE pedidos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    codigo      VARCHAR(20) NOT NULL,                   -- PED-000123
    cliente_id  INT UNSIGNED NOT NULL,
    estado      ENUM('pendiente_pago','pagado','en_preparacion',
                     'enviado','entregado','cancelado','incidencia')
                NOT NULL DEFAULT 'pendiente_pago',
    subtotal    DECIMAL(10,2) NOT NULL,                 -- sin IVA, antes de descuento
    descuento   DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    envio       DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    iva         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total       DECIMAL(10,2) NOT NULL,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pedidos_codigo (codigo),
    KEY idx_pedidos_cliente (cliente_id),
    KEY idx_pedidos_estado (estado),
    KEY idx_pedidos_creado (creado_en),
    CONSTRAINT fk_pedidos_cliente
        FOREIGN KEY (cliente_id) REFERENCES clientes (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Se guarda copia de nombre, precio e IVA: el pedido no cambia si el producto se edita
CREATE TABLE lineas_pedido (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id        INT UNSIGNED NOT NULL,
    producto_id      INT UNSIGNED NOT NULL,
    nombre_producto  VARCHAR(150) NOT NULL,
    precio_unitario  DECIMAL(10,2) NOT NULL,
    tasa_iva         DECIMAL(4,2)  NOT NULL,
    cantidad         INT UNSIGNED  NOT NULL,
    total_linea      DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_lineas_pedido (pedido_id),
    KEY idx_lineas_producto (producto_id),
    CONSTRAINT fk_lineas_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_lineas_producto
        FOREIGN KEY (producto_id) REFERENCES productos (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_lineas_cantidad CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE pagos (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id   INT UNSIGNED NOT NULL,
    metodo      VARCHAR(30) NOT NULL,                   -- tarjeta_simulada, bizum_simulado
    estado      ENUM('aprobado','rechazado') NOT NULL,
    importe     DECIMAL(10,2) NOT NULL,
    referencia  VARCHAR(50) NULL,
    creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_pagos_pedido (pedido_id),
    CONSTRAINT fk_pagos_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE historial_estados_pedido (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id        INT UNSIGNED NOT NULL,
    estado_origen    VARCHAR(30) NULL,                  -- NULL en el alta del pedido
    estado_destino   VARCHAR(30) NOT NULL,
    cambiado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_historial_pedido (pedido_id),
    CONSTRAINT fk_historial_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- SOPORTE Y EVENTOS
-- ------------------------------------------------------------

CREATE TABLE tickets_soporte (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    pedido_id  INT UNSIGNED NULL,                       -- opcional
    nombre     VARCHAR(120) NOT NULL,
    email      VARCHAR(150) NOT NULL,
    asunto     VARCHAR(200) NOT NULL,
    mensaje    TEXT NOT NULL,
    estado     ENUM('abierta','en_curso','cerrada') NOT NULL DEFAULT 'abierta',
    creado_en  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tickets_pedido (pedido_id),
    KEY idx_tickets_estado (estado),
    CONSTRAINT fk_tickets_pedido
        FOREIGN KEY (pedido_id) REFERENCES pedidos (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- tipo: product.viewed, cart.item_added, checkout.started, order.created,
--       payment.simulated, support.requested ...

CREATE TABLE eventos (
    id            BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    tipo          VARCHAR(50) NOT NULL,
    tipo_entidad  VARCHAR(30) NULL,                     -- producto, pedido, ticket...
    entidad_id    INT UNSIGNED NULL,
    datos         JSON NULL,
    sesion_id     VARCHAR(64) NULL,
    creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_eventos_tipo (tipo),
    KEY idx_eventos_entidad (tipo_entidad, entidad_id),
    KEY idx_eventos_creado (creado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Fin del esquema - 
-- ------------------------------------------------------------
