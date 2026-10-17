# The Black Lotus TCG

Tarea 1 SIE (UCAM). Tienda online de cartas coleccionables (Magic: The Gathering, Riftbound, Pokémon TCG y Yu-Gi-Oh!) con **React + Vite** (front), **API en PHP** sin framework y **MySQL/MariaDB**, desplegada en DonDominio.

> Prototipo académico, sin actividad comercial real. Todos los datos son ficticios.

## Acceso

- **Tienda:** https://www.theblacklotustcg.onl
- **Administración:** https://www.theblacklotustcg.onl/#/admin (usuario de prueba en [Usuarios de prueba](#usuarios-de-prueba))
- **Comprobar que la API responde:** https://www.theblacklotustcg.onl/api/health debe devolver `{"status":"ok"}`.

## Reparto del trabajo

| Alumno | Parte |
|--------|-------|
| A (Celia) | Carrito, checkout (con datos de facturación), pago simulado y correo de confirmación |
| B (José) | Catálogo, estilo común y componentes, base de datos y despliegue |
| C (César) | Administración (con la facturación de los pedidos), eventos, soporte y seguridad |

El uso de IA de cada uno está en [`docs/ia-log.md`](docs/ia-log.md).

## Estructura

```
frontend/   React + Vite (HashRouter)
  src/components            B
  src/pages/home, catalog, product  B
  src/pages/cart, checkout  A
  src/pages/admin, support  C
api/        API PHP (en el servidor va en /public/api/)
  index.php, lib/, config.example.php   común
  http/products.php                     B
  http/orders.php, services/, repositories/  A
  http/admin.php, support.php, events.php    C
db/         schema.sql, seed.sql (B)
docs/       despliegue.md (guía del hosting) e ia-log.md (diario de uso de IA)
```

## Arrancar en local

```bash
cd frontend
npm install
npm run dev
```

La API necesita PHP 8.1 o superior (con `pdo_mysql`, `mbstring` y `openssl`) y MySQL o MariaDB: copia `api/config.example.php` como `api/config.php` con tus datos (`config.php` no se sube a GitHub) y arranca `php -S localhost:8000` desde la raíz del repositorio. Vite reenvía `/api` a ese servidor.

## Base de datos

Importa los dos ficheros en este orden, en local (phpMyAdmin o `mysql`) y en el hosting, **siempre de la misma versión**:

1. `db/schema.sql`: crea las 10 tablas (borra las existentes, así que se puede reimportar).
2. `db/seed.sql`: datos de prueba ficticios (4 categorías, 15 productos (Sol Ring, Lightning Bolt y Dark Magician en tres versiones cada uno, más cartas sueltas y sobres), 4 clientes, 4 pedidos de ejemplo y el administrador).

Cada versión de una carta (nueva, usada o foil) es un producto distinto. `products.card_ref` agrupa las versiones de la misma carta.

## Usuarios de prueba

Datos ficticios, solo para el prototipo académico:

| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | `admin@tcg-sie.example` | `Admin-SIE-2026` |

En la base de datos solo se guarda el hash (`password_hash()` de PHP), nunca la contraseña.

Tarjetas de prueba del pago simulado: `4242 4242 4242 4242` se aprueba y `4000 0000 0000 0002` se rechaza. Cupón de descuento: `BIENVENIDA10`.

## Correo de confirmación (opcional)

Al aprobarse el pago, la API envía al cliente un correo que es una **factura proforma de confirmación de pago**: resume el pedido (líneas con su foto, subtotal sin IVA, descuento, envío, IVA y total) y enlaza a la página del pedido. No es una factura fiscal definitiva, y el pago es simulado, así que no se cobra nada. Se envía por SMTP con [PHPMailer](https://github.com/PHPMailer/PHPMailer) 7.1.1 (en `api/lib/PHPMailer/`, licencia LGPL). Está desactivado por defecto: en local no hace falta configurarlo.

Para activarlo, en el `config.php` del servidor, bloque `mail` (ver `config.example.php`): `enabled => true` y los datos SMTP de una cuenta del propio dominio. En DonDominio: servidor `smtp.dondominio.com` (con `smtp.<dominio>` el certificado no coincide y falla), puerto 587, `secure => 'tls'` y usuario = el email completo (`pedidos@theblacklotustcg.onl`). La contraseña de esa cuenta va **solo** en `config.php`, nunca en GitHub. Si el envío falla, el pedido sigue pagado y el error queda en el log de PHP. Los correos a direcciones `@example.com` no llegan: para probarlo usa un correo real.

## Despliegue en DonDominio

El hosting (plan Básico) solo admite web estática y PHP, sin Node.js. Por eso el front se compila en local y se sube ya construido. La guía paso a paso, con los datos reales del hosting y la lista de comprobaciones, está en [`docs/despliegue.md`](docs/despliegue.md). Resumen:

1. **Base de datos.** En el panel del hosting crea una base de datos y su usuario. En phpMyAdmin importa `db/schema.sql` y después `db/seed.sql`. En este hosting el servidor de base de datos es `bbdd.theblacklotustcg.onl` (no `localhost`) y el motor es MariaDB.
2. **Compilar el front.** Desde `frontend/`: `npm run build`. Genera `frontend/dist/`.
3. **Subir los ficheros.** La carpeta raíz de la web es **`/public/`** (no `public_html/`).
   - El contenido de `frontend/dist/` va a `/public/`, incluido `.htaccess`.
   - La carpeta `api/` va a `/public/api/`, sin `config.php`.
   - La carpeta `db/` no se sube.
4. **Configuración en el servidor.** Crea `/public/api/config.php` a partir de `config.example.php` con los datos de la base de datos **del hosting**. Este fichero solo existe en el servidor y nunca se sube a GitHub.
5. **SSL.** El certificado debe cubrir el dominio con y sin `www`. Cuando la web cargue por `https://`, descomenta la redirección a HTTPS de `/public/.htaccess`.
6. **Comprobar.** Abre `/api/health`, `/api/products` (15 productos), el catálogo, una compra de prueba completa y el login de administrador. `/api/config.php` debe dar 403.
7. **Antes de grabar el vídeo.** Ejecuta `TRUNCATE TABLE events;` en phpMyAdmin para que el visor de eventos solo enseñe la demostración.

## Limitaciones conocidas

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
- **Contraseña de prueba.** La del administrador está publicada en este README porque lo pide el enunciado; en un entorno real se cambiaría tras desplegar.
- **Soporte.** El formulario no tiene protección contra spam, y la administración ve las incidencias pero no puede cambiar su estado.
- **Incidencias sin pagar.** Un pedido sin pagar puede pasar de `incidencia` a `en_preparacion` sin pasar por `pagado`; depende del criterio del administrador.
- **Stock reservado.** Los pedidos pendientes de pago reservan stock sin caducidad hasta que se cancelan (por ejemplo, si tras un pago rechazado el cliente recarga la página o cambia el email y vuelve a pagar, el primer pedido se queda pendiente), y los pedidos de ejemplo del seed no restaron stock al crearse.
- **Concurrencia.** El bloqueo de pedidos (`SELECT … FOR UPDATE`) no se ha probado con peticiones realmente simultáneas, porque el servidor de desarrollo de PHP las atiende de una en una.
- **Analítica del carrito.** `cart.item_added` solo guarda la cantidad: para analizar por juego, edición o estado hay que cruzar `entity_id` con `products`.
- **Exportación de eventos.** Carga en memoria todos los eventos que cumplen el filtro.
- **Eventos del navegador.** `POST /api/events` no limita las peticiones, así que se podría llenar la tabla de eventos con datos falsos.
- **Cabeceras de seguridad.** La API no envía `X-Content-Type-Options: nosniff`.
