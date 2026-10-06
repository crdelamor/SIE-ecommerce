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

## 2026-10-01 · José (B) · Claude Code
- **Qué pedí:** el sistema de estilo y los componentes base (prompt del apartado 9 del plan): `theme.css` con variables, `global.css`, hook `useReveal`, cabecera, pie, botón, tarjeta de producto, banner del aviso de prototipo y una página de muestra.
- **Qué falló o hubo que corregir:** al revisarlo con el grupo salieron varios fallos. La tarjeta mostraba el precio sin IVA mientras que el carrito lo mostrará con IVA, así que ahora usa `price_with_tax` calculado por la API. Un producto sin imagen salía con la imagen rota (ahora usa `placeholder.svg`). Los lectores de pantalla leían el nombre del producto dos veces. Había variables y clases sin usar (`--color-accent-hover`, `--space-7`, `.section--tight`).- **Cómo lo validé:** revisión del código y de la accesibilidad (contraste, `alt`, foco con teclado, vista móvil) y revisión del PR por los compañeros.

## 2026-10-02 · José (B) · Claude Code
- **Qué pedí:** preparar los commits y el pull request con el trabajo de estilo, y redactar la descripción del PR (primero en general y luego en primera persona, como alumno del grupo).
- **Qué falló o hubo que corregir:** pedí hacer el PR hacia `feature/parteB`, pero esa rama no existe en GitHub y el plan asigna a B la rama `feature/catalogo`, así que se usó esa. La herramienta no tiene la CLI `gh`, por lo que el PR hay que abrirlo desde la web de GitHub. Se creó una rama nueva, `feature/catalogo-estilo`, con un único commit y mi autoría, y se subió sin forzar nada; la rama antigua `feature/catalogo` queda sin usar. También avisé de que la rama sale de `feature/db-schema`, así que el PR puede incluir el commit del schema.
- **Cómo lo validé:** comprobé con `git log` el autor del commit y que la rama se subió a GitHub. La descripción del PR la revisé yo antes de pegarla.

## 2026-10-06 · José (B) · Claude Code
- **Qué pedí:** pasar `schema.sql` a los nombres en inglés del contrato del plan y añadir a `products` los campos del catálogo de cartas (tipo de producto, edición, estado de conservación, idioma, ilustrador y foil).
- **Qué falló o hubo que corregir:** la columna `condition` que proponíamos es palabra reservada de MySQL y habría dado error, así que se llama `card_condition`. Los estados de pago estaban como `aprobado`/`rechazado` y el plan dice `approved`/`rejected`. Los estados del pedido se dejaron en castellano porque así los fija el plan.
- **Cómo lo validé:** comprobé a mano que los nombres coinciden con la tabla 2.1 del plan y con `logEvent()` (`events`, `entity_type`, `payload`...).

## 2026-10-06 · José (B) · Claude Code
- **Qué pedí:** el `seed.sql` con 4 categorías (una por juego), 11 productos, un administrador de prueba y 4 pedidos de ejemplo en distintos estados, con clientes, líneas, pagos e historial, y totales que cuadren con las reglas del plan.
- **Qué falló o hubo que corregir:** el script para calcular los totales se hizo primero en Python, que no estaba instalado, y se rehízo en PHP con importes en céntimos para evitar errores de redondeo. Los nombres de cartas, ediciones, ilustradores y precios son orientativos y hay que contrastarlos con fuentes reales. Para Riftbound solo hay un sobre porque no tenía datos fiables de cartas sueltas. Las imágenes son un placeholder porque las ilustraciones reales tienen derechos de autor.
- **Cómo lo validé:** recalculé a mano los totales de los 4 pedidos (IVA 21 %, envío de 4,95 € gratis desde 50 €, cupón BIENVENIDA10) y coinciden con el script; comprobé con `password_verify()` de PHP que el hash corresponde a la contraseña del README.

## 2026-10-06 · José (B) · Claude Code
- **Qué pedí:** un resumen de todos los cambios aplicados y el texto de declaración de uso de IA para la memoria (punto de partida del proyecto), además de completar este diario con el trabajo anterior.
- **Qué falló o hubo que corregir:** la herramienta no sabe si se usó una plantilla de terceros ni cómo se montó la fase 0, así que no los inventó y hubo que completarlos con lo que consta en el repositorio y en la entrada de Celia de este diario.
- **Cómo lo validé:** contrasté el resumen con los ficheros y los commits reales del repositorio.
