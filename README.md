# The Black Lotus TCG

Trabajo de SIE (UCAM). Tienda online de cartas coleccionables (Magic: The Gathering, Riftbound, Pokémon TCG y Yu-Gi-Oh!).

- **Tarea 1:** la tienda. Front en **React + Vite**, **API en PHP** sin framework y **MySQL/MariaDB**, desplegada en DonDominio.
- **Tarea 2:** la tienda se conecta por **n8n** con un **back-office** aparte (PHP + MySQL, con su propia base de datos). Incluye un **agente de IA** (Gemini) que propone qué hacer con las incidencias. Una persona aprueba o rechaza la propuesta por **Telegram**. n8n y el back-office están en una máquina virtual de Azure.

> Prototipo académico, sin actividad comercial real. Todos los datos son ficticios.

## Acceso

| Sistema | URL |
|---|---|
| Tienda | https://www.theblacklotustcg.onl |
| Administración de la tienda | https://www.theblacklotustcg.onl/#/admin |
| Back-office (cuadro de mando, pedidos, incidencias y registro) | https://backoffice.theblacklotustcg.onl |
| n8n | https://n8n.theblacklotustcg.onl |
| Bot de Telegram (pruebas) | [@blacklotus_soporte_bot](https://t.me/blacklotus_soporte_bot) |

Los usuarios están en [Usuarios de prueba](#usuarios-de-prueba). Para comprobar que todo responde:
- https://www.theblacklotustcg.onl/api/health
- https://backoffice.theblacklotustcg.onl/bo-api/health
- https://n8n.theblacklotustcg.onl/healthz

Las tres tienen que devolver `"status":"ok"`.

## Reparto del trabajo

**Tarea 1**

| Alumno | Parte |
|--------|-------|
| A (Celia) | Carrito, checkout (con datos de facturación), pago simulado y correo de confirmación |
| B (José) | Catálogo, estilo común y componentes, base de datos y despliegue |
| C (César) | Administración (con la facturación de los pedidos), eventos, soporte y seguridad |

**Tarea 2**

| Alumno | Parte |
|--------|-------|
| A (César) | n8n: flujos "Pedidos" (escenarios 1 y 2), "Reintentos", "Errores" y "Analítica", API de integración de la tienda, despliegue en Azure, matriz de decisión y diagramas |
| B (José) | Back-office: API `/bo-api`, base de datos, login del gestor, cuadro de mando con las métricas y pantallas de consulta |
| C (Celia) | Escenario 3: flujo "Incidencias" con el agente de IA y la aprobación por Telegram, flujo "Telegram" para que el cliente siga su incidencia, endpoint de incidencias de la tienda y contrato de integración |

El uso de IA de cada uno (herramienta, qué se le pidió, errores y cómo se validó) está en [`docs/ia-log.md`](docs/ia-log.md).

## Estructura

```
frontend/   React + Vite (HashRouter)
  src/components            B
  src/pages/home, catalog, product  B
  src/pages/cart, checkout  A
  src/pages/admin, support  C
api/        API PHP de la tienda (en el servidor va en /public/api/)
  index.php, lib/, config.example.php   común
  http/products.php                     B
  http/orders.php, services/, repositories/  A
  http/admin.php, support.php, events.php    C
  http/integration.php                  Tarea 2: lo que n8n le pide a la tienda
db/         schema.sql, seed.sql de la tienda
backoffice/ back-office (Tarea 2): otro sistema, con su base de datos
  api/http/       API /bo-api que llama n8n
  public/         login, cuadro de mando y pantallas del gestor
  db/             schema.sql, seed.sql y web-metrics.sql
  Dockerfile
integration/
  n8n/workflows/  los 6 flujos exportados de n8n
  payloads/       payloads de prueba de los tres escenarios
deploy/     docker-compose.yml y .env.example del servidor (n8n + back-office)
docs/       documentación (ver abajo)
```

Documentación de la Tarea 2:

| Fichero | Qué tiene |
|---|---|
| [`docs/integracion.md`](docs/integracion.md) | Contrato de integración: sobre de los eventos, validaciones, tipos de error, salida del agente y Telegram |
| [`docs/backoffice-api.md`](docs/backoffice-api.md) | API del back-office, endpoint a endpoint |
| [`docs/metricas.md`](docs/metricas.md) | Métricas del cuadro de mando, de dónde salen y por qué se eligieron |
| [`docs/matriz-decision.md`](docs/matriz-decision.md) | Matriz de decisión de implantación |
| [`docs/despliegue-tarea2.md`](docs/despliegue-tarea2.md) | Cómo se monta n8n y el back-office, en local y en el servidor |
| [`docs/diagramas/`](docs/diagramas/) | Diagrama del flujo de datos de todo el sistema y del flujo de pedidos |

## Arrancar en local

### Tienda

```bash
cd frontend
npm install
npm run dev
```

La API necesita PHP 8.1 o superior (con `pdo_mysql`, `mbstring` y `openssl`) y MySQL o MariaDB:
1. Copia `api/config.example.php` como `api/config.php` con tus datos (`config.php` no se sube a GitHub).
2. Arranca `php -S localhost:8000` desde la raíz del repositorio. Vite reenvía `/api` a ese servidor.

Sin `webhook_url` en `config.php`, la tienda funciona igual pero no manda los eventos a n8n.

### Back-office

1. Crea la base de datos `blacklotus_bo` e importa `backoffice/db/schema.sql` y después `backoffice/db/seed.sql`. El seed solo trae el gestor de prueba: los pedidos y las incidencias llegan por n8n.
2. Copia `backoffice/config.example.php` como `backoffice/config.php` con los datos de esa base de datos y una clave para `api_key` (la misma que pondrás en n8n).
3. Desde `backoffice/`: `php -S localhost:8080 -t public router.php`. El cuadro de mando queda en http://localhost:8080 y la API en http://localhost:8080/bo-api.

### n8n

Se instala con npm (n8n 2.45.7, Node.js 24) y se importan los flujos de `integration/n8n/workflows/` por línea de comandos, para que mantengan sus id. Los pasos, las credenciales que hay que crear (webhook, tienda, back-office, Telegram y Gemini) y cómo probar Telegram en local están en [`docs/despliegue-tarea2.md`](docs/despliegue-tarea2.md).

Los flujos del repositorio tienen las URLs de local en su nodo **Ajustes**, y el chat del grupo de Telegram como `PENDIENTE_CHAT_ID`.

## Base de datos

**Tienda.** Importa los dos ficheros en este orden, en local (phpMyAdmin o `mysql`) y en el hosting, **siempre de la misma versión**:

1. `db/schema.sql`: crea las tablas (borra las existentes, así que se puede reimportar).
2. `db/seed.sql`: datos de prueba ficticios (4 categorías, 15 productos (Sol Ring, Lightning Bolt y Dark Magician en tres versiones cada uno, más cartas sueltas y sobres), 4 clientes, 4 pedidos de ejemplo y el administrador).

Cada versión de una carta (nueva, usada o foil) es un producto distinto. `products.card_ref` agrupa las versiones de la misma carta.

**Back-office.** Tablas `bo_*` en `backoffice/db/schema.sql`: clientes, pedidos y líneas, historial de estados, incidencias, propuestas del agente, registro de integración, visitas por día y usuarios. En el servidor lo ejecuta MySQL la primera vez que arranca el contenedor. `web-metrics.sql` crea solo la tabla de visitas, para un back-office que ya existía antes.

## Usuarios de prueba

Datos ficticios, solo para el prototipo académico:

| Dónde | Rol | Email | Contraseña |
|-----|-----|-------|------------|
| Tienda | Administrador | `admin@tcg-sie.example` | `Admin-SIE-2026` |
| Back-office | Gestor | `gestor@blacklotus.test` | `PENDIENTE` |

En las dos bases de datos solo se guarda el hash (`password_hash()` de PHP), nunca la contraseña.

Tarjetas de prueba del pago simulado: `4242 4242 4242 4242` se aprueba y `4000 0000 0000 0002` se rechaza. Cupón de descuento: `BIENVENIDA10`.

## Probar los tres escenarios

En la web publicada:

1. **Pedido integrado.** Haz una compra con la tarjeta que se aprueba. En el back-office aparece el pedido en *Pedidos*, con cliente, líneas, importe, estado y fecha, y en *Registro* sale la ejecución de n8n.
2. **Cambio de estado.** En la administración de la tienda, cambia el estado del pedido (por ejemplo, a enviado). El back-office lo pone en el historial del pedido. Si el back-office no está disponible, el evento queda `pendiente` y "Reintentos" lo vuelve a mandar cada 5 minutos.
3. **Incidencia con Telegram.**
   1. En *Soporte*, envía una incidencia con el código y el email de un pedido.
   2. Pulsa "Seguir mi incidencia por Telegram" y después *Iniciar* en el bot. Tiene que contestar "Listo...".
   3. El agente consulta el pedido y el historial del cliente, clasifica la incidencia y manda su propuesta al grupo de Telegram del equipo.
   4. Si se aprueba, el cliente recibe la respuesta por el bot y la incidencia se cierra. Si se rechaza, no se le manda nada.

   La propuesta y la decisión quedan en el detalle de la incidencia del back-office.

Para probar el webhook sin la tienda, están los payloads de [`integration/payloads/`](integration/payloads/): `escenario1.json`, `escenario2.json`, `escenario3.json` e `incompleto.json`, este último sin `data.code` para ver la rama de error. Se mandan con `POST` al webhook de n8n (`/webhook/tienda`) con la cabecera `X-Webhook-Secret`. Sin el secreto, n8n responde 403.

## Correo de confirmación (opcional)

Al aprobarse el pago, la API envía al cliente un correo que es una **factura proforma de confirmación de pago**:
- Resume el pedido: líneas con su foto, subtotal sin IVA, descuento, envío, IVA y total.
- Enlaza a la página del pedido.
- No es una factura fiscal definitiva, y el pago es simulado, así que no se cobra nada.

Se envía por SMTP con [PHPMailer](https://github.com/PHPMailer/PHPMailer) 7.1.1 (en `api/lib/PHPMailer/`, licencia LGPL). Está desactivado por defecto: en local no hace falta configurarlo.

Para activarlo, en el bloque `mail` del `config.php` del servidor (ver `config.example.php`):
- `enabled => true` y los datos SMTP de una cuenta del propio dominio.
- En DonDominio el servidor es `smtp.dondominio.com`. Con `smtp.<dominio>` el certificado no coincide y falla.
- Puerto 587, `secure => 'tls'` y como usuario el email completo (`pedidos@theblacklotustcg.onl`).
- La contraseña de esa cuenta va **solo** en `config.php`, nunca en GitHub.

Si el envío falla, el pedido sigue pagado y el error queda en el log de PHP. Los correos a direcciones `@example.com` no llegan: para probarlo usa un correo real.

## Despliegue

### Tienda en DonDominio

El hosting (plan Básico) solo admite web estática y PHP, sin Node.js. Por eso el front se compila en local y se sube ya construido. La guía paso a paso, con los datos reales del hosting y la lista de comprobaciones, está en [`docs/despliegue.md`](docs/despliegue.md). Resumen:

1. **Base de datos.** En el panel del hosting crea una base de datos y su usuario. En phpMyAdmin importa `db/schema.sql` y después `db/seed.sql`. En este hosting el servidor de base de datos es `bbdd.theblacklotustcg.onl` (no `localhost`) y el motor es MariaDB.
2. **Compilar el front.** Desde `frontend/`: `npm run build`. Genera `frontend/dist/`.
3. **Subir los ficheros.** La carpeta raíz de la web es **`/public/`** (no `public_html/`).
   - El contenido de `frontend/dist/` va a `/public/`, incluido `.htaccess`.
   - La carpeta `api/` va a `/public/api/`, sin `config.php`.
   - La carpeta `db/` no se sube.
4. **Configuración en el servidor.** Crea `/public/api/config.php` a partir de `config.example.php` con los datos de la base de datos **del hosting** y, para la Tarea 2, la sección `integration`:
   - la URL del webhook de n8n;
   - el secreto y la clave de integración;
   - el usuario del bot de Telegram.

   Este fichero solo existe en el servidor y nunca se sube a GitHub.
5. **SSL.** El certificado debe cubrir el dominio con y sin `www`. Cuando la web cargue por `https://`, descomenta la redirección a HTTPS de `/public/.htaccess`.
6. **Comprobar.** Abre:
   - `/api/health`;
   - `/api/products` (15 productos);
   - el catálogo;
   - una compra de prueba completa;
   - el login de administrador.

   `/api/config.php` debe dar 403.
7. **Antes de grabar el vídeo.** Ejecuta `TRUNCATE TABLE events;` en phpMyAdmin para que el visor de eventos solo enseñe la demostración.

### n8n y back-office en Azure

DonDominio no puede ejecutar n8n, así que n8n y el back-office van en una máquina virtual de Azure (Azure for Students, Ubuntu 24.04).
- `deploy/docker-compose.yml` levanta cuatro contenedores: n8n con PostgreSQL, y el back-office con MySQL.
- Caddy pone delante HTTPS con certificados de Let's Encrypt.
- Las claves van en `deploy/.env` (a partir de `.env.example`) y en las credenciales de n8n, nunca en el repositorio.

Los pasos completos están en [`docs/despliegue-tarea2.md`](docs/despliegue-tarea2.md).

## Limitaciones conocidas

### Tienda (Tarea 1)

- **Redondeo del IVA.** El precio con IVA de la ficha se calcula por unidad y el checkout calcula el IVA sobre el total, así que con varias unidades puede haber 1 céntimo de diferencia (3 × Sol Ring usada: 10,17 € en la ficha y 10,16 € en el checkout). El importe que se cobra es siempre el del servidor.
- **Datos de producto.** Las ilustraciones son propias, no las cartas reales (derechos de imagen). Los nombres de ediciones, los ilustradores y los precios son orientativos. Riftbound solo tiene un sobre.
- **Idioma de las imágenes.** Los textos pintados en las imágenes de producto están en español aunque la carta sea inglesa (campo `language`). Está anotado como mejora futura.
- **Variantes.** `order_items` guarda el nombre, el precio y el IVA, pero no el estado, el idioma ni el foil de la carta comprada. Por eso no deben editarse esos campos en productos que ya tienen pedidos.
- **Sin panel de productos.** El catálogo solo se modifica por la base de datos; la administración no permite dar de alta ni editar productos.
- **Clientes.** Se crea un cliente nuevo en cada pedido, aunque el email se repita.
- **Base de datos.** `schema.sql` borra y recrea las tablas: no hay migraciones.
- **Factura.** El correo es una factura proforma de confirmación de pago, no una factura fiscal.
- **Fuente externa.** La tipografía Space Grotesk se carga desde Google Fonts.
- **Accesibilidad.** Se han pasado comprobaciones automáticas (axe, sin violaciones A/AA) y de teclado, pero no una auditoría con usuarios ni con lectores de pantalla.
- **Despliegue manual.** Se sube por el administrador de archivos o FTP y no hay integración continua. Se probó con MariaDB 12.3 en local y el hosting tiene MariaDB 12.1.
- **Intentos de login.** El login de administrador no limita los intentos fallidos.
- **Contraseñas de prueba.** La del administrador y la del gestor están publicadas en este README porque el enunciado pide los usuarios de prueba. En un entorno real se cambiarían tras desplegar.
- **Soporte.** El formulario no tiene protección contra spam, y la administración ve las incidencias pero no puede cambiar su estado.
- **Incidencias sin pagar.** Un pedido sin pagar puede pasar de `incidencia` a `en_preparacion` sin pasar por `pagado`; depende del criterio del administrador.
- **Stock reservado.** Los pedidos pendientes de pago reservan stock sin caducidad hasta que se cancelan (por ejemplo, si tras un pago rechazado el cliente recarga la página o cambia el email y vuelve a pagar, el primer pedido se queda pendiente), y los pedidos de ejemplo del seed no restaron stock al crearse.
- **Concurrencia.** El bloqueo de pedidos (`SELECT … FOR UPDATE`) no se ha probado con peticiones realmente simultáneas, porque el servidor de desarrollo de PHP las atiende de una en una.
- **Analítica del carrito.** `cart.item_added` solo guarda la cantidad: para analizar por juego, edición o estado hay que cruzar `entity_id` con `products`.
- **Exportación de eventos.** Carga en memoria todos los eventos que cumplen el filtro.
- **Eventos del navegador.** `POST /api/events` no limita las peticiones, así que se podría llenar la tabla de eventos con datos falsos.
- **Cabeceras de seguridad.** La API no envía `X-Content-Type-Options: nosniff`.

### Integración, back-office y agente (Tarea 2)

- **Acciones simuladas.** Al aprobar una propuesta, la acción (cambio, devolución...) no se hace de verdad: queda como `ejecutado_simulado`. Lo único real es el mensaje al cliente por Telegram.
- **Quién aprueba.** Los botones de Telegram no dicen quién los ha pulsado, así que `reviewed_by` es siempre el alias de la cuenta de pruebas que revisa (nodo **Ajustes**). La aprobación solo se hace desde Telegram, no desde el back-office.
- **Límite de Gemini.** El agente usa la capa gratuita de Gemini. Si no responde o se pasa del límite, el ticket se crea igual, pero sin propuesta (`agente_no_disponible`), y hay que repetirlo a mano desde n8n.
- **Confianza del agente.** El agente da un nivel de confianza, pero no está comprobado que corresponda con lo que acierta. Por eso el cuadro de mando compara la confianza de las propuestas aprobadas con la de las rechazadas.
- **Número de incidencia.** El número que ve el cliente es el de la tienda y no coincide con el id del ticket en el back-office. El mensaje del grupo muestra los dos.
- **Telegram solo en el servidor.** Cada bot tiene un único webhook HTTPS, así que el flujo "Telegram" solo funciona en el n8n del servidor. En local se prueba a mano con `getUpdates`.
- **Reintentos.** Un evento pendiente se reintenta cada 5 minutos durante una hora (12 veces). Después queda como `reintentos_agotados` y hay que revisarlo a mano.
- **Clientes por email.** El back-office identifica al cliente por su email, porque la tienda crea un cliente nuevo en cada pedido.
- **Totales del pedido.** Al back-office llegan las líneas sin IVA y el total del pedido, pero no el IVA, el envío y el descuento por separado. El detalle del pedido muestra su suma en una sola línea.
- **Visitas.** Los eventos de navegación no salen de la tienda: "Analítica" solo manda al back-office los totales por día, cada hora.
- **Un solo servidor.** n8n y el back-office están en la misma máquina virtual, sin copia de seguridad automática. Las copias se hacen a mano (ver `docs/despliegue-tarea2.md`).
- **Pantallas del gestor.** Son de consulta: el gestor no puede cambiar pedidos ni incidencias desde el back-office.
