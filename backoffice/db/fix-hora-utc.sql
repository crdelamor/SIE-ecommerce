-- ------------------------------------------------------------
-- Corrección única de las fechas guardadas en UTC (servidor, noviembre de 2026).
--
-- Hasta que db.php fijó la zona horaria de la sesión, NOW() y DEFAULT CURRENT_TIMESTAMP
-- daban la hora UTC del MySQL del servidor, mientras que las fechas de la tienda
-- (placed_at, opened_at, changed_at) se guardan en hora de Madrid. Todas las filas son
-- posteriores al cambio de hora del 25/10, así que la diferencia es siempre de 1 hora.
--
-- Se ejecuta UNA SOLA VEZ, con n8n parado y antes de arrancar el back-office con el
-- db.php nuevo; si no, las filas nuevas también se moverían. Antes, copia de seguridad:
--   docker compose exec backoffice-db mysqldump -u root -p blacklotus_bo > bo-antes-hora.sql
-- En local no hace falta: allí MySQL ya usa la hora del equipo.
-- ------------------------------------------------------------

START TRANSACTION;

UPDATE bo_customers       SET created_at  = created_at  + INTERVAL 1 HOUR;
UPDATE bo_orders          SET synced_at   = synced_at   + INTERVAL 1 HOUR;
UPDATE bo_agent_proposals SET created_at  = created_at  + INTERVAL 1 HOUR;
UPDATE bo_agent_proposals SET reviewed_at = reviewed_at + INTERVAL 1 HOUR WHERE reviewed_at IS NOT NULL;
UPDATE bo_integration_log SET created_at  = created_at  + INTERVAL 1 HOUR;

COMMIT;
