-- ============================================================
-- schema.sql - Modelo de datos del back-office
-- Asignatura: Soluciones Informáticas para la Empresa (SIE)
-- Práctica 2
-- Autor: José Gómez Reinaldos (alumno)
-- Tablas del plan v2, apartado 2.2. Base de datos propia (blacklotus_bo),
-- nunca la de la tienda.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Borramos tablas para poder reimportar el fichero
DROP TABLE IF EXISTS bo_integration_log;
DROP TABLE IF EXISTS bo_agent_proposals;
DROP TABLE IF EXISTS bo_tickets;
DROP TABLE IF EXISTS bo_order_status_log;
DROP TABLE IF EXISTS bo_order_lines;
DROP TABLE IF EXISTS bo_orders;
DROP TABLE IF EXISTS bo_customers;
DROP TABLE IF EXISTS bo_users;

SET FOREIGN_KEY_CHECKS = 1;

-- ------------------------------------------------------------
-- USUARIOS Y CLIENTES
-- ------------------------------------------------------------

CREATE TABLE bo_users (
    id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email          VARCHAR(150) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,                 -- password_hash() de PHP
    role           ENUM('gestor') NOT NULL DEFAULT 'gestor',
    PRIMARY KEY (id),
    UNIQUE KEY uq_bo_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Solo lo necesario: sin dirección ni teléfono. El email identifica al cliente
-- entre pedidos y tickets, porque no guardamos el id de la tienda.
CREATE TABLE bo_customers (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name              VARCHAR(120) NOT NULL,
    email             VARCHAR(150) NOT NULL,
    telegram_chat_id  VARCHAR(32) NULL,                   -- lo rellena el flujo "Telegram"
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bo_customers_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- PEDIDOS
-- ------------------------------------------------------------

CREATE TABLE bo_orders (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    code         VARCHAR(20) NOT NULL,                    -- PED-000014, el de la tienda
    customer_id  INT UNSIGNED NOT NULL,
    status       ENUM('pendiente_pago','pagado','en_preparacion',
                      'enviado','entregado','cancelado','incidencia') NOT NULL,
    total        DECIMAL(10,2) NOT NULL,
    placed_at    DATETIME NOT NULL,                       -- fecha del pedido en la tienda
    synced_at    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bo_orders_code (code),                  -- idempotencia del alta
    KEY idx_bo_orders_customer (customer_id),
    KEY idx_bo_orders_status (status),
    KEY idx_bo_orders_placed (placed_at),
    CONSTRAINT fk_bo_orders_customer
        FOREIGN KEY (customer_id) REFERENCES bo_customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT chk_bo_orders_total CHECK (total >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE bo_order_lines (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id        INT UNSIGNED NOT NULL,
    product_name    VARCHAR(150) NOT NULL,
    game            VARCHAR(50) NOT NULL,                 -- Magic: The Gathering, Pokémon TCG...
    set_name        VARCHAR(100) NULL,
    card_condition  ENUM('NM','LP','MP') NULL,            -- NULL en sobres
    language        VARCHAR(30) NULL,
    is_foil         TINYINT(1) NOT NULL DEFAULT 0,
    quantity        INT UNSIGNED NOT NULL,
    unit_price      DECIMAL(10,2) NOT NULL,
    line_total      DECIMAL(10,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_bo_order_lines_order (order_id),
    KEY idx_bo_order_lines_game (game),
    CONSTRAINT fk_bo_order_lines_order
        FOREIGN KEY (order_id) REFERENCES bo_orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_bo_order_lines_quantity CHECK (quantity > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Un mismo event_id no se aplica dos veces al mismo pedido (reintentos de n8n)
CREATE TABLE bo_order_status_log (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    order_id     INT UNSIGNED NOT NULL,
    from_status  VARCHAR(30) NULL,                        -- NULL en el alta del pedido
    to_status    VARCHAR(30) NOT NULL,
    changed_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    event_id     BIGINT UNSIGNED NULL,                    -- id de events en la tienda
    PRIMARY KEY (id),
    UNIQUE KEY uq_bo_status_log_event (order_id, event_id),
    CONSTRAINT fk_bo_status_log_order
        FOREIGN KEY (order_id) REFERENCES bo_orders (id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- INCIDENCIAS Y AGENTE
-- ------------------------------------------------------------

-- order_code sin clave foránea: la incidencia puede llegar antes que el pedido
CREATE TABLE bo_tickets (
    id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
    shop_ticket_id  INT UNSIGNED NOT NULL,                -- id de support_tickets en la tienda
    customer_id     INT UNSIGNED NOT NULL,
    order_code      VARCHAR(20) NULL,
    reason          VARCHAR(200) NOT NULL,
    status          ENUM('abierta','en_curso','cerrada') NOT NULL DEFAULT 'abierta',
    priority        ENUM('baja','media','alta') NOT NULL DEFAULT 'media',
    opened_at       DATETIME NOT NULL,
    closed_at       DATETIME NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_bo_tickets_shop (shop_ticket_id),       -- idempotencia del alta
    KEY idx_bo_tickets_customer (customer_id),
    KEY idx_bo_tickets_status (status),
    CONSTRAINT fk_bo_tickets_customer
        FOREIGN KEY (customer_id) REFERENCES bo_customers (id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- final_status: los del enunciado (apartado 6.g). El agente solo propone;
-- la decisión (reviewed_by, reviewed_at) la toma una persona.
CREATE TABLE bo_agent_proposals (
    id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
    ticket_id        INT UNSIGNED NOT NULL,
    case_type        VARCHAR(50) NOT NULL,
    consulted_data   JSON NOT NULL,
    proposed_action  TEXT NOT NULL,
    justification    TEXT NOT NULL,
    confidence       DECIMAL(3,2) NOT NULL,               -- de 0.00 a 1.00
    risks            TEXT NULL,
    final_status     ENUM('pendiente_revision','aprobado','rechazado','ejecutado_simulado')
                     NOT NULL DEFAULT 'pendiente_revision',
    reviewed_by      VARCHAR(150) NULL,
    reviewed_at      DATETIME NULL,
    created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bo_proposals_ticket (ticket_id),
    KEY idx_bo_proposals_status (final_status),
    CONSTRAINT fk_bo_proposals_ticket
        FOREIGN KEY (ticket_id) REFERENCES bo_tickets (id)
        ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT chk_bo_proposals_confidence CHECK (confidence BETWEEN 0 AND 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- TRAZABILIDAD
-- ------------------------------------------------------------

-- Una fila por ejecución de n8n, también los reintentos
CREATE TABLE bo_integration_log (
    id                INT UNSIGNED NOT NULL AUTO_INCREMENT,
    event_id          BIGINT UNSIGNED NULL,               -- NULL si el evento llegó incompleto
    event_type        VARCHAR(50) NOT NULL,
    workflow          VARCHAR(50) NOT NULL,               -- Pedidos, Reintentos, Incidencias...
    result            ENUM('ok','error','duplicado','pendiente') NOT NULL,
    error_type        VARCHAR(50) NULL,                   -- tipos de docs/integracion.md
    message           VARCHAR(255) NULL,
    payload           JSON NULL,
    n8n_execution_id  VARCHAR(50) NULL,
    created_at        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_bo_log_event (event_id),
    KEY idx_bo_log_result (result),
    KEY idx_bo_log_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ------------------------------------------------------------
-- Fin del esquema
-- ------------------------------------------------------------
