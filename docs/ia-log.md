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
