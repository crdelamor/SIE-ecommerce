# Despliegue en DonDominio

Guía para publicar la tienda en el hosting (plan Básico: web estática + PHP + MySQL, sin Node.js). Responsable: B.

Los datos de acceso (servidor FTP, usuario, contraseña, datos de MySQL) están en el correo de bienvenida y en el panel de control del hosting. **No se escriben en este repositorio.**

## Estructura en el servidor

```
public_html/            <- contenido de frontend/dist/ (index.html, assets/, products/, .htaccess)
public_html/api/        <- carpeta api/ del repositorio
public_html/api/config.php   <- SOLO en el servidor (no está en GitHub)
```

## Requisitos del hosting

Antes de subir nada, comprueba en el panel de control:

1. **Versión de PHP: 8.1 o superior.** El código usa funciones de PHP 8.1 (`new` como valor por defecto en los constructores de `OrderService`, `AuthService`, `PaymentService`…, propiedades `readonly` y `array_is_list`). Con PHP 8.0 o anterior la API no arranca. Si el panel ofrece varias versiones, elige la más reciente disponible.
2. **Extensiones activas: `pdo_mysql` y `mbstring`.** La primera es la conexión con MySQL; la segunda la usan las validaciones de longitud de los formularios (`mb_strlen`).
3. **Errores de PHP sin mostrar:** `display_errors` desactivado (en el panel, en la configuración de PHP). Así un fallo no enseña rutas del servidor al visitante; la API ya responde con un JSON genérico y guarda el detalle en el log.

## 1. Base de datos

1. En el panel, crea una base de datos MySQL y un usuario con todos los permisos sobre ella. Anota nombre, usuario y contraseña (en el hosting suelen llevar un prefijo).
2. Abre phpMyAdmin, selecciona esa base de datos.
3. Pestaña **Importar**: `db/schema.sql` y, después, `db/seed.sql`.
4. Comprueba en phpMyAdmin que están las 10 tablas y que `products` tiene datos.

`schema.sql` borra las tablas existentes antes de crearlas: al reimportar se pierden los datos.

## 2. Compilar el frontend

```bash
cd frontend
npm install
npm run build
```

Genera `frontend/dist/`. `vite.config.js` usa `base: './'` y la app usa `HashRouter`, así que funciona sin reglas de reescritura en el hosting.

## 3. Subir por FTP

Con un cliente FTP (por ejemplo FileZilla), con los datos del panel:

1. Sube **el contenido** de `frontend/dist/` (no la carpeta `dist`) a `public_html/`. Incluye `.htaccess` y `products/`: comprueba que el cliente muestra los ficheros ocultos.
2. Sube la carpeta `api/` a `public_html/api/`. No subas `config.php` local.
3. Si el servidor ya tenía un `index.html` de ejemplo, reemplázalo.

## 4. Configuración de la API en el servidor

1. Copia `api/config.example.php` como `public_html/api/config.php`.
2. Rellena `host`, `name`, `user` y `password` con los datos de MySQL **del hosting** (el `host` suele ser `localhost`, pero confírmalo en el panel).
3. Comprueba que el fichero no es accesible desde el navegador: `https://<dominio>/api/config.php` debe dar 403. Lo bloquea `api/.htaccess`.

## 5. Certificado SSL

1. Activa el certificado SSL del dominio desde el panel de control del hosting.
2. Espera a que se emita y comprueba que `https://<dominio>` carga sin avisos del navegador.
3. Entonces, en `public_html/.htaccess`, descomenta las 3 líneas de la redirección a HTTPS. (En el repositorio están comentadas a propósito: si se activan antes de tener el certificado, la web deja de cargar.)

## 6. Lista de comprobación tras cada despliegue

- [ ] `https://<dominio>/api/health` devuelve `{"status":"ok"}`.
- [ ] `https://<dominio>/api/no-existe` devuelve solo `{"error":"Ruta no encontrada"}` (404), sin avisos de PHP ni rutas del servidor.
- [ ] `https://<dominio>/api/products` devuelve los productos en JSON. *(Falla hasta que se suba `api/http/products.php`, que aún no está en el repositorio.)*
- [ ] La página principal, el catálogo y la ficha de producto se ven bien; las imágenes cargan. *(Pendiente de que estén hechas esas páginas.)*
- [ ] Recargar una página interna no da error 404 (por `HashRouter`).
- [ ] Se puede hacer una compra de prueba y el pedido aparece en la administración.
- [ ] Login de administrador con el usuario de prueba del README.
- [ ] El candado de HTTPS aparece y `http://` redirige a `https://`.
- [ ] Aviso "Prototipo académico, sin actividad comercial real" visible.
- [ ] `config.php` no está en GitHub ni es accesible por URL.

## Problemas frecuentes

| Síntoma | Causa probable |
|---|---|
| `/api/...` devuelve 500 | `config.php` no existe o los datos de MySQL son incorrectos. Mira el log de errores de PHP en el panel. |
| La API da 500 en todas las rutas nada más subirla | La versión de PHP es anterior a 8.1 (ver «Requisitos del hosting»). |
| Los formularios (checkout o soporte) dan 500 | Falta la extensión `mbstring` o `pdo_mysql` en PHP. |
| `/api/...` devuelve 404 | No se subió `api/.htaccess` (es un fichero oculto) o falta `mod_rewrite`. |
| La web no carga tras activar la redirección | Se descomentó antes de activar el SSL. Vuelve a comentar las líneas. |
| Pantalla en blanco | Se subió la carpeta `dist` en vez de su contenido, o falta `index.html` en `public_html/`. |
| Se ven datos viejos | Caché del navegador: recarga forzada (`Ctrl+F5`). |
