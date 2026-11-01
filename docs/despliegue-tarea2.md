# Despliegue de la Tarea 2

Cómo se ponen en marcha n8n (parte A) y el back-office (parte B), en local para desarrollar y en la nube para la entrega. Las claves nunca van en el repositorio: solo en `api/config.php`, `backoffice/config.php`, el `.env` del servidor y las credenciales de n8n.

| Sistema | Local | Nube |
|---|---|---|
| Tienda | `php -S localhost:8000 index.php` desde `api/` | https://www.theblacklotustcg.onl/ (DonDominio) |
| n8n | http://localhost:5678 | `https://n8n.theblacklotustcg.onl` *(pendiente del servidor)* |
| Back-office | `php -S localhost:8080 api/index.php` desde `backoffice/` | `https://backoffice.theblacklotustcg.onl` *(pendiente del servidor)* |

## Parte A · n8n

### Claves que hay que repartir

Tres valores, iguales a ambos lados:

| Clave | En la tienda / back-office | En n8n (credencial de tipo *Header Auth*) |
|---|---|---|
| Secreto del webhook | `api/config.php` → `integration.webhook_secret` | `Tienda X-Webhook-Secret` (nombre de cabecera `X-Webhook-Secret`) |
| Clave de integración de la tienda | `api/config.php` → `integration.api_key` | `Tienda X-Api-Key` (cabecera `X-Api-Key`) |
| Clave del back-office | `BO_API_KEY` del back-office | `Back-office X-Api-Key` (cabecera `X-Api-Key`) |

Los flujos exportados en `integration/n8n/workflows/` solo hacen referencia a estas credenciales por su nombre: hay que crearlas en cada n8n.

### En local (Windows, sin Docker)

1. Node.js 24 o superior. Instalar n8n en una carpeta propia (por ejemplo `D:\n8n`):
   ```
   npm install n8n@2.45.7
   npm rebuild sqlite3 isolated-vm protobufjs msgpackr-extract
   ```
   npm 11 no ejecuta los scripts de instalación de las dependencias; sin el `rebuild`, n8n no tiene su base de datos SQLite.
2. Arrancarlo con `N8N_USER_FOLDER` apuntando a esa carpeta (ahí guarda flujos, credenciales e historial) y `GENERIC_TIMEZONE=Europe/Madrid`:
   ```
   n8n start
   ```
   La primera vez, http://localhost:5678 pide crear la cuenta de administrador.
3. Crear las tres credenciales de la tabla anterior (en local se pueden usar valores de prueba).
4. Importar los flujos y publicar "Pedidos" (con n8n parado, o desde la interfaz con *Import from file*):
   ```
   n8n import:workflow --input=integration/n8n/workflows/pedidos.json
   n8n import:workflow --input=integration/n8n/workflows/reintentos.json
   n8n publish:workflow --id=A0pedidos0000001
   ```
   n8n activa el webhook unos segundos después de arrancar.
5. En `api/config.php` de la tienda, sección `integration`: `webhook_url` = `http://localhost:5678/webhook/tienda` y las dos claves.
6. Comprobación: hacer un pedido en la tienda local y mirar en n8n (*Executions*) que el flujo "Pedidos" termina en "Registrar resultado", y en el back-office `GET /bo-api/integration-log?result=ok`.

"Reintentos" se puede dejar sin publicar en local y lanzar a mano con su nodo "Ejecutar ahora".

### En el servidor: `deploy/docker-compose.yml`

Un solo `docker-compose` levanta los cuatro contenedores del servidor: **n8n** con su **PostgreSQL 17** (n8n ya no admite MySQL) y el **back-office** (`backoffice/Dockerfile`) con su **MySQL 8**. Cada sistema tiene su base de datos y su volumen; la tienda sigue en DonDominio.

1. En el servidor, con Docker instalado, clonar el repositorio y:
   ```
   cd deploy
   cp .env.example .env      # rellenarlo aquí; .env no se sube
   docker compose up -d --build
   ```
   - Claves: `openssl rand -hex 32` para cada una.
   - `N8N_ENCRYPTION_KEY`: guardar una copia fuera del servidor. Si se pierde, n8n no puede leer las credenciales.
   - `N8N_WEBHOOK_URL`: la URL pública con HTTPS (la necesitan la tienda y Telegram).
   - La primera vez, MySQL ejecuta `schema.sql` y `seed.sql` del back-office (tablas y gestor de prueba, sin pedidos).
2. Subdominios `n8n.theblacklotustcg.onl` y `backoffice.theblacklotustcg.onl` apuntando al servidor (registros A en DonDominio) y HTTPS delante de los puertos `N8N_PORT` y `BO_PORT` *(pendiente: proxy con certificado, al desplegar)*.
3. Cuenta de administrador de n8n con contraseña fuerte; Celia y José, invitados como usuarios.
4. Credenciales con los valores reales (nunca los de local) e importación de los flujos (`docker compose exec n8n n8n import:workflow --input=...`).
5. En el nodo **Ajustes** de cada flujo: `tienda_api_url` = `https://www.theblacklotustcg.onl/api`, `bo_url` = `http://backoffice` (nombre del servicio dentro de Docker) y, en "Reintentos", `pedidos_webhook_url` = `http://localhost:5678/webhook/tienda`. Publicar "Pedidos" y "Reintentos" y reiniciar n8n (`docker compose restart n8n`).
6. En el `config.php` de la tienda en DonDominio: `webhook_url` = `https://n8n.theblacklotustcg.onl/webhook/tienda` y las claves reales.
7. Comprobación: un pedido en la web publicada y mirar *Executions* en n8n y `GET /bo-api/integration-log?result=ok`.

**Probado en local** (Docker Desktop, 01/11/2026) con puertos 5688 y 8088 y la tienda en el equipo (`tienda_api_url` = `http://host.docker.internal:8001`): pedido y pago → pedido creado y pagado en el back-office del contenedor (`ok` ×3); tras `docker compose down` y `up`, siguen el pedido, los flujos y el webhook.

### Copias de seguridad

- Flujos: `docker compose exec n8n n8n export:workflow --all --separate --output=/home/node/.n8n/backup` (sin credenciales) y copiarlos a `integration/n8n/workflows/` si cambian.
- Base de datos de n8n: `docker compose exec n8n-db pg_dump -U n8n n8n > n8n.sql`.
- Base de datos del back-office: `docker compose exec backoffice-db mysqldump -u root -p blacklotus_bo > backoffice.sql`.
- La `N8N_ENCRYPTION_KEY`, guardada aparte.

## Parte B · Back-office

*(La completa José.)*
