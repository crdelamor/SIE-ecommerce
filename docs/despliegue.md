# Despliegue en DonDominio

Guía para publicar y actualizar la tienda The Black Lotus TCG en el hosting (plan Básico: web estática + PHP + MariaDB, sin Node.js). Responsable: B.

Los datos de acceso (servidor FTP, usuario, contraseñas de la base de datos y del correo) están en el correo de bienvenida y en el panel de control del hosting. **No se escriben en este repositorio, ni se pegan en ningún chat ni en ninguna IA.**

## Datos de este hosting

Lo que hay que saber de nuestro hosting, y que difiere de lo previsto en el plan:

| Dato | Valor |
|---|---|
| Dirección pública | `https://www.theblacklotustcg.onl` |
| Carpeta raíz de la web | **`/public/`** (no `public_html/`) |
| Servidor de base de datos | **`bbdd.theblacklotustcg.onl`**, puerto 3306 (no `localhost`) |
| Motor de base de datos | **MariaDB 12.1** (no MySQL); `schema.sql` y `seed.sql` se importan igual |
| PHP | 8.5 (el código pide 8.1 o superior) |
| Certificado SSL | Instalado para `www`. Para el dominio sin `www`, ver el apartado 5 |

## Estructura en el servidor

```
/public/                  <- contenido de frontend/dist/ (index.html, assets/, products/, logo.svg, favicon.svg, .htaccess)
/public/api/              <- carpeta api/ del repositorio
/public/api/config.php    <- SOLO en el servidor (no está en GitHub)
```

La carpeta `db/` del repositorio **no se sube al servidor**: los dos ficheros SQL se importan desde el ordenador con phpMyAdmin.

## Requisitos del hosting

Antes de subir nada, comprueba en el panel de control:

1. **Versión de PHP: 8.1 o superior.** El código usa funciones de PHP 8.1 (`new` como valor por defecto en los constructores de `OrderService`, `AuthService`, `PaymentService`…, propiedades `readonly` y `array_is_list`). Con PHP 8.0 o anterior la API no arranca.
2. **Extensiones activas: `pdo_mysql`, `mbstring` y `openssl`.** La primera es la conexión con la base de datos, la segunda la usan las validaciones de longitud de los formularios (`mb_strlen`) y la tercera el correo de confirmación con STARTTLS.
3. **Errores de PHP sin mostrar:** `display_errors` desactivado. Así un fallo no enseña rutas del servidor al visitante; la API ya responde con un JSON genérico y guarda el detalle en el log.

## 1. Base de datos (solo la primera vez)

1. En el panel, pestaña *Base de datos*, crea una base de datos con su usuario y una contraseña que eliges tú. Anota el nombre y el usuario (en el hosting suelen llevar un prefijo).
2. Abre phpMyAdmin y selecciona esa base de datos.
3. Pestaña **Importar**: primero `db/schema.sql` y después `db/seed.sql`.
4. Comprueba que están las 10 tablas y que `products` tiene 15 filas.

`schema.sql` borra las tablas existentes antes de crearlas: al reimportar se pierden los datos. Importa siempre los dos ficheros de la **misma versión** de la rama `develop`; mezclar un esquema antiguo con un seed nuevo (o al revés) hace que la API dé error 500.

## 2. Compilar el frontend

```bash
cd frontend
npm install
npm run build
```

Genera `frontend/dist/`. `vite.config.js` usa `base: './'` y la app usa `HashRouter`, así que funciona sin reglas de reescritura en el hosting.

## 3. Subir los ficheros

Con un cliente FTP (por ejemplo FileZilla) o con el administrador de archivos del panel:

1. Sube **el contenido** de `frontend/dist/` (no la carpeta `dist`) a `/public/`. Incluye `.htaccess` y `products/`: comprueba que se muestran los ficheros ocultos.
2. Sube la carpeta `api/` a `/public/api/`, **sin `config.php`**. Incluye `api/.htaccess` (es un fichero oculto) y `api/lib/PHPMailer/`.
3. Si el servidor ya tenía un `index.html` de ejemplo, reemplázalo.
4. No subas la carpeta `db/` ni ningún fichero de prueba o de diagnóstico.
5. **Comprueba desde fuera que todo se ha subido.** El administrador de archivos del panel puede dejar carpetas a medias sin avisar (nos pasó con `assets/`, `products/`, `api/lib/PHPMailer/` y `api/services/`). Abre una de las rutas de la lista del apartado 9 en una ventana de incógnito: si `/api/...` responde pero el pago no devuelve `email_sent`, o la web sale en blanco, falta algún fichero.

## 4. Configuración de la API en el servidor

1. Crea `/public/api/config.php` a partir de `api/config.example.php`.
2. En el bloque `db`, pon el servidor **`bbdd.theblacklotustcg.onl`**, el puerto 3306 y el nombre, el usuario y la contraseña de la base de datos que has creado. La contraseña se escribe solo en ese fichero, en el servidor.
3. El bloque `mail` es opcional (ver «Correo de confirmación»). Sin él, o con `'enabled' => false`, la tienda funciona igual y no envía correos.
4. Comprueba que el fichero no es accesible desde el navegador: `https://www.theblacklotustcg.onl/api/config.php` debe dar 403. Lo bloquea `api/.htaccess`.

## 5. Certificado SSL y HTTPS

1. El certificado cubre `www.theblacklotustcg.onl`. Comprueba que `https://www.theblacklotustcg.onl` carga sin avisos del navegador.
2. **Dominio sin `www`:** `https://theblacklotustcg.onl` da un error de certificado (sale el genérico de DonDominio, no el nuestro). En el panel, en el certificado SSL, hay que incluir también `theblacklotustcg.onl` sin `www`. Después, comprueba que carga sin aviso.
3. La redirección de HTTP a HTTPS está en `/public/.htaccess`, comentada a propósito en el repositorio: si se activa antes de tener el certificado, la web deja de cargar. Con el certificado funcionando, descomenta las 3 líneas y comprueba en una ventana de incógnito que `http://` pasa a `https://`.

## 6. Actualizar una versión ya desplegada

Cada vez que entren cambios nuevos en `develop`:

1. Compila de nuevo el frontend (apartado 2) y sube el contenido de `frontend/dist/` a `/public/`, sobrescribiendo. Sube también la carpeta `api/` (sin `config.php`).
2. **Si la versión cambia los datos de prueba, actualiza la base de datos.** Ejemplo: las imágenes de los productos pasaron de `.svg` a `.png`. Si la base de datos publicada sigue apuntando a `.svg`, la web se ve con las imágenes rotas. Dos formas:
   - En phpMyAdmin, pestaña *SQL*, ejecuta: `UPDATE products SET image_url = REPLACE(image_url, '.svg', '.png') WHERE image_url LIKE 'products/%.svg';`
   - O reimporta `db/schema.sql` y `db/seed.sql` (borra también los pedidos de prueba).
   - Después, comprueba que no queda ninguna: `SELECT COUNT(*) FROM products WHERE image_url LIKE '%.svg';` debe dar 0.
3. Si el cambio añade columnas o tablas (`schema.sql`), hay que reimportar los dos ficheros: no hay migraciones.
4. Repite la lista de comprobación del apartado 9.

## 7. Correo de confirmación (opcional)

Al aprobarse un pago, la API puede enviar por SMTP un correo que es una **factura proforma de confirmación de pago** (resumen del pedido con subtotal, descuento, envío, IVA y total; no es una factura fiscal). Está desactivado hasta que se configura:

1. En el panel, apartado *Correo*, crea el buzón `pedidos@theblacklotustcg.onl` con una contraseña fuerte.
2. En `/public/api/config.php` del servidor, añade el bloque `mail` de `config.example.php` con: `enabled` a `true`, `host` **`smtp.dondominio.com`** (con `smtp.theblacklotustcg.onl` el certificado del servidor no coincide y el envío falla con «subjectAltName did not match»), `port` 587, `secure` `'tls'`, `user` y `from` `pedidos@theblacklotustcg.onl`, `from_name` `The Black Lotus TCG`, `password` la del buzón y `site_url` `https://www.theblacklotustcg.onl`. La contraseña solo va ahí, nunca en GitHub ni por chat.
3. En la zona DNS del dominio, comprueba que hay registro SPF (y DKIM si se puede activar), para que los correos no vayan a spam.
4. Comprueba que PHP tiene activas `openssl`, `mbstring` y `pdo_mysql`.
5. Prueba una compra con un email tuyo de verdad (los `@example.com` no llegan). Debe llegar «Tu pedido PED-… está confirmado». Si no llega, el motivo queda en el log de errores de PHP del panel.

## 8. Antes de grabar el vídeo

En phpMyAdmin, pestaña *SQL*, ejecuta `TRUNCATE TABLE events;`. Así el visor de eventos de la administración enseña solo la compra del vídeo y no las pruebas anteriores.

## 9. Lista de comprobación tras cada despliegue

- [ ] `https://www.theblacklotustcg.onl/api/health` devuelve `{"status":"ok"}`.
- [ ] `/api/products` devuelve los 15 productos y las imágenes que indican son `.png`; `/api/categories` devuelve los 4 juegos.
- [ ] `/api/no-existe` devuelve solo `{"error":"Ruta no encontrada"}` (404), sin avisos de PHP ni rutas del servidor.
- [ ] La portada, el catálogo (con filtro por juego) y la ficha de producto se ven bien y las imágenes cargan.
- [ ] Recargar una página interna no da error 404 (por `HashRouter`).
- [ ] Compra de prueba completa: tarjeta `4000 0000 0000 0002` (rechazada) y luego `4242 4242 4242 4242` (aprobada); el pedido aparece en `#/admin`.
- [ ] Login de administrador con el usuario de prueba del README.
- [ ] `http://` redirige a `https://` y el candado aparece, con y sin `www` (cuando el certificado cubra el dominio sin `www`).
- [ ] Aviso «Prototipo académico, sin actividad comercial real» visible.
- [ ] `/public/api/config.php` da 403 y no hay en el servidor ficheros de diagnóstico ni la carpeta `db/`.

## Problemas frecuentes

| Síntoma | Causa probable |
|---|---|
| `/api/...` devuelve 500 | `config.php` no existe o tiene datos incorrectos (el servidor debe ser `bbdd.theblacklotustcg.onl`), o la base de datos tiene un `schema.sql` antiguo. Mira el log de errores de PHP en el panel. |
| `/api/products` da 500 pero `/api/health` va bien | La consulta a la base de datos falla: faltan tablas o columnas (esquema antiguo) o los datos de `config.php` no son correctos. Reimporta `schema.sql` y `seed.sql` de la misma versión. |
| La API da 500 en todas las rutas nada más subirla | La versión de PHP es anterior a 8.1 (ver «Requisitos del hosting»). |
| Los formularios (checkout o soporte) dan 500 | Falta la extensión `mbstring` o `pdo_mysql` en PHP. |
| `/api/...` devuelve 404 | No se subió `api/.htaccess` (es un fichero oculto) o falta `mod_rewrite`. |
| Las imágenes de los productos salen rotas | La base de datos publicada apunta a `.svg` y las imágenes subidas son `.png`: ejecuta el `UPDATE` del apartado 6. |
| `https://theblacklotustcg.onl` (sin `www`) da error de certificado | El certificado solo cubre `www`: ver el apartado 5. |
| La web no carga tras activar la redirección | Se descomentó antes de activar el SSL. Vuelve a comentar las líneas. |
| Pantalla en blanco | Se subió la carpeta `dist` en vez de su contenido, o falta `index.html` en `/public/`. |
| No llega el correo de confirmación | Mira el log de errores de PHP: suele ser el servidor SMTP, el usuario o la contraseña del buzón, o la extensión `openssl`. |
| Se ven datos viejos | Caché del navegador: recarga forzada (`Ctrl+F5`). |
