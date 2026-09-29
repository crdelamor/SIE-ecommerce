# Diario de uso de IA

Apuntad aquí cada uso importante de IA. De este fichero sale el anexo de IA de la memoria.

```
## AAAA-MM-DD · Persona (A/B/C) · Herramienta
- Qué pedí:
- Qué falló o hubo que corregir:
- Cómo lo validé:
```

---

## 2026-09-29 · Celia (A) · Claude Code
- **Qué pedí:** montar la fase 0: estructura del repositorio, React + Vite con HashRouter y páginas vacías, esqueleto de `useCart().addItem()`, `api/index.php` con enrutador, conexión PDO, `logEvent()`, `config.example.php` y `.gitignore`.
- **Qué falló o hubo que corregir:** la primera versión incluía cosas que no eran de la fase 0 (carrito completo, cliente de la API, rutas pendientes de B y C, estilos). Le pedí que lo recortara a la estructura mínima.
- **Cómo lo validé:** `php -l` en los ficheros PHP, petición a `/api/health` con el servidor de PHP y `npm run build` sin errores.

## 2026-09-29 · Cesa (C) · Claude Code
- **Qué pedí:** revisar la fase 0 frente a los contratos del plan de trabajo, preparar mi entorno local (Node, PHP 8 con `pdo_mysql`, MySQL, rama `feature/admin`), proponer el plan de mi módulo (administración, estados, soporte, eventos y seguridad) y crear los ficheros vacíos con los que voy a trabajar.
- **Qué falló o hubo que corregir:**
  - Configuró `php.ini` con la ruta completa de las extensiones; como mi carpeta de usuario lleva tilde, PHP no cargaba `pdo_mysql`. Se corrigió usando la ruta corta de Windows (sin tildes).
  - Creó la rama `feature/admin` siguiendo a `origin/main`, con lo que un `push` podía apuntar a `main`. Se quitó ese seguimiento.
  - Una prueba con `php -r` falló por cómo PowerShell trata las comillas; se hizo con un script aparte.
  - El prompt inicial pedía Node 18, pero Vite 8 necesita Node 20.19 o superior: la IA lo detectó y se instaló la LTS actual.
- **Cambios hechos por mí:** decidí seguir el formato de este diario, esperar al grupo en lo que depende de A y B (`schema.sql`/`seed.sql`, subrutas `admin/*` en `App.jsx`, cabecera `X-Session-Id`) y dejar las pantallas sin maquetar hasta conocer la temática. Corregí el usuario de MySQL local que había indicado.
- **Cómo lo validé:** `php -m` muestra `pdo_mysql`; `/api/health` responde `{"status":"ok"}` directo y a través del proxy de Vite; la conexión PDO a la base de datos local funciona; `git check-ignore` confirma que `api/config.php` no se sube; `php -l` sin errores en los ficheros nuevos y `npm run build` correcto.
