# SIE-ecommerce

Tarea 1 SIE (UCAM). Tienda online con **React + Vite** (front), **API en PHP** y **MySQL**, desplegada en DonDominio.

> Prototipo académico, sin actividad comercial real. Todos los datos son ficticios.

## Estructura

```
frontend/   React + Vite (HashRouter)
  src/components            B
  src/pages/home, catalog, product  B
  src/pages/cart, checkout  A
  src/pages/admin, support  C
api/        API PHP (se sube a public_html/api/)
  index.php, lib/, config.example.php   común
  http/products.php                     B
  http/orders.php, services/, repositories/  A
  http/admin.php, support.php, events.php    C
db/         schema.sql, seed.sql (B)
docs/       ia-log.md (diario de uso de IA)
```

## Arrancar en local

```bash
cd frontend
npm install
npm run dev
```

La API necesita PHP y MySQL: copia `api/config.example.php` como `api/config.php` con tus datos (`config.php` no se sube a GitHub) y arranca `php -S localhost:8000` desde la raíz del repositorio. Vite reenvía `/api` a ese servidor.

## Base de datos

Importa los dos ficheros en este orden, en local (phpMyAdmin o `mysql`) y en el hosting:

1. `db/schema.sql`: crea las tablas (borra las existentes, así que se puede reimportar).
2. `db/seed.sql`: datos de prueba ficticios (4 categorías, 11 productos, 4 clientes, 4 pedidos de ejemplo y el administrador).

## Usuarios de prueba

Datos ficticios, solo para el prototipo académico:

| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | `admin@tcg-sie.example` | `Admin-SIE-2026` |

En la base de datos solo se guarda el hash (`password_hash()` de PHP), nunca la contraseña.

Tarjetas de prueba del pago simulado: `4242 4242 4242 4242` se aprueba y `4000 0000 0000 0002` se rechaza. Cupón de descuento: `BIENVENIDA10`.

## Despliegue en DonDominio

El hosting (plan Básico) solo admite web estática y PHP, sin Node.js. Por eso el front se compila en local y se sube ya construido. La guía paso a paso, con la lista de comprobaciones, está en [`docs/despliegue.md`](docs/despliegue.md). Resumen:

1. **Base de datos.** En el panel del hosting crea una base de datos MySQL y su usuario. En phpMyAdmin importa `db/schema.sql` y después `db/seed.sql`.
2. **Compilar el front.** Desde `frontend/`: `npm run build`. Genera `frontend/dist/`.
3. **Subir por FTP.**
   - El contenido de `frontend/dist/` va a `public_html/`.
   - La carpeta `api/` va a `public_html/api/`, sin `config.php`.
4. **Configuración en el servidor.** Crea `public_html/api/config.php` a partir de `config.example.php` con los datos de MySQL **del hosting** (nombre de la base, usuario y contraseña que te da el panel). Este fichero solo existe en el servidor y nunca se sube a GitHub.
5. **SSL.** Activa el certificado desde el panel. Cuando la web cargue por `https://`, descomenta la redirección a HTTPS de `public_html/.htaccess`.
6. **Comprobar.** Abre `https://<dominio>/api/health` (debe responder `{"status":"ok"}`), el catálogo y el login de administrador.
