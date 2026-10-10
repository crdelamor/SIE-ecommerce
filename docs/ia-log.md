# Diario de uso de IA

Apuntad aquí cada uso importante de IA. De este fichero sale el anexo de IA de la memoria, que según el enunciado (apartado 7) debe indicar: herramientas, tareas, partes asistidas, errores de la IA, cambios hechos por el grupo y cómo se validó.

```
## AAAA-MM-DD · Persona (A/B/C) · Tema
- **Herramienta:**
- **Tarea:**
- **Partes asistidas por IA:**
- **Errores detectados en la IA:**
- **Cambios hechos por mí:**
- **Cómo lo validé:**
```

---

## 2026-09-29 · Celia (A) · Fase 0: estructura inicial
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** montar la fase 0: estructura del repositorio, React + Vite con HashRouter y páginas vacías, esqueleto de `useCart().addItem()`, `api/index.php` con enrutador, conexión PDO, `logEvent()`, `config.example.php` y `.gitignore`.
- **Partes asistidas por IA:** `api/index.php`, `api/lib/` (`db.php`, `events.php`, `response.php`, `router.php`), `api/.htaccess`, `frontend/` (configuración de Vite, `App.jsx`, `Layout.jsx`, `CartContext.jsx`, páginas vacías), `README.md`.
- **Errores detectados en la IA:**
  - La primera versión incluía cosas que no eran de la fase 0 (estructura base del ecommerce y base del carrito). Le pedí que lo recortara a la estructura mínima.
  - Firmó los commits con un correo que no era el mío. Lo corregí para que la autoría en GitHub sea `crdelamor <crdelamor@alu.ucam.edu>`.
- **Cambios hechos por mí:** alcance limitado a la fase 0, un único commit "Estructura inicial del proyecto" y el proyecto movido a la carpeta `ECOMMERCE-t1`.
- **Cómo lo validé:** `php -l` en los ficheros PHP, petición a `/api/health` con el servidor de PHP (200) y a una ruta inexistente (404), y `npm run build` sin errores.

## 2026-09-29 · César (C) · Claude Code
- **Qué pedí:** revisar la fase 0 frente a los contratos del plan de trabajo, preparar mi entorno local (Node, PHP 8 con `pdo_mysql`, MySQL, rama `feature/admin`), proponer el plan de mi módulo (administración, estados, soporte, eventos y seguridad) y crear los ficheros vacíos con los que voy a trabajar.
- **Qué falló o hubo que corregir:**
  - Configuró `php.ini` con la ruta completa de las extensiones; como mi carpeta de usuario lleva tilde, PHP no cargaba `pdo_mysql`. Se corrigió usando la ruta corta de Windows (sin tildes).
  - Creó la rama `feature/admin` siguiendo a `origin/main`, con lo que un `push` podía apuntar a `main`. Se quitó ese seguimiento.
  - Una prueba con `php -r` falló por cómo PowerShell trata las comillas; se hizo con un script aparte.
  - El prompt inicial pedía Node 18, pero Vite 8 necesita Node 20.19 o superior: la IA lo detectó y se instaló la LTS actual.
- **Cambios hechos por mí:** decidí seguir el formato de este diario, esperar al grupo en lo que depende de A y B (`schema.sql`/`seed.sql`, subrutas `admin/*` en `App.jsx`, cabecera `X-Session-Id`) y dejar las pantallas sin maquetar hasta conocer la temática. Corregí el usuario de MySQL local que había indicado.
- **Cómo lo validé:** `php -m` muestra `pdo_mysql`; `/api/health` responde `{"status":"ok"}` directo y a través del proxy de Vite; la conexión PDO a la base de datos local funciona; `git check-ignore` confirma que `api/config.php` no se sube; `php -l` sin errores en los ficheros nuevos y `npm run build` correcto.

## 2026-09-29 · Celia (A) · Carrito, eventos del carrito y estilo general
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** los dos primeros puntos de mi parte del frontend:
  1. Carrito en React (contexto + `localStorage`): añadir, quitar, cambiar cantidad y contador en la cabecera.
  2. Enviar `cart.item_added` al añadir un producto y `checkout.started` al entrar en el checkout.

  Además, el carrito como panel lateral que se abre con `?isCartOpened=1` en la URL, y unos estilos generales siguiendo el apartado 9 del plan de trabajo.
- **Partes asistidas por IA:** `frontend/src/context/CartContext.jsx`, `frontend/src/lib/events.js`, `frontend/src/pages/cart/CartPanel.jsx` y `.css`, `frontend/src/components/Layout.jsx` y `.css`, `frontend/src/components/Icons.jsx`, `frontend/src/styles/theme.css` y `global.css`, `frontend/src/pages/checkout/CheckoutPage.jsx`.
- **Errores detectados en la IA:**
  - Se adelantó a trabajo que no le había pedido (cliente completo de la API, formato de precios y todas las pantallas del checkout). Le paré y le pedí ir poco a poco; se eliminó lo que sobraba.
  - Con el carrito vacío, el botón "Ver el catálogo" se estiraba hasta ocupar toda la altura del panel.
  - La página tenía un ancho máximo de 1200 px en lugar de ocupar toda la pantalla.
  - En la revisión final encontró tres fallos en su propio código:
    - El panel se anunciaba como modal, pero con Tab se podía salir a la página de detrás.
    - El botón del carrito se leía como "1 productos".
    - `checkout.started` se enviaba aunque el carrito estuviera vacío.
- **Cambios hechos por mí:**
  - El carrito es un panel que sale por la derecha y se controla con `?isCartOpened=1`, en lugar de una página `#/carrito`.
  - Con el carrito vacío solo se muestra "Carrito vacío" y el botón "Ver el catálogo".
  - Ancho completo con márgenes laterales de 64 px.
- **Cómo lo validé:**
  - `npm run build` sin errores.
  - Revisión en el navegador (`npm run dev`, `localhost:5173`) con un carrito de prueba metido en `localStorage` desde la consola: así vi los fallos del carrito vacío y del ancho.
  - Pendiente: comprobar que los eventos se guardan en la tabla `events` cuando exista `POST /api/events`.

## 2026-10-05 · César (C) · Claude Code
- **Qué pedí:** revisar las ramas del grupo para ver si se podían mergear y mergear mi PR #1 en `develop`.
- **Qué falló o hubo que corregir:** la IA hizo merges locales de `feature/catalogo` y `feature/admin` directamente en `develop`, saltándose el flujo de PR acordado. No llegaron a subirse y se deshicieron.
- **Cambios hechos por mí:** decidí integrar solo mi rama mediante el PR #1 (aprobado por Celia) y dejar a cada compañero su propio merge.
- **Cómo lo validé:** simulación de merge con `git merge-tree` sin conflictos, `npm run build` y `php -l` correctos, y PR en estado MERGED en GitHub.

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

## 2026-10-07 · César (C) · Login, administración de pedidos y cambio de estado
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** la primera parte de mi módulo, paso a paso y probando cada paso antes del siguiente:
  1. Login y logout de administrador con sesión PHP, y guard de las rutas `/api/admin/*`.
  2. Lista de pedidos con filtros por estado, fechas y búsqueda, y paginación.
  3. Ficha del pedido con cliente, productos (con juego, set, estado, idioma y foil), pagos e historial.
  4. Cambio de estado con validación de transiciones, historial y evento `order.status_changed`.
- **Partes asistidas por IA:** `api/http/admin.php`, `api/services/AuthService.php`, `api/services/OrderStatusService.php`, `api/repositories/UserRepository.php`, `api/repositories/AdminOrderRepository.php` y las pantallas de `frontend/src/pages/admin/` (`AdminPage`, `LoginPage`, `OrdersPage`, `OrderDetailPage`, `OrderStatusActions`, `StatusBadge`, `adminApi.js`, `adminFormat.js`, `admin.css`).
- **Errores detectados en la IA:**
  - El hash ficticio que se comprueba cuando el email no existe tenía un coste distinto al del administrador, así que un email inexistente tardaba el doble y se podía saber qué cuentas existían. Lo detectamos midiendo los tiempos de respuesta y se corrigió.
  - La primera prueba de inyección SQL no llegó a la base de datos porque usaba una herramienta (`jq`) que no estaba instalada y las peticiones iban vacías. Se repitió generando el JSON con PHP.
  - La prueba de dos cambios de estado simultáneos no demuestra el bloqueo `SELECT … FOR UPDATE`, porque el servidor de PHP de desarrollo atiende las peticiones de una en una. La propia IA lo avisó; el bloqueo protege en el hosting real.
  - Detalles de calidad corregidos en la revisión: un `!important` en el CSS y un comentario para ESLint, que el proyecto no tiene configurado.
- **Cambios hechos por mí:**
  - Pedí el filtro por fecha y que la lista fuera profesional; se añadieron búsqueda, paginación en el servidor y filtros guardados en la URL.
  - Decidí que la web no enlace la administración desde el pie (se lo pedí a José, que es el dueño de `Footer.jsx`).
  - Propuse al grupo las transiciones de `incidencia` y el nombre del evento; no subí ese cambio hasta que Celia y José lo aprobaron.
  - Commits con Conventional Commits (`feat(...)`) y sin cambiar ficheros comunes ni de A o B.
- **Cómo lo validé:**
  - Cada endpoint con `curl`: casos correctos y de error (400, 401, 404, 409, 422), inyecciones SQL en el login, los filtros y el id, y la cookie de sesión `HttpOnly` y `SameSite=Strict`.
  - Atomicidad del cambio de estado: con la tabla `events` renombrada a propósito, la API devolvió 500 y el pedido no cambió ni se añadió historial.
  - Todas las pantallas en el navegador (`npm run dev` + `php -S localhost:8000`) y `npm run build` sin errores. Tras las pruebas, la base de datos local se restauró con `schema.sql` y `seed.sql`.

## 2026-10-05 · Celia (A) · Revisión de las PR de José y César
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** revisar las ramas `feature/catalogo`, `feature/db-schema`, `feature/catalogo-estilo`, `feature/footer-sin-admin` y `feature/admin` comparándolas con el plan de trabajo y el enunciado, y preparar los comentarios de las PR.
- **Partes asistidas por IA:** las revisiones
- **Errores detectados en la IA:**
  - La primera revisión mezclaba lo que pide el plan de trabajo con lo que exige el enunciado. Le pedí separarlo para saber qué era obligatorio de verdad.
- **Cambios hechos por mí:** decidí qué puntos llevar a cada PR, pasé el tema de la tienda (cartas TCG) para que José añadiera los campos al esquema y acordé con César cómo encajar su parte con la mía (`readJsonBody()` común, historial al pagar, stock al cancelar).
- **Cómo lo validé:** simulación de los merges con `git merge-tree`, `npm run build` de cada rama y comparación con los dos PDF.

## 2026-10-06 · Celia (A) · Integración con develop, estilo común y nombre de la tienda
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** actualizar mi rama con `develop` resolviendo los conflictos con el estilo de José, poner el pie siempre abajo, márgenes laterales, espacio en los títulos y nombre a la tienda.
- **Partes asistidas por IA:** la resolución de conflictos del rebase, `Layout.jsx`, `Header.jsx`, `CartPanel.jsx` y `.css`, `Icons.jsx`, `theme.css`, `components.css`, `Footer.jsx` e `index.html`.
- **Errores detectados en la IA:**
  - Al integrar, el CSS del panel del carrito usaba 15 variables de mi tema antiguo que no existían en el de José.
  - El panel quedaba por debajo de la cabecera fija de José y hubo que subirlo.
- **Cambios hechos por mí:**
  - Me quedé con el `theme.css` de José y con su botón del carrito; mi commit de estilos generales se quitó.
  - Pedí el pie siempre abajo, márgenes de 64 px y más espacio en los títulos. En el móvil dejé 16 px.
  - La IA propuso el nombre "La Bóveda TCG" y lo acepté.
- **Cómo lo validé:** `npm run build` y prueba en el navegador con `npm run dev`. Hice yo el commit de los ajustes.

## 2026-10-09 · Celia (A) · Entorno local y API del flujo de compra (pasos 1 a 5)
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:**
  1. Instalar PHP 8.3 y MariaDB 11.8 en local, crear la base de datos con `schema.sql` y `seed.sql` y el `api/config.php`.
  2. Mover `readJsonBody()` a `api/lib/request.php` (acordado con César).
  3. `POST /api/events` para los eventos del carrito y del checkout.
  4. `PriceCalculator` y `POST /api/checkout/quote`.
  5. `OrderService` y `POST /api/orders`.
  6. `PaymentService` y `POST /api/orders/{code}/pay` (pago simulado con tarjeta de prueba o Bizum).
- **Partes asistidas por IA:** `api/lib/request.php`, `api/index.php`, `api/http/admin.php` (solo quitar `readJsonBody()`), `api/http/orders.php`, `api/services/PriceCalculator.php`, `api/services/OrderService.php`, `api/services/PaymentService.php`, `api/repositories/OrderRepository.php` y la limpieza de comentarios de `CartContext.jsx`, `events.js` y `CartPanel.jsx`.
- **Errores detectados en la IA:**
  - La instalación de MariaDB falló (código 1603) porque no pedía permisos de administrador. Se repitió pidiéndolos.
  - Escribía comentarios y mensajes de commit demasiado largos, con mucha pinta de IA. Le pedí escribir más sencillo y simplificar los comentarios del carrito.
  - Dejaba este diario para el final en vez de ir apuntando cada paso. Se lo tuve que recordar.
  - `createOrder()` devolvía solo el código del pedido y hacía falta también el id para el historial y el evento.
  - En una prueba llamó a la ruta de cancelar sin `/status` y dio 404. Era un fallo de la prueba, no del código.
- **Cambios hechos por mí:**
  - Elegí las reglas de envío e IVA del `seed.sql` de José (envío gratis desde 50 € sin IVA, el envío no lleva IVA) para que los pedidos de ejemplo cuadren.
  - Decidí ir paso a paso: un commit cada vez y probarlo antes de seguir.
  - Acordé con César reutilizar su `OrderStatusService` para el historial y que él devuelva el stock al cancelar.
- **Cómo lo validé:**
  - `php -l` en todos los ficheros y pruebas con `curl` contra la base de datos local. Las ejecutó la IA y revisé los resultados.
  - El presupuesto da los mismos totales que los 4 pedidos del seed (15,72 / 27,93 / 62,32 / 20,46) y un precio inventado desde el navegador se ignora.
  - Al crear un pedido se guardan el cliente, las líneas, el historial y el evento `order.created`, y baja el stock. Si hay un error (email, código postal, stock) no se guarda nada.
  - Cancelándolo desde la administración de César, el stock vuelve.
  - Pago: con la tarjeta 4000… se guarda el pago rechazado y el pedido sigue en `pendiente_pago`; al reintentar con la 4242… pasa a `pagado`, con su fila en el historial y los eventos `payment.simulated` y `order.status_changed`. Pagar dos veces da 409 y una tarjeta que no es de prueba da 400. Del número de tarjeta solo se guardan los 4 últimos dígitos.
  - Los eventos `cart.item_added` y `checkout.started` ya se guardan en la tabla `events` (lo que quedaba pendiente el 29-09).
  - Al terminar, la base de datos se restauró con `schema.sql` y `seed.sql`.
