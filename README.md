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
2. `db/seed.sql`: datos de prueba ficticios (4 categorías, 15 productos (Sol Ring, Lightning Bolt y Dark Magician en tres versiones cada uno), 4 clientes, 4 pedidos de ejemplo y el administrador).

## Usuarios de prueba

Datos ficticios, solo para el prototipo académico:

| Rol | Email | Contraseña |
|-----|-------|------------|
| Administrador | `admin@tcg-sie.example` | `Admin-SIE-2026` |

En la base de datos solo se guarda el hash (`password_hash()` de PHP), nunca la contraseña.

Tarjetas de prueba del pago simulado: `4242 4242 4242 4242` se aprueba y `4000 0000 0000 0002` se rechaza. Cupón de descuento: `BIENVENIDA10`.
