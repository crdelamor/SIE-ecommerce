# Despliegue en DonDominio

Guía para publicar la tienda en el hosting (plan Básico: web estática + PHP + MySQL, sin Node.js). Responsable: B.

Los datos de acceso (servidor FTP, usuario, contraseña, datos de MySQL) están en el correo de bienvenida y en el panel de control del hosting. **No se escriben en este repositorio.**

## Estructura en el servidor

```
public_html/            <- contenido de frontend/dist/ (index.html, assets/, products/, .htaccess)
public_html/api/        <- carpeta api/ del repositorio
public_html/api/config.php   <- SOLO en el servidor (no está en GitHub)
```

## 1. Base de datos

1. En el panel, crea una base de datos MySQL y un usuario con todos los permisos sobre ella. Anota nombre, usuario y contraseña (en el hosting suelen llevar un prefijo).
2. Abre phpMyAdmin, selecciona esa base de datos.
3. Pestaña **Importar**: `db/schema.sql` y, después, `db/seed.sql`.
4. Comprueba en phpMyAdmin que están las 10 tablas y que `productos` tiene datos.

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
- [ ] `https://<dominio>/api/products` devuelve los productos en JSON.
- [ ] La página principal, el catálogo y la ficha de producto se ven bien; las imágenes cargan.
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
| `/api/...` devuelve 404 | No se subió `api/.htaccess` (es un fichero oculto) o falta `mod_rewrite`. |
| La web no carga tras activar la redirección | Se descomentó antes de activar el SSL. Vuelve a comentar las líneas. |
| Pantalla en blanco | Se subió la carpeta `dist` en vez de su contenido, o falta `index.html` en `public_html/`. |
| Se ven datos viejos | Caché del navegador: recarga forzada (`Ctrl+F5`). |
