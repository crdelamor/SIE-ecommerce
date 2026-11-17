-- ------------------------------------------------------------
-- Tabla bo_web_metrics en una base que ya existe (servidor, noviembre de 2026).
--
-- schema.sql borra y recrea todo, y MySQL solo lo ejecuta la primera vez que arranca el
-- contenedor. Este fichero añade solo la tabla nueva y se puede ejecutar más de una vez:
--   docker compose exec -T backoffice-db mysql -u root -p blacklotus_bo < ../backoffice/db/web-metrics.sql
-- ------------------------------------------------------------

-- Visitas de la tienda por día. Las manda el flujo "Analítica" cada hora (ayer y hoy) y cada
-- envío sustituye la fila del día. Solo totales: ni sesiones ni datos de clientes.
CREATE TABLE IF NOT EXISTS bo_web_metrics (
    day                         DATE NOT NULL,
    visits                      INT UNSIGNED NOT NULL DEFAULT 0,
    visit_seconds               INT UNSIGNED NOT NULL DEFAULT 0,   -- suma de lo que duran las visitas
    visits_with_cart            INT UNSIGNED NOT NULL DEFAULT 0,
    visits_with_cart_and_order  INT UNSIGNED NOT NULL DEFAULT 0,
    updated_at                  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (day),
    CONSTRAINT chk_bo_web_cart CHECK (visits_with_cart <= visits),
    CONSTRAINT chk_bo_web_order CHECK (visits_with_cart_and_order <= visits_with_cart)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
