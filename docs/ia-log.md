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

## 2026-10-06 · José (B) · Añade db/schema.sql con el esquema de la base de datos
Claude Code
- **Qué pedí:** pasar `schema.sql` a los nombres en inglés del contrato del plan y añadir a `products` los campos del catálogo de cartas (tipo de producto, edición, estado de conservación, idioma, ilustrador y foil).
- **Qué falló o hubo que corregir:** la columna `condition` que proponíamos es palabra reservada de MySQL y habría dado error, así que se llama `card_condition`. Los estados de pago estaban como `aprobado`/`rechazado` y el plan dice `approved`/`rejected`. Los estados del pedido se dejaron en castellano porque así los fija el plan.
- **Cómo lo validé:** comprobé a mano que los nombres coinciden con la tabla 2.1 del plan y con `logEvent()` (`events`, `entity_type`, `payload`...).

## 2026-10-06 · José (B) · Feature/catalogo
Claude Code
- **Qué pedí:** el `seed.sql` con 4 categorías (una por juego), 11 productos, un administrador de prueba y 4 pedidos de ejemplo en distintos estados, con clientes, líneas, pagos e historial, y totales que cuadren con las reglas del plan.
- **Qué falló o hubo que corregir:** el script para calcular los totales se hizo primero en Python, que no estaba instalado, y se rehízo en PHP con importes en céntimos para evitar errores de redondeo. Los nombres de cartas, ediciones, ilustradores y precios son orientativos y hay que contrastarlos con fuentes reales. Para Riftbound solo hay un sobre porque no tenía datos fiables de cartas sueltas. Las imágenes son un placeholder porque las ilustraciones reales tienen derechos de autor.
- **Cómo lo validé:** recalculé a mano los totales de los 4 pedidos (IVA 21 %, envío de 4,95 € gratis desde 50 €, cupón BIENVENIDA10) y coinciden con el script; comprobé con `password_verify()` de PHP que el hash corresponde a la contraseña del README.

## 2026-10-06 · José (B) · Feature/catalogo estilo - CORRECIONES
Claude Code
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

## 2026-10-09 · Celia (A) · Entorno local y API del flujo de compra (pasos 1 a 6)
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:**
  1. Instalar PHP 8.3 y MariaDB 11.8 en local, crear la base de datos con `schema.sql` y `seed.sql` y el `api/config.php`.
  2. Mover `readJsonBody()` a `api/lib/request.php` (acordado con César).
  3. `POST /api/events` para los eventos del carrito y del checkout.
  4. `PriceCalculator` y `POST /api/checkout/quote`.
  5. `OrderService` y `POST /api/orders`.
  6. `PaymentService` y `POST /api/orders/{code}/pay` (pago simulado con tarjeta de prueba o Bizum).
  7. `GET /api/orders/{code}` para la página de confirmación, sin datos personales del cliente porque el código del pedido se puede adivinar.
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
  - Confirmación: `GET /api/orders/PED-000002` devuelve productos y totales sin nombre, email ni dirección; un código que no existe (o con SQL inyectado) da 404.
  - Los eventos `cart.item_added` y `checkout.started` ya se guardan en la tabla `events` (lo que quedaba pendiente el 29-09).
  - Al terminar, la base de datos se restauró con `schema.sql` y `seed.sql`.

## 2026-10-08 · José (B) · Quitar enlace público a Administración del pie
Claude Code
- **Qué pedí:** ayuda para revisar el PR de Celia (carrito) y el de César (administración), redactar mis comentarios en ellos y, a petición de César, quitar del pie (`Footer.jsx`) el enlace público "Administración", de modo que se entre directamente por `#/admin`.
- **Qué falló o hubo que corregir:** en la revisión de los PR la herramienta solo leyó el código y no pudo ejecutarlo (sin Node en su entorno), así que las pruebas con `npm run dev` las hago yo. En el cambio del pie, la primera edición unió dos líneas porque el fichero usa saltos de línea CRLF y hubo que corregirla. También detectó que el contenedor del `Layout` y el `Container` de mis páginas se duplican, y que `order_items` no guarda estado, idioma ni foil; decidí no cambiar la tabla porque cada variante de carta es un producto distinto.
- **Cómo lo validé:** con `git diff`, comprobando que el único cambio funcional es la eliminación del enlace y que la ruta `/admin` sigue en `App.jsx`.

## 2026-10-09 · José (B) · Feature/catalogo imagenes
Claude Code
- **Qué pedí:** revisar si el catálogo tenía imágenes de producto y al menos 3 cartas con varias versiones (nueva, usada, foil), cada una con su imagen, y generar las imágenes que faltaban para todas las cartas sueltas de la base de datos.
- **Qué falló o hubo que corregir:** la revisión mostró que los 11 productos usaban el mismo `placeholder.svg` y que solo Lightning Bolt tenía dos versiones. La herramienta generó 12 ilustraciones SVG propias de cartas con un script en PHP (3 versiones de Sol Ring, Lightning Bolt y Dark Magician, y una de Pikachu, Mewtwo y Blue-Eyes) y después 4 más para los sobres. Son ilustraciones originales con nombres inventados, no las cartas ni los sobres oficiales, por los derechos de autor. 
  Las imágenes son horizontales (4:3) porque la tarjeta del catálogo recorta a ese formato. Un primer intento del script de sobres falló porque la función se llamaba `pack`, que ya existe en PHP, y se renombró. Para tener 3 cartas con 3 versiones cada una y no pasar del máximo de 15 productos del plan, se añadieron 5 productos (ids 12 a 16) y se quitó el sobre de Bloomburrow (id 4, que no aparecía en ningún pedido) junto con su imagen. Los ids existentes no se renumeraron para no tocar los pedidos de ejemplo. Además, los tres productos que no indicaban su estado se renombraron (`Sol Ring (nueva)`, `Lightning Bolt (usada)`, `Dark Magician (usada)`) porque el nombre se copia en `order_items`, y se actualizaron también las líneas de los pedidos de ejemplo. Tras la revisión de César, los teléfonos del seed tenían forma de móvil español real (600111222, 611222333, 622333444); se sustituyeron por `000000001`, que no es de ningún país, y `NULL` en los demás clientes, porque el campo es opcional. En España no hay un rango de teléfonos reservado para ficción, y números como 666666666 podrían existir, por lo que se descartaron.
- **Cómo lo validé:** comprobé que los 12 SVG son XML válido y que cada ruta del `seed.sql` apunta a un fichero existente. Las imágenes las reviso yo en el navegador.
## 2026-10-09 · César (C) · Soporte, incidencias y eventos
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** completar mi módulo (días 08 y 09/10):
  1. Ajustes de `OrderStatusService` para el pago de Celia (PR #9): `admin_id` opcional, uso de una transacción ya abierta, devolución de stock al cancelar y primera fila del historial.
  2. Soporte: `POST /api/support` con motivos de una tienda de cartas y evento `support.requested`, formulario público y lista de incidencias en la administración.
  3. Eventos: `GET /api/admin/events` y exportación a CSV y JSON, y el visor en el panel.
  4. Navegación del panel (Pedidos, Incidencias, Eventos) sin tocar `App.jsx`.
- **Partes asistidas por IA:** `api/http/support.php`, `api/http/events.php`, `api/services/SupportService.php`, `api/services/EventService.php`, `api/services/OrderStatusService.php`, `api/repositories/SupportTicketRepository.php`, `api/repositories/EventRepository.php`, `api/repositories/AdminOrderRepository.php` y en `frontend/src/pages/`: `support/` (`SupportPage`, `supportApi.js`, `support.css`) y `admin/` (`SupportTicketsPage`, `EventsPage`, `AdminPagination`, cambios en `AdminPage`).
- **Errores detectados en la IA:**
  - Usó `lines` como alias de una subconsulta y es palabra reservada de MySQL; se cambió a `sold` antes de probar.
  - Declaró un componente de React dentro de otro (se recreaba en cada render); se cambió por una función.
  - Al editar el CSV con `sed` metió un salto de línea literal dentro de una cadena de PHP; se detectó al revisar el fichero y se corrigió.
  - En una prueba, el formulario de soporte pareció fallar: era la propia prueba, porque `curl` en Windows envía mal las tildes escritas en la línea de comandos. Se repitió generando el JSON con PHP.
- **Cambios hechos por mí:**
  - Elegí que la primera fila del historial la guarde `OrderStatusService` (`recordCreated()`) para que Celia no toque mi repositorio, y acepté como limitación que el stock de los pedidos del seed no cuadre al cancelarlos, para no cargar a los compañeros.
  - Decidí que cambiar el estado de un ticket quede como sugerencia para el grupo y no se implemente sin acordarlo.
  - Pedí trabajar soporte y eventos en una rama aparte (`feature/soporte`) para no mezclarlo con el PR #9 que Celia estaba revisando.
  - Tras la revisión de José (10/10): los helpers de la URL pasan a `lib/query.php` para no depender del orden de carga de `http/`, y el formulario habla de la página de confirmación, porque no enviamos emails.
- **Cómo lo validé:**
  - `OrderStatusService` con un script que lo usa como lo hará el pago: devolución de stock desde pagado e incidencia, producto repetido en dos líneas, commit y rollback de una transacción de fuera y `recordCreated()`.
  - Soporte con `curl`: ticket vinculado solo si el email coincide con el del pedido, misma respuesta si no existe, errores por campo (422), inyección en el código de pedido y atomicidad (con `events` renombrada, ningún ticket guardado).
  - Eventos con `curl`: filtros, cabeceras de descarga, BOM y CRLF del CSV, payload aplanado en columnas e inyección de fórmulas neutralizada (`=HYPERLINK(...)` sale como texto).
  - Todas las pantallas en el navegador y `npm run build` sin errores; base de datos restaurada con el seed tras cada prueba.

## 2026-10-09 · José (B) · Imágenes de producto y cartas con varias versiones
Claude Code
- **Qué pedí:** revisar si el catálogo tenía imágenes de producto y al menos 3 cartas con varias versiones (nueva, usada, foil), cada una con su imagen, y generar las imágenes que faltaban para todas las cartas sueltas de la base de datos.
- **Qué falló o hubo que corregir:** la revisión mostró que los 11 productos usaban el mismo `placeholder.svg` y que solo Lightning Bolt tenía dos versiones. La herramienta generó 12 ilustraciones SVG propias de cartas con un script en PHP (3 versiones de Sol Ring, Lightning Bolt y Dark Magician, y una de Pikachu, Mewtwo y Blue-Eyes) y después 4 más para los sobres. Son ilustraciones originales con nombres inventados, no las cartas ni los sobres oficiales, por los derechos de autor. Las imágenes son horizontales (4:3) porque la tarjeta del catálogo recorta a ese formato. Un primer intento del script de sobres falló porque la función se llamaba `pack`, que ya existe en PHP, y se renombró. Para tener 3 cartas con 3 versiones cada una y no pasar del máximo de 15 productos del plan, se añadieron 5 productos (ids 12 a 16) y se quitó el sobre de Bloomburrow (id 4, que no aparecía en ningún pedido) junto con su imagen. Los ids existentes no se renumeraron para no tocar los pedidos de ejemplo. Además, los tres productos que no indicaban su estado se renombraron (`Sol Ring (nueva)`, `Lightning Bolt (usada)`, `Dark Magician (usada)`) porque el nombre se copia en `order_items`, y se actualizaron también las líneas de los pedidos de ejemplo.
- **Cómo lo validé:** comprobé que los 12 SVG son XML válido y que cada ruta del `seed.sql` apunta a un fichero existente. Las 15 imágenes las abrí yo una a una en el navegador con `npm run dev` y se ven correctamente.

## 2026-10-11 y 12 · Celia (A) · Checkout tipo Shopify, facturación y confirmación
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code, y la extensión Claude in Chrome para las pruebas.
- **Tarea:**
  1. Un primer formulario de checkout con validación (11-10). No se llegó a subir: lo sustituyó el siguiente.
  2. Rehacer el checkout copiando la estructura del de Shopify: cabecera propia sin menú, formulario a la izquierda (contacto, entrega, envío, pago y facturación) y resumen del pedido a la derecha con el código de descuento; en el móvil el resumen sale arriba y plegado.
  3. Pago con tarjeta de prueba o Bizum en la misma página; si se rechaza, se reintenta sobre el mismo pedido.
  4. Guardar una dirección de facturación distinta: columnas `billing_*` nuevas en `orders` (cambio del `schema.sql` de José, avisando a José y César).
  5. Página de confirmación del pedido y vaciar el carrito al pagar.
- **Partes asistidas por IA:** `db/schema.sql` (columnas de facturación), `api/services/OrderService.php`, `api/repositories/OrderRepository.php`, `api/http/orders.php`, `frontend/src/App.jsx` y en `frontend/src/pages/checkout/`: `CheckoutLayout.jsx`, `CheckoutPage.jsx`, `OrderSummary.jsx`, `ConfirmationPage.jsx`, `checkoutApi.js` y `checkout.css`; además `clearCart()` en `CartContext.jsx` y exportar `getSessionId()` de `lib/events.js`.
- **Errores detectados en la IA:**
  - En el primer formulario, cuando el código postal mostraba su error en dos líneas, el campo "Ciudad" de al lado se estiraba. Se vio en las capturas y se corrigió.
  - Para validar la facturación reutilizaba la validación del cliente inventándose un email. Se cambió por una función común de dirección.
  - Una prueba con `curl` fallaba con "JSON no válido" porque la consola de Windows mandaba mal la tilde de "Polígono". Era un fallo de la prueba, no del código.
  - En la prueba en Chrome se vio que, si tras un pago rechazado se cambia algún dato (por ejemplo la facturación), se crea un pedido nuevo y el anterior se queda "pendiente de pago" reservando stock. Se corrigió: el pedido nuevo cancela el anterior (con el `OrderStatusService` de César, que devuelve el stock), solo si sigue sin pagar y es del mismo email.
  - En la primera versión de esa corrección, la cancelación iba después de comprobar el stock, así que una carta con stock 1 reservada por el pedido viejo habría dado "sin stock". Se puso la cancelación antes.
- **Cambios hechos por mí:**
  - Pedí copiar el checkout de Shopify y quitar el menú y el pie del checkout para que el cliente no tenga por dónde salirse a mitad de la compra.
  - Decidí guardar la dirección de facturación en la base de datos en lugar de usar siempre la de envío.
  - Pedí ver las pruebas en mi navegador e instalé la extensión de Chrome para ello.
  - Decidí no subir el primer formulario y pasar directamente al nuevo.
  - Ante el pedido que se quedaba pendiente, elegí que el nuevo cancele el anterior en lugar de dejarlo como limitación.
  - Revisé con la IA el enunciado y el plan: el pago puede ser simulado (no hace falta PayPal) y no se pide enviar correo; se deja como limitación.
- **Cómo lo validé:**
  - `npm run build` y `php -l` sin errores.
  - En mi Chrome: con todo vacío salen los errores; el cupón "GRATIS" se rechaza y BIENVENIDA10 deja el total en 27,93 € (igual que el pedido 2 del seed); la tarjeta 4000… se rechaza con su mensaje; con la 4242… y otra dirección de facturación se paga y lleva a "¡Gracias, Laura!". En la base de datos quedan el pedido pagado con su facturación, los dos pagos, el historial y los eventos, y el carrito queda vacío.
  - La vista de móvil, con capturas a 390 px de ancho.
  - Sustitución del pedido: con `curl`, otro email no puede cancelar el pedido de Laura y el mismo email sí, y 2 Mewtwo (todo el stock) reservados por el pedido viejo se pueden volver a comprar en el nuevo. En Chrome: pago rechazado, cambio de facturación y pago aprobado; en la administración el primer pedido sale "Cancelado" y el segundo "Pagado", y el stock solo baja una vez.

## 2026-10-13 · Celia (A) · Correcciones de la revisión de la PR #12
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** leer las revisiones de César y José de la PR #12, decidir qué se arreglaba en el código y qué iba a las limitaciones de la memoria, y hacer los arreglos.
- **Partes asistidas por IA:** el análisis de los comentarios, `api/services/PaymentService.php`, `api/services/PriceCalculator.php` y `api/http/orders.php`.
- **Errores detectados en la IA:** los tres arreglos corrigen fallos del código que había hecho la IA y que encontraron mis compañeros:
  - El evento `payment.simulated` se guardaba después de `order.status_changed` (lo vio César).
  - El cálculo del IVA dividía entre el subtotal, que podía ser 0 € (lo vio César).
  - Las rutas de crear y pagar pedidos no capturaban `OrderStatusException`, que habría dado un 500 (lo vio José).
- **Cambios hechos por mí:** decidí arreglar esos tres puntos y dejar como limitaciones de la memoria: un cliente nuevo por pedido (la fila guarda la dirección de envío de ese pedido), `POST /api/events` sin límite de peticiones, poder cancelar un pedido sin pagar conociendo su código y su email, los pedidos pendientes que reservan stock sin caducidad y que el número de tarjeta llega entero al servidor aunque no se guarda.
- **Cómo lo validé:** `php -l`; con `curl` contra la base de datos local: los eventos del pago salen en orden (pedido, pago y cambio de estado), un carrito a 0 € da 200 con IVA 0 y los totales del seed no cambian (27,93 y 62,32), y el flujo de pedido, pago rechazado, pago aprobado y pagar dos veces (409) sigue igual. Un pedido ya pagado no se cancela aunque otro intente sustituirlo. Al terminar se restauró la base de datos con el seed.

## 2026-10-13 · José (B) · Plataforma: guía de despliegue en DonDominio y README
Claude Code
- **Qué pedí:** preparar mi parte de "Plataforma" del plan: la documentación del despliegue en DonDominio (FTP, importación de la base de datos en phpMyAdmin y certificado SSL) y completar el README. Después, preparar un PR con todos los cambios pendientes que tenía sin subir.
- **Cambios aplicados:**
  - `docs/despliegue.md`: guía paso a paso (base de datos, `npm run build`, subida por FTP, `config.php` solo en el servidor, SSL, lista de comprobación y problemas frecuentes).
  - `README.md`: sección "Despliegue en DonDominio" con el resumen, y número de productos del seed actualizado (15).
  - `frontend/public/.htaccess`: redirección a HTTPS, comentada a propósito para activarla solo cuando el SSL esté emitido.
  - `db/seed.sql` y `frontend/public/products/`: las imágenes y los productos de la entrada anterior, que estaban sin subir.
- **Qué falló o hubo que corregir:**
  - La herramienta no conoce los nombres exactos de las secciones del panel de DonDominio, así que la guía está escrita en términos generales y la ajusto yo cuando lo haga en el panel. La herramienta no recibió ninguna contraseña ni dato de acceso del hosting.
  - Mi rama `feature/catalogo` estaba obsoleta: tenía 4 commits de estilo que ya entraron en `develop` por otras ramas y al fusionarla daban 9 conflictos (`Layout`, `Header`, `Footer`, `theme.css`, etc.). Decidí no arrastrarla y preparar el PR desde una rama nueva, `feature/catalogo-despliegue`, creada desde `develop`.
  - Al pasar los cambios a la rama nueva hubo un conflicto en `docs/ia-log.md`, porque `develop` lo había ampliado mucho. Me quedé con la versión de `develop` y volví a añadir mis entradas al final.
  - Al guardar los cambios con `git stash` falló el borrado de la carpeta `frontend/public/products/` (permiso denegado), pero el guardado estaba completo. Lo comprobé antes de seguir (20 ficheros) y había hecho una copia aparte.
- **Cómo lo validé:** con `git merge-tree` comprobé los conflictos antes de elegir la rama, con `git stash show` que no se perdía ningún fichero, y con `git status` y `git diff` que el PR contiene solo estos cambios. Las 15 imágenes se ven correctamente en el navegador (`npm run dev`), abriendo cada una por su enlace directo; el catálogo todavía no las muestra porque `CatalogPage` está vacía, y eso se hace en el siguiente PR. `npm run build` termina sin errores y `dist/` incluye `.htaccess`, `index.html`, `assets/` y las 15 imágenes de `products/`. **Pendiente de validar por mí:** probar el despliegue real en DonDominio con la lista de comprobación de `docs/despliegue.md`.

## 2026-10-13 · José (B) · Revisión del PR #13 y ajustes de la guía de despliegue
Claude Code
- **Qué pedí:** revisar el PR #13 (despliegue) tras el comentario de un compañero, que advertía de que repetía el seed y las imágenes del #11 con los teléfonos antiguos, y aplicar sus ajustes a `docs/despliegue.md`.
- **Qué falló o hubo que corregir:**
  - El #13 incluía las 15 imágenes y el `seed.sql` del #11, pero con los teléfonos con forma de móvil real (600111222, 611222333, 622333444), porque lo preparé desde `develop` antes de corregirlos en el #11. Como el seed y las imágenes ya van en el #11, se quitaron del #13 (el seed queda igual que en `develop`, con lo que no revierte nada) y se devolvió en el README el recuento de productos al de `develop`; el #13 queda solo con el despliegue. Las entradas anteriores de este diario que citan el seed y las imágenes describen el proyecto entero, que llega por el #11.
  - En `docs/despliegue.md`: la tabla se llama `products`, no `productos`; la comprobación de `/api/products` y la del catálogo fallan hasta que existan `api/http/products.php` y las páginas, y ahora se avisa de ello; se añadió una sección de requisitos del hosting con PHP 8.1 o superior (el código usa `new` como valor por defecto en constructores, propiedades `readonly` y `array_is_list`), las extensiones `pdo_mysql` y `mbstring` y `display_errors` desactivado; y se añadió a la lista de comprobación que una ruta inexistente devuelva solo JSON. También dos filas nuevas en «Problemas frecuentes».
- **Cómo lo validé:** comprobé en el código de `develop` que `api/index.php` responde un JSON 500 genérico a las excepciones y que `lib/router.php` devuelve `{"error":"Ruta no encontrada"}` con 404; que `api/.htaccess` bloquea `config.php`; que no existe `api/http/products.php`; y que las funciones de PHP 8.1 citadas están en el código. Con `git diff` comprobé que el seed no cambia respecto a `develop`. **Pendiente de validar por mí:** el despliegue real en DonDominio y la versión de PHP y las extensiones de mi plan.

## 2026-10-14 · Celia (A) · Prueba con el catálogo y borradores de memoria y vídeo
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:**
  1. Revisar lo que han subido José y César y comprobar que el catálogo de José encaja con mi carrito y mi checkout.
  2. Borrador de mi sección de la memoria y del guion de mi parte del vídeo (los guardo fuera del repositorio hasta pasarlos a la memoria final).
- **Partes asistidas por IA:** la revisión de las ramas, la prueba de integración y el texto de los dos borradores, que tengo que repasar y adaptar con mis palabras.
- **Errores detectados en la IA:**
- **Cambios hechos por mí:**
- **Cómo lo validé:** la extensión de Chrome estaba desconectada, así que la prueba se hizo con un script que abría Microsoft Edge en mi pantalla. En una copia aparte con `develop` + `feature/catalogo-productos` + mis correcciones (sin subir nada), compra completa desde el catálogo: ficha, añadir al carrito, panel, checkout con cupón, pago rechazado, pago aprobado y confirmación (PED-000005, 27,93 €), sin errores en la consola y con el carrito vacío al final. Se vio que el catálogo muestra los precios con IVA y el resumen del checkout las líneas sin IVA (va a las limitaciones).

## 2026-10-15 · Celia (A) · Revisión de la web publicada, rama al día, DonDominio y nuevo nombre
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:**
  1. Revisar la web publicada en https://www.theblacklotustcg.onl/ sin crear pedidos (páginas, API, ficheros bloqueados, certificado).
  2. Poner mi rama al día con `develop`.
  3. Investigar si DonDominio ofrece envío de correo y pasarela de pago.
  4. Cambiar el nombre de la tienda a theblacklotustcg en el título y en las páginas, y usar el loto negro como favicon y logo.
  5. Quitar del checkout el aviso con las tarjetas de prueba (quedan solo en el README).
  6. Dar margen lateral a los productos del panel del carrito.
  7. Aviso "Añadido al carrito" bajo el icono de la cesta: dura 2 segundos y tiene un botón "Ver carrito" que lo cierra y abre el panel. La ficha de producto deja de abrir el panel directamente al añadir.
  8. Comprobar la gestión del stock en el carrito y corregirla.
- **Partes asistidas por IA:** la revisión, la unión con `develop`, la búsqueda sobre DonDominio, `frontend/public/favicon.svg` y `logo.svg` (a partir del SVG del loto que pasé yo), `index.html`, `Header.jsx`, `Footer.jsx`, `CheckoutLayout.jsx`, `HomePage.jsx`, `home.css`, `ProductPage.jsx` y `components.css`.
- **Errores detectados en la IA:**
  - Con el nombre nuevo, el título grande del inicio no cabía en el móvil y la cabecera mostraba "theblacklotus tcg" separado. Se vio en las capturas y se corrigió.
  - En el CSS del panel del carrito, `.cart-list` ponía el `padding` a 0 y anulaba el margen lateral de `.cart-drawer__body`, así que los productos quedaban pegados al borde (lo vi yo). Ahora solo quita el de arriba y abajo.
  - El carrito no comprobaba el stock: se podía añadir el Mewtwo (stock 2) tres veces y subirlo con "+" sin límite. El servidor sí lo frenaba en el checkout ("No hay stock suficiente") y no dejaba pagar, pero el cliente se enteraba tarde. Ahora el carrito guarda el stock de cada producto, no deja pasar de él al añadir ni con "+" y avisa con "No quedan más unidades".
- **Cambios hechos por mí:**
  - Decidí que la tienda se llame theblacklotustcg, como el dominio, y pasé el dibujo del loto para el favicon.
  - Decidí que las tarjetas de prueba no salgan en la web, solo en la documentación.
  - Pedí el aviso "Añadido al carrito" con el botón "Ver carrito".
- **Cómo lo validé:**
  - Web publicada: todas las páginas cargan sin errores en ordenador y móvil, la API responde, `http` redirige a `https`, `config.php` da 403 y el checkout calcula lo mismo que en local (30,48 €). Se vio que `https://theblacklotustcg.onl` sin `www` da un certificado de DonDominio en vez del nuestro (hay que arreglarlo en el panel).
  - DonDominio: el plan incluye cuentas de correo y se puede enviar por SMTP con una cuenta del dominio, pero no tiene pasarela de pago para la tienda (el Redsys que usan es para cobrar sus servicios). Se queda el pago simulado.
  - Nombre y favicon: capturas en ordenador y móvil y el favicon en tema claro y oscuro; el título de la pestaña cambia también en la ficha de producto. `npm run build` sin errores.
  - Aviso del carrito: con un script en Edge, en ordenador y móvil, sale al añadir desde el catálogo y desde la ficha, se va a los 2 segundos y "Ver carrito" abre el panel. Al principio no salía porque el servidor de desarrollo de Vite se había quedado con una versión a medio guardar de `CartContext.jsx`; se arregló reiniciándolo.
  - Stock: Mewtwo (stock 2) añadido 3 veces → la tercera sale "No quedan más unidades" y el carrito se queda en 2; el "+" queda desactivado en 2; un carrito antiguo con 4 unidades da el error en el checkout y no deja pagar, y al bajar a 2 se puede pagar.
## 2026-10-15 · Celia (A) · Correo de confirmación del pedido
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** en la rama `feature/correo-confirmacion` (desde `develop`), enviar un correo con el resumen del pedido cuando se aprueba el pago, por SMTP con una cuenta del dominio de DonDominio.
- **Partes asistidas por IA:** `api/services/MailService.php`, el envío al final de `PaymentService.php`, `findContact()` en `OrderRepository.php`, el bloque `mail` de `config.example.php`, el aviso en `ConfirmationPage.jsx` y `CheckoutPage.jsx`, y el apartado del README. PHPMailer 7.1.1 se descargó del repositorio oficial a `api/lib/PHPMailer/`.
- **Errores detectados en la IA:**
- **Cambios hechos por mí:**
  - Decidí usar PHPMailer en lugar de escribir el envío SMTP a mano.
  - Pedí que el correo fuera más bonito (cabecera con el nombre de la tienda, botón "Ver mi pedido") y que llevara la foto pequeña de cada producto. Para eso `findSummary()` devuelve también `image_url` de cada línea.
- **Cómo lo validé:**
  - Con el correo desactivado (como en local), el pago funciona igual y devuelve `email_sent: false`.
  - Con un servidor SMTP falso en mi ordenador (guarda el mensaje y no lo envía), el correo sale con remitente, destinatario, asunto, versión de texto y HTML, productos, descuento, totales (27,93 €) y enlace al pedido. Falta probarlo con el buzón real de DonDominio.
  - Vi el diseño nuevo con un pedido inventado en escritorio y en móvil. En el móvil el símbolo € se bajaba de línea; se arregló con un espacio que no se corta.
  - Las fotos de los productos eran SVG y Gmail y Outlook no muestran imágenes SVG en los correos. Decidí pasarlas a PNG en toda la web: la herramienta las convirtió con el navegador de las pruebas (Playwright, al doble de tamaño), cambió las rutas de `seed.sql` y el `placeholder` de `ProductCard.jsx` y `ProductPage.jsx`, y borró los SVG. El favicon y el logo siguen en SVG porque el navegador sí los muestra.
  - Comprobé que el catálogo carga las 15 fotos en PNG sin ninguna rota, que la ficha de producto también, y que `npm run build` copia los PNG a `dist/products/`. En mi base de datos local cambié las rutas con `UPDATE products SET image_url = REPLACE(image_url, '.svg', '.png')`.
