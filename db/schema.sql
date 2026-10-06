-- ============================================================
-- schema.sql - Modelo de datos del eCommerce (tienda de cartas coleccionables)
-- Asignatura: Soluciones Informáticas para la Empresa (SIE)
-- Práctica 1
-- Autor: José Gómez Reinaldos (alumno)
-- Nombres de tablas y columnas en inglés (contrato del plan, apartado 2.1)
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Borramos tablas para poder reimportar el fichero
DROP TABLE IF EXISTS events;
DROP TABLE IF EXISTS support_tickets;
DROP TABLE IF EXISTS order_status_history;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS customers;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- DATOS MAESTROS
-- ------------------------------------------------------------

-- Cada juego es una categoría: Magic, Riftbound, Pokémon, Yu-Gi-Oh!
CREATE TABLE categories (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(100) NOT NULL,
    slug  VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_slug (slug)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La misma carta en distinto estado, idioma o foil es un producto distinto
-- (cada uno con su precio y su stock), así carrito y pedidos no cambian.
CREATE TABLE products (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id     INT UNSIGNED NOT NULL,
    name            VARCHAR(150) NOT NULL,
    description     TEXT NULL,
    price           DECIMAL(10,2) NOT NULL,               -- sin IVA
    tax_rate        DECIMAL(4,2)  NOT NULL DEFAULT 21.00, -- 21.00, 10.00 o 4.00
    stock           INT UNSIGNED  NOT NULL DEFAULT 0,
    image_url       VARCHAR(255) NULL,
    active          TINYINT(1) NOT NULL DEFAULT 1,
    product_type    ENUM('single','booster') NOT NULL DEFAULT 'single', -- carta suelta / sobre
    set_name        VARCHAR(100) NULL,                    -- edición o set
    card_condition  ENUM('NM','LP','MP') NULL,            -- NULL en sobres (van precintados)
    language        VARCHAR(30) NULL,                     -- Español, Inglés, Japonés...
    illustrator     VARCHAR(100) NULL,                    -- NULL en sobres
    is_foil         TINYINT(1) NOT NULL DEFAULT 0,        -- 0 en sobres
    PRIMARY KEY (id),
    KEY idx_products_category (category_id),
    KEY idx_products_active (active),
    KEY idx_products_type (product_type),
    KEY idx_products_set (set_name),
    CONSTRAINT fk_products_category
        FOREIGN KEY (category_id) REFERENCES categories (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_products_price    CHECK (price >= 0),
    CONSTRAINT chk_products_tax_rate CHECK (tax_rate IN (21.00, 10.00, 4.00)),
    -- Un sobre no tiene estado de conservación ni foil
    CONSTRAINT chk_products_booster  CHECK (product_type = 'single' OR (card_condition IS NULL AND is_foil = 0))
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE customers (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(120) NOT NULL,
    email        VARCHAR(150) NOT NULL,
    phone        VARCHAR(30) NULL,
    address      VARCHAR(200) NOT NULL,
    city         VARCHAR(100) NULL,
    postal_code  CHAR(5) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_customers_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE users (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email          VARCHAR(150) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,                 -- password_hash() de PHP
    role           ENUM('admin') NOT NULL DEFAULT 'admin',
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- DATOS TRANSACCIONALES
-- ------------------------------------------------------------

CREATE TABLE orders (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         VARCHAR(20) NOT NULL,                    -- PED-000123
    customer_id  INT UNSIGNED NOT NULL,
    status       ENUM('pendiente_pago','pagado','en_preparacion',
                      'enviado','entregado','cancelado','incidencia')
                 NOT NULL DEFAULT 'pendiente_pago',
    subtotal     DECIMAL(10,2) NOT NULL,                  -- sin IVA, antes de descuento
    discount     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    shipping     DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    tax          DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    total        DECIMAL(10,2) NOT NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_orders_code (code),
    KEY idx_orders_customer (customer_id),
    KEY idx_orders_status (status),
    KEY idx_orders_created (created_at),
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Se guarda copia de nombre, precio e IVA: el pedido no cambia si el producto se edita
CREATE TABLE order_items (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id      INT UNSIGNED NOT NULL,
    product_id    INT UNSIGNED NOT NULL,
    product_name  VARCHAR(150) NOT NULL,
    unit_price    DECIMAL(10,2) NOT NULL,
    tax_rate      DECIMAL(4,2)  NOT NULL,
    quantity      INT UNSIGNED  NOT NULL,
    line_total    DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_order_items_order (order_id),
    KEY idx_order_items_product (product_id),
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product
        FOREIGN KEY (product_id) REFERENCES products (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_order_items_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE payments (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id    INT UNSIGNED NOT NULL,
    method      VARCHAR(30) NOT NULL,                     -- tarjeta_simulada, bizum_simulado
    status      ENUM('approved','rejected') NOT NULL,
    amount      DECIMAL(10,2) NOT NULL,
    reference   VARCHAR(50) NULL,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_payments_order (order_id),
    CONSTRAINT fk_payments_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE order_status_history (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id    INT UNSIGNED NOT NULL,
    from_status VARCHAR(30) NULL,                         -- NULL en el alta del pedido
    to_status   VARCHAR(30) NOT NULL,
    changed_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_status_history_order (order_id),
    CONSTRAINT fk_status_history_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- SOPORTE Y EVENTOS
-- ------------------------------------------------------------

CREATE TABLE support_tickets (
    id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id    INT UNSIGNED NULL,                        -- opcional
    name        VARCHAR(120) NOT NULL,
    email       VARCHAR(150) NOT NULL,
    subject     VARCHAR(200) NOT NULL,
    message     TEXT NOT NULL,
    status      ENUM('abierta','en_curso','cerrada') NOT NULL DEFAULT 'abierta',
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_tickets_order (order_id),
    KEY idx_tickets_status (status),
    CONSTRAINT fk_tickets_order
        FOREIGN KEY (order_id) REFERENCES orders (id)
        ON UPDATE CASCADE ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- type: product.viewed, cart.item_added, checkout.started, order.created,
--       payment.simulated, support.requested ...

CREATE TABLE events (
    id           BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    type         VARCHAR(50) NOT NULL,
    entity_type  VARCHAR(30) NULL,                        -- product, order, ticket...
    entity_id    INT UNSIGNED NULL,
    payload      JSON NULL,
    session_id   VARCHAR(64) NULL,
    created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_events_type (type),
    KEY idx_events_entity (entity_type, entity_id),
    KEY idx_events_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Fin del esquema
-- ------------------------------------------------------------
