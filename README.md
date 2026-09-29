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
