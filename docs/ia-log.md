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
  - El primer diseño del correo era muy plano y en el móvil el símbolo € se bajaba de línea en los importes. Se rehízo el diseño y se puso un espacio que no se corta antes del €.
  - La herramienta puso las fotos de los productos en el correo sin avisar al principio de que eran SVG, y Gmail y Outlook no muestran SVG en los correos. Lo avisó después, al rehacer el diseño; por eso se pasaron a PNG.
  - No puso ningún tiempo máximo al SMTP: si el servidor no contestaba, el pago se quedaba esperando hasta 300 s. Lo detectó un compañero en la revisión de la PR, y además con solo `Timeout` no bastaba, también hacía falta `Timelimit`. Se corrigió en la PR de `feature/checkout`.
  - Creó la rama `feature/correo-confirmacion` enlazada a `origin/develop`, así que al hacer `git push` los 4 commits se subieron directamente a `develop` sin PR. Se dejó así y se avisó al grupo.
  - Al pasar las imágenes a PNG no añadió a `docs/despliegue.md` el `UPDATE` de `image_url` para la base de datos del hosting, y en el texto de la PR dijo que sí estaba. Lo detectó un compañero en la revisión y se añadió después.
- **Cambios hechos por mí:**
  - Decidí usar PHPMailer en lugar de escribir el envío SMTP a mano.
  - Pedí que el correo fuera más bonito (cabecera con el nombre de la tienda, botón "Ver mi pedido") y que llevara la foto pequeña de cada producto. Para eso `findSummary()` devuelve también `image_url` de cada línea.
- **Cómo lo validé:**
  - Con el correo desactivado (como en local), el pago funciona igual y devuelve `email_sent: false`.
  - Con un servidor SMTP falso en mi ordenador (guarda el mensaje y no lo envía), el correo sale con remitente, destinatario, asunto, versión de texto y HTML, productos, descuento, totales (27,93 €) y enlace al pedido. Falta probarlo con el buzón real de DonDominio.
  - Vi el diseño nuevo con un pedido inventado en escritorio y en móvil. En el móvil el símbolo € se bajaba de línea; se arregló con un espacio que no se corta.
  - Las fotos de los productos eran SVG y Gmail y Outlook no muestran imágenes SVG en los correos. Decidí pasarlas a PNG en toda la web: la herramienta las convirtió con el navegador de las pruebas (Playwright, al doble de tamaño), cambió las rutas de `seed.sql` y el `placeholder` de `ProductCard.jsx` y `ProductPage.jsx`, y borró los SVG. El favicon y el logo siguen en SVG porque el navegador sí los muestra.
  - Comprobé que el catálogo carga las 15 fotos en PNG sin ninguna rota, que la ficha de producto también, y que `npm run build` copia los PNG a `dist/products/`. En mi base de datos local cambié las rutas con `UPDATE products SET image_url = REPLACE(image_url, '.svg', '.png')`.

## 2026-10-15 · Celia (A) · Cambios pedidos en la revisión de la PR de `feature/checkout`
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** aplicar lo que pidieron José y César al revisar la PR.
- **Partes asistidas por IA:** el nombre "The Black Lotus TCG" en título, cabecera, cabecera del checkout, pie, portada, ficha, favicon y correo; el tiempo máximo de 10 s del SMTP en `MailService.php`; la frase de las tarjetas de prueba en el checkout; el aviso "Añadido al carrito" a 4 s con pausa; y el comentario de `handleAdd` en `ProductPage.jsx`.
- **Errores detectados en la IA:** la herramienta añadió primero una segunda línea "Pago simulado" debajo del botón de pagar sin ver que ya había una en la sección de pago; se dejó solo la que había, con la nota del README.
- **Cambios hechos por mí:**
  - Elegí el nombre "The Black Lotus TCG", igual que en la memoria, una frase discreta para las tarjetas de prueba y que el aviso del carrito durara más y se parara con el ratón o el foco.
- **Cómo lo validé:**
  - Con un servidor SMTP que acepta la conexión y no contesta, el envío se corta a los 10-11 s con "NO enviado" (antes podía esperar 300 s).
  - En el navegador: el nombre sale en título, cabecera, portada y ficha; en el móvil (375 px) la portada no se sale de la pantalla; el aviso sigue a los 3 s y desaparece antes de los 4,5 s, con el ratón encima o el foco en "Ver carrito" sigue a los 6 s, y con Enter se abre el carrito; la frase sale en el checkout.
  - `npm run build` y `php -l` sin errores.

## 2026-10-16 · José (B) · Redespliegue en DonDominio, correo de confirmación en el hosting, README y guía final
- **Herramienta:** Claude Code (Claude Sonnet 5.5). Claude en Chrome (extensión del navegador) solo para revisar la pestaña de red de una compra de prueba; no leyó ni escribió contraseñas ni cambió ajustes.
- **Tarea:** regenerar el paquete de despliegue desde `develop` tras fusionar el PR #16, guiar la actualización del hosting, diagnosticar por qué no llegaba el correo de confirmación, y dejar el `README.md` y `docs/despliegue.md` listos para el PR final a `main`.
- **Partes asistidas por IA:** el script del paquete de despliegue y dos ficheros de prueba de un solo uso (`diagnostico.php` y `correo-prueba.php`, con una clave de un solo uso y fuera del repositorio); las comprobaciones desde fuera del estado del hosting; `README.md` (acceso, reparto, correo, despliegue y limitaciones conocidas) y `docs/despliegue.md`.
- **Errores detectados en la IA:**
  - Pensó que los 404 de `/api/health` eran un fallo del contenido de `.htaccess`; eran estados transitorios mientras se subían y editaban los ficheros. Lo aclararon las comprobaciones repetidas desde fuera.
  - Dijo que `PaymentService` devolvía `email_sent` y descartó el análisis de Claude en Chrome; en `develop` era cierto, pero el servidor tenía una versión anterior de `api/` (sin `PHPMailer/` ni la llamada a `MailService`), así que la extensión describía bien lo que había publicado. Se sustituyó la carpeta `api/` completa. Claude en Chrome propuso además soluciones que no encajan con el proyecto (variables de entorno, Composer, otras columnas, un proveedor de correo externo); no se aplicó ninguna.
  - El primer `correo-prueba.php` daba un 500 en blanco en el hosting aunque funcionaba en local; se rehízo para comprobar cada paso y mostrar el motivo.
  - El diagnóstico no mostraba el campo `enabled` del bloque `mail`, así que no podía distinguir un correo desactivado de uno que fallaba.
- **Cambios hechos por mí:**
  - Decidí dejar como mejora futura el texto en español pintado en las imágenes de producto de cartas en inglés, y anotarlo en las limitaciones.
  - Pedí que el README describa el correo como factura proforma de confirmación de pago, y no como factura fiscal.
  - Cambié el servidor SMTP de `config.php` a `smtp.dondominio.com` (con `smtp.theblacklotustcg.onl` el certificado no coincide) y lo recogí en el README y en la guía.
  - Subí yo los ficheros al hosting y escribí yo la contraseña del buzón en `config.php`; la IA no la vio.
- **Cómo lo validé:**
  - Desde fuera del hosting: `/api/health`, `/api/products` (15 productos con las 15 imágenes `.png`), `config.php` y la carpeta `db/` con 403, y `correo-prueba.php` con 404 tras borrarlo (`diagnostico.php` y la carpeta `db/` están pendientes de borrar a la hora de escribir esto).
  - `correo-prueba.php` con el SMTP real: el primer intento dio «subjectAltName did not match», y tras cambiar el servidor a `smtp.dondominio.com` llegó el correo de prueba.
  - Compra completa con la tarjeta `4242 4242 4242 4242` tras subir la `api/` actual: en la confirmación sale la línea del email y llega la factura proforma.
  - Los apartados del README se contrastaron con el código y con los ficheros del repositorio (cupón, tablas, versión de PHP, rutas y estructura).

## 2026-10-17 · Celia (A) · Revisión final de main y página del pedido
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** revisar `main` entero (web publicada, código y flujo completo) y, en la rama `fix/estado-pedido` (desde `develop`), arreglar la página del pedido.
- **Partes asistidas por IA:** la revisión (scripts de navegador y de la API, y lectura del código), los textos por estado de `ConfirmationPage.jsx` y el ejemplo añadido a la limitación «Stock reservado» del README.
- **Errores detectados en la IA:**
  - En una prueba de la API esperaba que la API diera error al intentar sustituir un pedido ya pagado; en realidad la API ignora la sustitución y crea un pedido nuevo sin tocar el pagado, que es correcto. Era fallo de la prueba, no del código.
  - Marcó como fallo que un pedido sin pagar pueda pasar de `incidencia` a `en_preparacion` y lo cambió en `OrderStatusService.php` (también que no se devolviera el stock al cancelar un pedido ya enviado). Pero eso lo habíamos decidido así en el grupo y ya estaba documentado en el README, así que deshice esos cambios.
- **Cambios hechos por mí:**
  - Pedí la revisión completa y autoricé hacer un pedido real en la web publicada con mi email, para comprobar el correo.
  - Decidí dejar las transiciones de incidencia como las acordó el grupo.
- **Cómo lo validé:**
  - Web publicada: pedido PED-000014 con Bizum y BIENVENIDA10 (14,86 €), `email_sent: true`, stock de 30 a 28, e incidencia de soporte nº 1. El admin publicado lo compruebo yo.
  - En local: 44 pruebas de la API (reglas, pagos, stock, sustitución, admin, soporte y eventos) y el admin en el navegador.
  - La página del pedido decía "Tu pedido está confirmado" aunque estuviera cancelado. Ahora muestra el estado real (pendiente, confirmado, en preparación, en camino, entregado, cancelado o incidencia) y el ✓ solo si está pagado. Comprobado con un pedido de cada estado.
  - `npm run build` y `php -l` sin errores.

## 2026-10-17 · César (C) · Logout que no cerraba la sesión en el hosting
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** revisar la web publicada antes de grabar el vídeo y corregir lo que fallara en mi parte.
- **Partes asistidas por IA:** la prueba del logout contra la web publicada y el cambio en `frontend/src/pages/admin/adminApi.js`.
- **Errores detectados en la IA:**
  - La función `logout()` que escribió la IA hacía un `POST` sin cuerpo. En local funcionaba, pero el servidor de DonDominio rechaza esos `POST` con un 403 de Apache antes de llegar a PHP, así que la sesión seguía abierta en el servidor aunque la pantalla volviera al login.
  - Mis pruebas automáticas del logout no lo detectaron porque comprobaban la cookie que devolvía el servidor y no si la sesión antigua seguía siendo válida.
- **Cambios hechos por mí:**
  - Pedí revisar la web publicada antes de grabar y decidí arreglarlo antes de la entrega.
  - El logout envía ahora un cuerpo JSON vacío (`{}`); no cambia la API.
- **Cómo lo validé:**
  - En la web publicada, con `curl`: el `POST /api/auth/logout` sin cuerpo daba 403 y la misma cookie seguía devolviendo los pedidos (200); con cuerpo `{}` da 200 y después la misma cookie recibe 401.
  - En local, login y logout con la API y `npm run build` sin errores.

## 2026-11-01 · José (B) · Tarea 2: contrato de la API del back-office
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** bloque 1 del back-office en la rama `feature/backoffice` (desde `develop`): escribir `docs/backoffice-api.md` con los endpoints de la sección 2.3 del plan v2 y añadir `backoffice/config.php` al `.gitignore`.
- **Partes asistidas por IA:** la redacción de `docs/backoffice-api.md` (cabeceras, JSON de entrada y salida de ejemplo, códigos de respuesta y la lista de decisiones) y la descripción del PR.
- **Errores detectados en la IA:**
  - Al pasar el PDF del plan a texto, la tabla de la sección 2.2 salió con las columnas descolocadas (por ejemplo, los campos de `bo_customers` aparecían junto a `bo_orders`). Hubo que reconstruir qué campos van en cada tabla antes de escribir el contrato.
  - Añadió cosas que el plan no fija: el `503` de `/bo-api/health`, el `405` y las prioridades `baja`/`media`/`alta`. Quedan como propuesta en la sección de decisiones para revisarlas con A y C.
- **Cambios hechos por mí:**
  - Decidí que ni los commits ni el PR lleven firma o coautoría de IA, y que el uso de IA se declare solo en este diario.
  - Pedí la descripción del PR con las decisiones que tienen que revisar A y C, y abrí yo el PR desde GitHub.
- **Cómo lo validé:**
  - Cada endpoint y cada campo del contrato se contrastó con las secciones 2.2 y 2.3 del plan, y los estados del pedido con el `ENUM` de `orders` en `db/schema.sql` de la tienda.
  - `git check-ignore -v backoffice/config.php` confirma que el fichero queda fuera del repositorio.
  - Pendiente: revisión del contrato por A y C en el PR.

## 2026-11-01 · José (B) · Tarea 2: base de datos, esqueleto de la API y Dockerfile del back-office
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** bloques 2, 3 y 4 del back-office en la rama `feature/backoffice-base`: esquema y datos iniciales (`backoffice/db/`), esqueleto de la API con `X-Api-Key` y `GET /bo-api/health`, y `Dockerfile` para el despliegue en la nube.
- **Partes asistidas por IA:** `schema.sql` (8 tablas `bo_*` del apartado 2.2 del plan), `seed.sql`, `config.example.php` (lee las claves de variables de entorno), `api/index.php`, `api/.htaccess`, `api/lib/` (`db.php`, `http.php`, `auth.php`, `router.php`), `api/http/health.php`, `Dockerfile`, `.dockerignore` y la portada provisional `public/index.php`. También los pasos para preparar MySQL y PHP en local.
- **Errores detectados en la IA:**
  - En el `.htaccess` de la API faltaba `RewriteBase /bo-api/`, necesario porque en el contenedor la API se sirve con un `Alias` de Apache. Se corrigió antes del commit.
  - Propuso arrancar en el puerto 8080, que en mi equipo ya estaba ocupado por otro programa; usamos el 8090.
  - El esqueleto copia la forma del router y de las respuestas de la tienda, pero sin incluir ningún fichero de `/api`, como pide el plan.
- **Cambios hechos por mí:**
  - Configuré MySQL 8.4 como servicio de Windows, activé `pdo_mysql` en PHP y creé la base `blacklotus_bo` con su propio usuario, distinto del de la tienda.
  - Generé yo el hash del gestor de prueba y escribí yo las contraseñas y la clave de la API en `backoffice/config.php`, que no se sube; la IA no las vio.
- **Cómo lo validé:**
  - `GET /bo-api/health` con la base de datos: `{"status":"ok","database":"ok"}`. Sin base de datos devuelve 503.
  - Una ruta sin clave o con una clave incorrecta devuelve 401; con la clave buena pasa la comprobación (404 porque los pedidos aún no están programados). Un método no permitido devuelve 405.
  - Las 8 tablas `bo_*` están creadas, el gestor tiene un hash de `password_hash()` y no hay pedidos, incidencias ni registros (evidencia del "antes").
  - `php -l` sin errores en todos los ficheros PHP.
  - Pendiente: probar el `Dockerfile` (no tengo Docker instalado) y el despliegue de prueba en la nube.

## 2026-11-01 · José (B) · Tarea 2: pedidos y registro de integración del back-office
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** bloque 5 del back-office en la rama `feature/backoffice-pedidos`: `POST /bo-api/orders`, `PATCH /bo-api/orders/{code}/status`, `GET /bo-api/orders/{code}`, `POST /bo-api/integration-log` y `GET /bo-api/integration-log?result=pendiente`, tal como están en `docs/backoffice-api.md`. Es lo que necesita A para el escenario 1.
- **Partes asistidas por IA:** `api/http/orders.php`, `api/http/integration_log.php` y las funciones comunes de validación y fechas añadidas a `api/lib/http.php` y `api/lib/db.php`.
- **Errores detectados en la IA:**
  - Las primeras pruebas del alta daban 400 («JSON no válido»). No era fallo del código: la terminal de Git Bash enviaba las tildes («Inglés», «Pokémon») sin UTF-8. Se repitieron las pruebas enviando el JSON desde un fichero.
  - El código usa `mb_strlen` para contar caracteres con tildes, pero en mi PHP local la extensión `mbstring` no estaba activada. La imagen de Docker sí la trae; en local hay que activarla en `php.ini`.
- **Cambios hechos por mí:**
  - Pedí seguir con el bloque 5 en una rama aparte para que cada PR se pueda revisar por separado.
- **Cómo lo validé:**
  - En local, contra mi base `blacklotus_bo`: alta de un pedido con dos líneas (201), el mismo pedido otra vez (200 `duplicado`), un pedido con campos que faltan (422 con la lista de campos) y un cuerpo que no es JSON (400).
  - Cambio de estado (200 con `from_status` y `to_status`), el mismo `event_id` otra vez (200 `duplicado`, sin otra fila de historial) y un pedido que no existe (404).
  - La consulta del pedido devuelve las líneas, el historial y las fechas con zona, y no devuelve el email del cliente.
  - Registro de integración: alta (201), datos no válidos (422) y lista de pendientes, donde no sale un evento que luego se reintentó con `ok`.
  - Sin clave o con clave incorrecta, 401. `php -l` sin errores.
  - Después borré los datos de prueba: el back-office queda vacío salvo el gestor.

## 2026-11-01 · José (B) · Tarea 2: incidencias y propuestas del agente en el back-office
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** bloque 6 del back-office en la rama `feature/backoffice-incidencias`: `POST /bo-api/tickets`, `PATCH /bo-api/tickets/{id}`, `GET /bo-api/customers/{id}/tickets`, `POST /bo-api/proposals` y `PATCH /bo-api/proposals/{id}`, según `docs/backoffice-api.md`. Es lo que necesita C para el escenario 3 y el agente.
- **Partes asistidas por IA:** `api/http/tickets.php`, `api/http/customers.php` (donde pasa la búsqueda o alta del cliente por email, que antes estaba en `orders.php`) y `api/http/proposals.php`.
- **Errores detectados en la IA:**
  - La búsqueda del cliente por email estaba dentro de `orders.php` y los tickets también la necesitan. Funcionaba solo por el orden en que se cargan los ficheros, así que se movió a `customers.php`.
  - En `POST /bo-api/tickets` el contrato pide `event_id`, pero la tabla `bo_tickets` del plan no tiene ese campo: se valida pero no se guarda. La trazabilidad del evento queda en `bo_integration_log`. Lo comento con C.
- **Cambios hechos por mí:**
  - Pedí seguir con el bloque 6 en una rama aparte, como los anteriores.
- **Cómo lo validé:**
  - En local, contra mi base `blacklotus_bo`: alta de ticket (201), el mismo `shop_ticket_id` otra vez (200 `duplicado`) y campos que faltan (422).
  - `PATCH` del ticket: prioridad y estado, chat de Telegram (se guarda en el cliente), cierre con `closed_at`, cuerpo vacío o prioridad no válida (422) y ticket que no existe (404).
  - Historial del cliente sin email ni chat; cliente que no existe (404).
  - Propuesta del agente: se crea siempre en `pendiente_revision`, aunque el cuerpo traiga otro estado. No se puede ejecutar sin aprobar, aprobar sin `reviewed_by` ni rechazar una ya aprobada (422). Aprobar y después `ejecutado_simulado` funcionan (200).
  - Repetí el alta de pedido del bloque 5 tras mover la función del cliente. `php -l` sin errores. Después borré los datos de prueba.

## 2026-11-01 · José (B) · Tarea 2: revisión de César (A) al contrato del back-office
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** aplicar en `docs/backoffice-api.md` (rama `feature/backoffice`) los comentarios de César al PR del contrato y llevarlos al código del bloque 5.
- **Partes asistidas por IA:** los cambios del contrato (ejemplo en `pendiente_pago`, respuesta `desfasado`, orden de los pendientes, nuevo `GET /bo-api/integration-log/processed` y la limitación de `order_items`) y su implementación en `orders.php` e `integration_log.php` (rama `feature/backoffice-pedidos`).
- **Errores detectados en la IA:**
  - El primer contrato ponía el pedido nuevo en `pagado`, pero en la tienda se crea en `pendiente_pago` y el pago llega después como `order.status_changed`. Lo vio César en la revisión.
  - La idempotencia por `event_id` no cubría los eventos que llegan desordenados: un reintento tardío podía devolver el pedido a un estado anterior. También lo detectó César.
- **Cambios hechos por mí:**
  - Pasé al hilo los comentarios de César y decidí aceptar los cuatro, con `desfasado` como valor nuevo de respuesta.
- **Cómo lo validé:**
  - Comparé cada cambio del contrato con los cuatro puntos de la revisión.
  - Las pruebas del código están en la entrada siguiente.

## 2026-11-01 · César (A) · Tarea 2: envío de eventos a n8n y endpoints de integración
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** bloque 1 de la parte A en `feature/integracion`: envío de los eventos integrados al webhook de n8n desde `logEvent()` y los endpoints `GET /api/integration/orders/{code}` y `GET /api/integration/events?after_id=`.
- **Partes asistidas por IA:** el código de `api/lib/integration.php`, `api/repositories/IntegrationRepository.php` y `api/http/integration.php`, el cambio en `api/lib/events.php` y la sección `integration` de `config.example.php`.
- **Errores detectados en la IA:**
  - Daba por hecho que el pedido llega a n8n ya pagado; en la tienda nace en `pendiente_pago` y el pago llega como `order.status_changed` (lo comenté también en el PR #24).
  - `order_items` no guarda los datos TCG: el endpoint los lee del producto actual. Queda como limitación.
- **Cambios hechos por mí:**
  - Decidí que el envío se haga al terminar la petición y solo con los eventos que existen en `events`, para no avisar nunca de una compra deshecha.
  - Avisé al grupo antes de tocar `api/lib/events.php` y `config.example.php`, que son comunes.
- **Cómo lo validé:**
  - En local, con un receptor PHP que hace de n8n: un pedido y su pago envían los 3 eventos con el sobre y el secreto; `checkout.started` no se envía.
  - Con el receptor tardando 5 s la compra tarda 2,2 s y se crea igual; el fallo queda en el log.
  - Endpoints: 401 sin clave o con clave mala, 404 con un código inexistente, 400 sin `after_id` o con `limit` fuera de rango.

## 2026-11-01 · César (A) · Tarea 2: flujo "Pedidos" de n8n
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** bloque 2 de la parte A en `feature/integracion-pedidos`: el flujo "Pedidos" de n8n (escenarios 1 y 2) y los payloads de ejemplo.
- **Partes asistidas por IA:** el JSON del flujo (`integration/n8n/workflows/pedidos.json`), los nodos Code de validación, transformación y clasificación de errores, la instalación de n8n en local y las pruebas.
- **Errores detectados en la IA:**
  - La primera versión pedía el pedido a `/api/integration/...`, pero en local la API no lleva el prefijo `/api`: la URL base de la tienda pasó a ser un ajuste (`tienda_api_url`).
  - La validación aceptaba un `event_id` que no era un número ("abc"); ahora exige un entero positivo.
  - Probó el flujo antes de que n8n activara la versión nueva y dio un falso error; ahora se espera a que el webhook de la versión actual esté activado.
  - npm 11 se saltó los scripts de instalación de n8n y hubo que ejecutar los de `sqlite3` e `isolated-vm`.
- **Cambios hechos por mí:**
  - Pedí instalar n8n y guardar sus datos en el disco D, no en C.
- **Cómo lo validé:**
  - En local, con n8n 2.45.7, la API de la tienda y un back-office falso que sigue `docs/backoffice-api.md`: un pedido con 2 líneas se crea con sus datos TCG y pasa a `pagado` con su historial; el mismo evento otra vez queda `duplicado`; `incompleto.json` queda `error` + `payload_incompleto`; un webhook sin secreto o con secreto malo da 403.
  - Con el back-office parado, el error se clasifica como `backoffice_no_disponible` y la ejecución queda en error en n8n (no se puede registrar en el back-office).

## 2026-11-01 · César (A) · Tarea 2: flujo "Reintentos", variables de n8n y matriz de decisión
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** bloque 3 de la parte A: flujo "Reintentos" (`feature/integracion-reintentos`), `integration/n8n/.env.example` y `docs/matriz-decision.md` (`feature/matriz-decision`).
- **Partes asistidas por IA:** el JSON del flujo, la plantilla de variables, la redacción de la matriz y el cálculo de los totales ponderados y de la sensibilidad.
- **Errores detectados en la IA:**
  - Intentó lanzar el flujo con `n8n execute` sin un disparador manual y con el n8n local encendido (puerto 5679 ocupado); se añadió el nodo "Ejecutar ahora" y otro puerto para la ejecución desde la consola.
  - La primera versión de la matriz ponía Zapier como alternativa SaaS sin más; el enunciado no acepta herramientas 100 % no-code como Zapier, así que se cambió por Make y se dejó escrito.
  - `.env.*` del `.gitignore` también ignora `.env.example`; se añadió forzado en vez de cambiar el `.gitignore` común. En la revisión José pidió la excepción `!.env.example` en el `.gitignore` y se cambió.
  - Con varios pendientes a la vez, "Reenviar a Pedidos" fallaba ("Paired item data ... is unavailable") porque leía los ajustes con `.item` después de un nodo Code que crea elementos nuevos; se cambió a `.first()`. Con un solo pendiente no se veía.
- **Cambios hechos por mí:**
  - Pedí adelantar lo que no dependía de nadie mientras José añade el endpoint de eventos procesados.
- **Cómo lo validé:**
  - Dos pendientes forzados (el `order.created` con la clave de la tienda cambiada, `tienda_no_disponible`, y su `order.status_changed`, `pedido_no_existe`): Reintentos los reenvió en orden y el pedido quedó creado y pagado.
  - Reintentos en local contra el back-office de José: un `order.status_changed` pendiente (`pedido_no_existe`) se aplicó al reintentarlo después de crear el pedido; un evento que no existe en la tienda se queda pendiente y no se reenvía.
  - Los totales de la matriz salen de un script (pesos que suman 100, puntuaciones de 1 a 5).
  - `deploy/docker-compose.yml` levantado en Docker Desktop con otro nombre de puertos (5688 y 8088) y sin tocar los contenedores de otra asignatura: pedido y pago llegaron al back-office del contenedor (`ok` ×3) y los datos siguieron tras `docker compose down` y `up`. n8n avisaba de que PostgreSQL 16 solo tiene soporte de compatibilidad: se pasó a la 17.
  - Escenario 2 desde la administración de la tienda: `pagado → en_preparacion → enviado → entregado` llega al back-office paso a paso con su historial; una cancelación de un pedido pagado devuelve el stock en la tienda (7 → 5 → 7) y deja el pedido `cancelado` en el back-office.
  - `docs/diagramas/flujo-datos.svg` (y su PNG) dibujado a mano en SVG; se revisó la imagen generada y se recolocaron dos etiquetas que se cruzaban con las flechas.
- **Limitaciones:**
  - En una prueba, un `order.status_changed` enviado por la tienda no llegó a ejecutarse en n8n sin dejar error en ningún lado; no se encontró la causa. Lo cubre la recuperación de huecos de la entrada siguiente.

## 2026-11-10 · César (A) · Tarea 2: cambios tras la revisión de José (#28 a #31)
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** aplicar los puntos de la revisión de José en `feature/integracion`, `feature/integracion-pedidos` y `feature/integracion-reintentos`, y en `feature/matriz-decision` (#31): tabla de la plataforma de despliegue (Student Pack con `docker-compose`, Render, Railway y DonDominio), descarte de "una tabla más en la tienda" y de ampliar `/#/admin`, y la hoja de Google en lugar de Airtable.
- **Partes asistidas por IA:** los cambios en `api/lib/integration.php`, en los nodos de "Pedidos" y "Reintentos" y en el compose, y las pruebas con datos simulados.
- **Errores detectados en la IA** (los encontró José en la revisión):
  - El envío a n8n esperaba hasta 2 s por cada evento de la petición; con n8n caído, una compra con tres eventos tardaba 6 s. Ahora se corta en el primer fallo y el resto lo recupera Reintentos. Si no existe `fastcgi_finish_request()` se usa `litespeed_finish_request()`.
  - "Pedidos" dejaba en `pendiente` cualquier error de la tienda, también un 401 o un 404, que no se arreglan reintentando. Ahora un 4xx queda en `error` (`clave_no_valida`, `pedido_no_existe_en_tienda` o `datos_no_validos`) y solo la red y los 5xx quedan en `pendiente`.
  - Reintentos solo reenviaba los `pendiente` del registro: un evento que no llegó a n8n no se recuperaba nunca. Se añade el ramal de huecos con `GET /bo-api/integration-log/processed?after_id=N`.
  - Quedaba en "Reintentos" un comentario interno de la herramienta (`ponytail:`); se quitó.
  - El compose publicaba los puertos de n8n y del back-office en todas las interfaces; ahora solo en `127.0.0.1`, para que desde Internet solo se entre por el proxy.
- **Cambios hechos por mí:**
  - El endpoint de tickets y el paso de `support.requested` a un flujo propio son de la parte de Celia (C) según el plan; no los hago yo. Reintentos excluye `support.requested` de forma explícita al buscar huecos.
- **Cómo lo validé:**
  - Envío con un receptor que tarda 5 s y tres eventos en cola: 6,0 s antes del cambio y 2,0 s después.
  - Código del nodo "Error de la tienda" con seis errores simulados: 401, 404 y 400 → `error`; 503, conexión rechazada y sin error → `pendiente`.
  - Nodos "Cursor" y "Huecos" con respuestas simuladas: reenvía solo los eventos sin ningún registro, no toca `support.requested`, los errores ni los de los últimos 2 minutos, y el cursor no pasa del primer hueco.
  - En n8n local (2.45.7, "Reintentos" publicado con su disparador de 5 minutos) contra el back-office de José (`feature/backoffice-pedidos`) con la base vacía: la primera vuelta recuperó los 47 eventos de la tienda (35 `ok` y 12 `duplicado`, 14 pedidos creados). Después, con el registro del evento 54 borrado y un `support.requested` nuevo (55), la segunda vuelta reenvió solo el 54, se saltó el 55 y dejó el cursor en 53; la tercera no reenvió nada y lo pasó a 55.
  - Un `order.created` con un código que no existe en la tienda queda `error` / `pedido_no_existe_en_tienda` con el mensaje "Pedir pedido a la tienda: 404 …".
- **Limitaciones:**
  - El cursor se guarda en los datos estáticos del flujo, que n8n solo conserva en las ejecuciones automáticas; con "Ejecutar ahora" se empieza desde 0 (se revisa todo, sin reenviar lo que ya tiene registro).

## 2026-11-12 · César (A) · Tarea 2: prueba de #28 a #31 fusionados
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** probar los cuatro PR de la parte A juntos, en el orden de fusión propuesto (back-office de José, #31, #28, #29 y #30) sobre `develop`, y probar el flujo "Errores" con el bot de Telegram de pruebas.
- **Partes asistidas por IA:** el montaje de la prueba (rama temporal con las fusiones, base del back-office desde cero) y el script que crea los pedidos y cambia los estados.
- **Errores detectados en la IA:**
  - El valor local de `tienda_api_url` en "Pedidos" y "Reintentos" era `http://localhost:8000`, que solo funciona arrancando la tienda desde `api/`. El README la arranca desde la raíz (la API queda en `/api`) y en producción también lleva `/api`, así que n8n recibía `404 Ruta no encontrada`. Ahora es `http://localhost:8000/api` y la guía de despliegue dice lo mismo que el README.
  - La guía no avisaba de que, al exportar los flujos del servidor, el `chatId` de "Errores" y las URLs de **Ajustes** salen con los valores reales. Ahora lo dice.
- **Cambios hechos por mí:**
  - Decidir que el arreglo de la URL va en nuestros ficheros (flujos y guía) y no en el README, que es común.
- **Cómo lo validé:**
  - Fusión de prueba: `docs/ia-log.md` choca con `feature/backoffice-pedidos` (las dos ramas añaden entradas al final); se resuelve dejando las de las dos. Nada más choca. `php -l` sin errores en `api/` y `backoffice/`; los JSON de flujos y payloads son válidos.
  - Escenarios 1 y 2: pedido con dos líneas pagado y llevado hasta `entregado` desde la administración, y otro sin pagar cancelado; los dos quedan igual en el back-office. `cancelado → pagado` da 409 sin evento, el payload incompleto queda `error` / `payload_incompleto` y el webhook sin secreto da 403.
  - El cambio a `pagado` llegó antes que su pedido (`pendiente` / `pedido_no_existe`) y la siguiente vuelta de Reintentos lo dejó `ok`.
  - Huecos con eventos reales: dos eventos se enviaron mientras n8n arrancaba (no los recibió) y otros fallaron por la URL; Reintentos los recuperó todos (3 pedidos más en el back-office, 2 `duplicado`) y el cursor no pasó del primero sin registro.
  - "Errores": con el back-office parado, "Pedidos" falla al registrar y llega al canal de pruebas de Telegram "Error en n8n / Flujo: Pedidos / Paso: Registrar resultado / Causa: The service refused the connection…". Al volver el back-office, Reintentos recupera el pedido.
## 2026-11-01 · José (B) · Tarea 2: eventos desordenados y procesados en el back-office
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** llevar al código del bloque 5 (rama `feature/backoffice-pedidos`) los cambios del contrato que pidió César: respuesta `desfasado`, `GET /bo-api/integration-log/processed` y pendientes ordenados por `event_id`.
- **Partes asistidas por IA:** `api/http/orders.php` (bloqueo del pedido con `FOR UPDATE` y comparación con el último `changed_at` del historial) y `api/http/integration_log.php` (nuevo endpoint y nuevo orden).
- **Errores detectados en la IA:** ninguno en las pruebas de esta parte.
- **Cambios hechos por mí:**
  - Elegí `desfasado` como valor propio de la respuesta, en lugar de reutilizar `duplicado`, para que en el registro se distinga un evento repetido de uno que llegó tarde.
- **Cómo lo validé:**
  - En local: pedido en `pendiente_pago`, pago a las 10:02 y envío al día siguiente. Después, un `en_preparacion` con hora anterior al envío devuelve `desfasado`, queda en el historial y el pedido sigue en `enviado`. El mismo evento otra vez devuelve `duplicado`.
  - `processed?after_id=N` devuelve solo los eventos cuyo último registro es `ok` o `duplicado`, en orden; uno que pasó de `pendiente` a `ok` con un reintento sí sale. Respeta `limit`, y sin `after_id` devuelve 422.
  - Los pendientes salen ordenados por `event_id` aunque se registraran en otro orden.
  - `php -l` sin errores. Después borré los datos de prueba.

## 2026-11-01 · José (B) · Tarea 2: revisión de César (A) al PR de pedidos
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** aplicar los comentarios de César al PR de pedidos (`feature/backoffice-pedidos`). Tres de los cuatro puntos ya estaban hechos tras su revisión del contrato: eventos desordenados, lectura con `FOR UPDATE` dentro de la transacción y `GET /bo-api/integration-log/processed`. Faltaba el cambio al mismo estado.
- **Partes asistidas por IA:** en `api/http/orders.php`, si el `status` que llega es igual al actual se responde `duplicado` sin añadir fila al historial; también la línea correspondiente en `docs/backoffice-api.md`.
- **Errores detectados en la IA:**
  - El código anterior guardaba `pagado → pagado` en el historial cuando el pedido llegaba ya pagado. Lo detectó César.
  - En una prueba, un cambio sin `changed_at` salió como `desfasado` porque se usó la hora actual y los datos de prueba tenían fechas futuras. Era un problema de los datos de prueba, no del código; se repitió la prueba con fechas coherentes.
- **Cambios hechos por mí:**
  - Pasé al hilo la revisión de César y pedí aplicarla.
- **Cómo lo validé:**
  - En local: un pedido que llega en `pagado` y después recibe `pendiente_pago → pagado` devuelve `duplicado`, y el historial no cambia. Un segundo `enviado` con otro `event_id` también devuelve `duplicado` sin añadir fila.
  - `php -l` sin errores. Después borré los datos de prueba.

## 2026-11-01 · José (B) · Tarea 2: revisión de César (A) al PR de incidencias
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** corregir en `feature/backoffice-incidencias` lo que vio César: dos altas a la vez con el mismo email nuevo hacían saltar el `UNIQUE` de `bo_customers` y la API respondía 500.
- **Partes asistidas por IA:** `findOrCreateCustomer` en `api/http/customers.php`, que ahora usa una sola sentencia `INSERT ... ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)` en lugar de buscar y luego insertar.
- **Errores detectados en la IA:** la versión anterior buscaba el cliente y después lo insertaba en dos pasos, así que dos peticiones a la vez podían intentar crear el mismo cliente. Lo detectó César en la revisión.
- **Cambios hechos por mí:**
  - Pasé al hilo la revisión de César y pedí corregirla.
- **Cómo lo validé:**
  - En local, con dos servidores de PHP a la vez, mandé cinco pares de altas de ticket simultáneas, cada par con el mismo email nuevo: las diez respondieron 201, se crearon 5 clientes (uno por email) y ningún ticket quedó sin cliente.
  - Un alta de pedido con un email ya existente reutiliza el cliente. `php -l` sin errores. Después borré los datos de prueba.

## 2026-11-01 · José (B) · Tarea 2: parada del contenedor del back-office
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** corregir el `Dockerfile` del back-office (rama `feature/backoffice-base`) a partir de lo que vio César al probarlo con su `docker-compose.yml`.
- **Partes asistidas por IA:** el cambio del `CMD`, que ahora termina en `exec apache2-foreground`.
- **Errores detectados en la IA:** el `CMD` en forma shell dejaba a `sh` como proceso principal. Al hacer `docker compose down`, la señal de parada no llegaba a Apache y Docker lo mataba a los 10 s (código 137). No lo vimos antes porque yo no tengo Docker instalado; lo detectó César.
- **Cambios hechos por mí:**
  - Pasé al hilo el comentario de César y pedí aplicarlo.
- **Cómo lo validé:**
  - No lo he podido probar: no tengo Docker. Queda pendiente que César lo compruebe con su compose (el contenedor debe pararse sin el código 137) y probarlo yo en el servidor.

## 2026-11-13 · José (B) · Tarea 2: integración del back-office en develop
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** llevar el back-office a `develop` con un PR desde `feature/backoffice-pedidos` y aplicar dos cambios del contrato que pidió Celia.
- **Partes asistidas por IA:**
  - Detectar que los PR #25, #26 y #27 se habían fusionado cada uno en su rama base y que `develop` solo tenía el contrato (#24).
  - Comprobar que `feature/backoffice-pedidos` se fusiona sin conflictos con `develop`.
  - En `docs/backoffice-api.md`: el ejemplo de `POST /bo-api/proposals` usa ahora `case_type` `dano_envio`, y los tipos de error se remiten a `docs/integracion.md` en lugar de repetirlos.
- **Errores detectados en la IA:** en un borrador de mensaje para César propuso arrancar el compose desde `feature/backoffice-pedidos`, que no tiene el `docker-compose.yml`. Se corrigió antes de enviarlo.
- **Cambios hechos por mí:**
  - Pedí los dos cambios del contrato a partir de lo que dijo Celia.
  - Reviso el diff del PR antes de fusionarlo.
- **Cómo lo validé:**
  - La clave `dano_envio` es la que usa `api/services/SupportService.php`.
  - Simulé la fusión con `develop` (`git merge-tree`) sin conflictos.

## 2026-11-12 · Celia (C) · Tarea 2: contrato de integración y endpoint de incidencias
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** fase 0 de la parte C en la rama `feature/agente-soporte` (desde `feature/integracion` de César, porque uso su `requireIntegrationKey()`): escribir `docs/integracion.md`, añadir `GET /api/integration/tickets/{id}` y guardar `integration/payloads/escenario3.json`.
- **Partes asistidas por IA:** la lectura de los PR #24 a #31 para ponerme al día, la redacción de `docs/integracion.md` (sobre, datos de los 4 eventos, endpoints de la tienda, tipos de error, salida del agente y `case_type`) y el código del endpoint en `api/http/support.php` y `SupportTicketRepository.php`.
- **Errores detectados en la IA:**
  - En la primera versión del contrato la salida del agente llevaba `priority` y `customer_reply`, pero `bo_agent_proposals` no tiene esas columnas. Lo vio al releer el `schema.sql` de José: la prioridad va al ticket con `PATCH /bo-api/tickets/{id}` y la respuesta al cliente dentro de `proposed_action`, sin cambiar la tabla.
- **Cambios hechos por mí:**
  - Decidí que `order_code` lo resuelva la tienda en el endpoint (solo sale si el ticket está vinculado al pedido) en vez de mirar `order_linked` en n8n, como pedía José en el #28.
  - Decidí no añadir `event_id` a `bo_tickets` (la trazabilidad queda en `bo_integration_log`) y respondí en el contrato al resto de puntos pendientes de C.
- **Cómo lo validé:**
  - Las pruebas las ejecutó Claude en mi PC, con la API en local (`php -S`) y una `api_key` de prueba solo en mi `api/config.php`: dos incidencias desde `POST /support`, una con el email del pedido (sale `order_code`) y otra con un email distinto (sale `null`). Sin clave o con clave mala, 401; id que no existe o no numérico, 404.
  - `escenario3.json` es el evento real 227 de esa prueba, sacado con `GET /api/integration/events`.
  - `php -l` sin errores.

## 2026-11-12 · Celia (C) · Tarea 2: modelo del agente y bot de Telegram
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** cerrar la decisión 5 del plan (modelo de IA del agente) y crear el bot de Telegram de pruebas (fase 0 de C).
- **Partes asistidas por IA:** los pasos para crear el bot con @BotFather y la clave en Google AI Studio, y la comparación de los límites del plan gratuito.
- **Errores detectados en la IA:**
  - No conocía los modelos actuales de Gemini (son posteriores a sus datos) y no pudo confirmar el nombre exacto: lo saqué yo de AI Studio.
  - Primero apuntaba a Gemini 3.8 Flash; al ver la tabla de límites solo tenía 20 peticiones al día, demasiado poco para un agente que hace 4 o 5 llamadas por incidencia.
- **Cambios hechos por mí:**
  - Elegí **Gemini 3.5 Flash Lite** (`gemini-3.5-flash-lite`), plan gratuito de Google AI Studio: 15 peticiones por minuto, 250K tokens por minuto y 500 peticiones al día (límites vistos en AI Studio el 2026-11-12). Si en las pruebas falla con las herramientas o el JSON, se probará `gemini-3.8-flash` (5 por minuto, 20 al día).
  - La clave de API que usamos es la `Default Gemini API Key` del proyecto `Default Gemini Project`, de nivel gratuito y sin facturación. Solo va en las credenciales de n8n. *(Corregido el 15/11: aquí ponía que había creado una clave `blacklotus-tarea2`, pero en AI Studio solo está esta.)*
  - Creé el bot de pruebas `@blacklotus_soporte_bot`. El token solo va en la credencial de n8n, nunca en el repositorio.
- **Cómo lo validé:**
  - Comprobé en AI Studio el identificador del modelo (Playground) y sus límites (Límite de frecuencia).
  - Riesgo para la memoria: en el plan gratuito Google puede usar las peticiones para mejorar sus productos; al modelo solo le llegan datos ficticios, sin email ni dirección.

## 2026-11-12 · Celia (C) · Tarea 2: revisión de los PR #28 a #31
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** revisar los PR de César (#28 a #31) y el estado de las ramas del back-office de José, para dar el visto bueno.
- **Partes asistidas por IA:** la lectura del código y de los flujos exportados, una prueba de fusión de todas las ramas en una copia aparte (back-office → #31 → #28 → #29 → #30 y mis cambios encima) y el borrador de los mensajes.
- **Errores detectados en la IA:** ninguno en esta revisión.
- **Cambios hechos por mí:** decidí qué puntos son para antes de fusionar y cuáles pueden esperar a mi PR (la rama `support.requested` de "Pedidos" la cambio yo con el flujo "Incidencias").
- **Cómo lo validé:** en la prueba de fusión solo choca `docs/ia-log.md` (entradas al final, se dejan las dos); mis cambios se aplican sin conflictos y los PHP pasan `php -l`. Comprobé que el código ya referencia `docs/integracion.md`, que es mío y aún no está subido.
- **#32 (back-office a develop, 2026-11-13):** es el mismo código que revisé, más los dos cambios del contrato que pedí. Repetí la prueba de fusión con el #32 en vez de `feature/backoffice-pedidos` y salió igual (solo choca el ia-log); los PHP del back-office pasan `php -l`. A raíz del mensaje de César, completé en `docs/integracion.md` el caso de `datos_no_validos` (también cuando la tienda da otro 4xx).

## 2026-11-13 · Celia (C) · Tarea 2: flujo "Incidencias" (escenario 3, sin agente)
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** primera parte del flujo "Incidencias" en la rama `feature/agente-incidencias` (desde `feature/agente-soporte`): `support.requested` → validación → incidencia desde la tienda → ticket en el back-office → registro. El agente va en el paso siguiente.
- **Partes asistidas por IA:** el borrador de `incidencias.json` siguiendo el estilo de `pedidos.json` de César, el cambio del nodo de Pedidos y los scripts de comprobación.
- **Errores detectados en la IA:** al editar `reintentos.json` con un script, el primer intento no encontraba el texto por cómo se escapaban los saltos de línea; se repitió con un script en fichero.
- **Cambios hechos por mí:**
  - Decidí que Pedidos llame a Incidencias sin esperar a que termine, porque cuando esté la aprobación por Telegram puede tardar horas.
  - Quité `support.requested` de los excluidos de Reintentos (lo había propuesto en la revisión del #28), porque ahora Incidencias sí lo registra.
  - Mismas credenciales y mismos tipos de error que en Pedidos; nuevo `ticket_no_existe_en_tienda` para el 404 de la tienda.
- **Cómo lo validé:**
  - Comprobación sin n8n: los tres flujos son JSON válido, las conexiones apuntan a nodos que existen y el código de cada nodo compila.
  - Ejecuté el código de los nodos con `escenario3.json` y la respuesta real de `GET /api/integration/tickets/5`: el cuerpo que sale para `POST /bo-api/tickets` cumple lo que valida el back-office. El ticket 6 (email distinto) sale con `order_code: null`.
  - Los errores salen con su tipo: 401 → `clave_no_valida`, 404 → `ticket_no_existe_en_tienda`, red/5xx → `pendiente`, 422 del back-office → `datos_no_validos`.
  - Prueba real en mi PC con n8n 2.45.7 (instalado aparte con un Node 24 portátil, sin tocar el Node del frontend), la tienda y el back-office de José con su base `blacklotus_bo` en mi MariaDB. Credenciales y secretos de prueba solo en n8n y en los `config.php` locales.
  - Incidencia enviada desde el formulario (ticket 7, evento 229): Pedidos → Incidencias → ticket creado en el back-office con su pedido y registro `ok`.
  - El mismo evento otra vez → `duplicado`; ticket con otro email → ticket sin pedido (`order_code` nulo); ticket que no existe → `error` + `ticket_no_existe_en_tienda`; sin `entity_id` → `error` + `payload_incompleto`; secreto malo → 403.
  - Con la tienda apagada el evento 227 quedó `pendiente` + `tienda_no_disponible`; al encenderla, Reintentos lo reenvió y quedó `ok`. Reintentos también recuperó incidencias antiguas que nunca habían llegado (ramal de huecos), así que quitar la exclusión funciona.
- **Revisión del #33 (José):** faltaba `event_id` en la línea del apartado 3 que dice qué se manda a `POST /bo-api/tickets` (el flujo ya lo mandaba). Lo añadí a la documentación en esta rama y marqué como hecho el punto 7 del apartado 6.

## 2026-11-13 · Celia (C) · Tarea 2: agente de IA en "Incidencias"
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** añadir a "Incidencias" el agente de IA (Gemini) con sus herramientas, la salida estructurada y la aprobación por Telegram, en la rama `feature/agente-ia`.
- **Partes asistidas por IA:** el borrador de los nodos nuevos, el prompt del agente, el esquema de la salida y los scripts de comprobación. Los nombres de los parámetros de cada nodo se sacaron de los ficheros del propio n8n 2.45.7 instalado.
- **Errores detectados en la IA:** la primera versión del script reformateaba todo `incidencias.json` y el diff salía entero; se cambió para que solo añada los nodos nuevos.
- **Cambios hechos por mí:**
  - Las herramientas llevan la URL fija a la incidencia, al pedido y al cliente, para que el agente no pueda consultar los de otra persona.
  - `registrar_propuesta` es un nodo del flujo y no una herramienta del modelo: así la propuesta se guarda una vez y tal como la valida el parser.
  - El mensaje del cliente se trata como datos en el prompt (por si intenta darle órdenes al agente).
  - Para `consultar_historial_cliente` hacía falta el `customer_id`: lo añadí a la respuesta de `POST /bo-api/tickets` del back-office (aviso a José).
  - `reviewed_by` sale de Ajustes, porque los botones de Telegram no dicen quién los pulsa.
- **Cómo lo validé:**
  - Comprobación del flujo: las conexiones apuntan a nodos que existen y el código de cada nodo compila; los nodos de código nuevos dan el cuerpo que pide `POST /bo-api/proposals` con una salida de ejemplo.
  - `POST /bo-api/tickets` devuelve `customer_id` (probado con el ticket 7, `duplicado`) y `php -l` pasa.
  - El flujo se importa y publica en mi n8n local sin errores. Falta la prueba con Gemini y Telegram: necesito crear antes las credenciales.
- **Revisión del 15/11:** al repasar el contrato vi que el apartado 4 ya tenía `agente_no_disponible` y `salida_agente_no_valida`, y el flujo no los registraba. Añadí la salida de error del agente, que registra una segunda fila para el evento. `agente_no_disponible` pasa a `error`, porque al reenviarlo daría `duplicado` y no volvería al agente. Añadí también `reintentos_agotados` a la tabla, como pidió César en el #36.
- **Prueba en n8n local (15/11):** la primera incidencia (ticket 8) falló en "Datos para el agente": `$('...').first()` no devuelve nada en nodos con dos salidas (la de error). Lo cambié por `.item`, como en el resto del flujo, y el mensaje de Telegram ya no lee del agente, que ahora también tiene dos salidas. Con el ticket 9 el flujo llega al agente; Gemini respondió 400 porque la credencial aún no tenía mi clave, y la rama de error registró `agente_no_disponible` como debía. Falta la prueba con la clave buena.
- **Prueba con la clave de Gemini (ticket 11):** el agente usó las tres herramientas y devolvió una propuesta válida, que se guardó en el back-office. Avisó en `risks` de que el pedido figura `pendiente_pago` aunque el cliente dice que le llegó, y de que tiene 8 incidencias anteriores (mis pruebas). El envío a Telegram falló con *can't parse entities*: *Send and Wait* manda siempre en Markdown y los `_` de `dano_envio` lo rompen. Ahora el mensaje se arma en "Preparar propuesta" con esos caracteres escapados.
- **Telegram en local (tickets 12 a 14):** con el escapado ya no falla el Markdown. Telegram rechaza los botones con `http://localhost:5678` (*Wrong HTTP URL*); en local arranco n8n con `WEBHOOK_URL=http://127.0.0.1.nip.io:5678/`, que apunta al mismo PC. Queda pendiente que el bot de la credencial local encuentre el grupo (*chat not found*).
- **Prueba completa en local (ticket 16, ejecución 121):** el bot y el chat del grupo estaban bien (la primera vez el bot aún no estaba dentro del grupo). Llegó al grupo el mensaje con la propuesta y los botones; al pulsar Aprobar, la propuesta 6 pasó a `aprobado` (`reviewed_by` `@revisor_pruebas`) y después a `ejecutado_simulado`. Añadí a la guía de despliegue cómo probar los botones en local.

## 2026-11-13 · César (A) · Tarea 2: despliegue de n8n y el back-office en Azure
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** poner en marcha el servidor de la nube con el `docker-compose` del repositorio, HTTPS en los dos subdominios y los tres flujos de n8n publicados.
- **Partes asistidas por IA:**
  - Los pasos para crear la máquina virtual en Azure: tamaño disponible en la región, puertos e IP estática.
  - Los scripts que instalan Docker y Caddy y generan el `.env` del servidor con claves aleatorias que no se imprimen.
  - La importación de credenciales y flujos por línea de comandos de n8n.
  - Los cambios de la guía de despliegue.
- **Errores detectados en la IA:**
  - Al generar el `.env` desde PowerShell, las comillas no llegaban al servidor y el comando falló. Se cambió por un script copiado al servidor.
  - El compose usaba la variable `WEBHOOK_URL`, que n8n 2.x da por obsoleta (avisa en el arranque). Ahora es `N8N_WEBHOOK_URL`.
- **Cambios hechos por mí:**
  - Creé la máquina en el portal de Azure con mi clave SSH y su frase de paso.
  - Creé la cuenta de administrador de n8n.
  - José dio de alta los registros DNS en DonDominio.
- **Cómo lo validé:**
  - Desde fuera del servidor, `https://n8n.theblacklotustcg.onl/healthz` da 200 y `https://backoffice.theblacklotustcg.onl/bo-api/health` responde `ok`. Por `http`, Caddy redirige (308).
  - Los puertos de n8n y del back-office solo escuchan en `127.0.0.1`.
  - El webhook público da 403 sin secreto. Con el secreto y un evento sin `event_id`, `occurred_at` ni `data.code`, el back-office guarda `error` / `payload_incompleto` con el id de la ejecución.

## 2026-11-14 · César (A) · Tarea 2: máximo de reintentos en "Reintentos"
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** que un evento `pendiente` que no se arregla deje de reenviarse. Al conectar la tienda publicada, los dos cambios de estado de PED-000006 se reenviaban cada 5 minutos sin fin (49 veces cada uno en 4 horas). Ese pedido es anterior a la integración y su `order.created` ya no está en la tienda.
- **Partes asistidas por IA:**
  - Contar los intentos de cada evento en los datos estáticos del flujo.
  - El nodo «¿Agotado?» y el registro `error` / `reintentos_agotados` pasadas 12 vueltas (1 hora).
  - El arnés de prueba del código del nodo.
- **Errores detectados en la IA:**
  - El nodo «Sobre» emparejaba cada respuesta de la tienda con su evento por la posición en la lista. Con «¿Agotado?» en medio, esa posición deja de coincidir y un evento bueno se perdía sin reenviarse. Ahora usa `itemMatching`, que sigue la relación entre elementos de n8n.
- **Cambios hechos por mí:**
  - Decidí el máximo (1 hora) y que el evento quede en `error` para revisarlo a mano, en vez de borrarlo.
  - Cerré a mano por la API del back-office, con `reintentos_agotados`, los dos eventos de PED-000006 que ya estaban en el servidor.
- **Cómo lo validé:**
  - Arnés con el código del nodo: 12 vueltas se reenvían, la 13.ª va a «Reintentos agotados», los eventos resueltos se olvidan y el cursor del ramal de huecos no cambia.
  - n8n 2.45.7 en local, con el back-office de `develop` desde cero y dos pendientes reales de la tienda local: el 74, con 12 intentos, queda `error` / `reintentos_agotados` y no se reenvía. El 75 se reenvía y queda `ok` (pedido creado), aunque en la lista va detrás del 74. Los contadores se vacían al resolverse.

## 2026-11-15 · Celia (C) · Tarea 2: seguimiento de la incidencia por Telegram
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** botón "Seguir mi incidencia por Telegram" en la web y flujo "Telegram" de n8n, que guarda el chat del cliente en el back-office (rama `feature/agente-telegram`).
- **Partes asistidas por IA:** el borrador del flujo, la firma del enlace en `support.php`, el botón de `SupportPage.jsx`, el endpoint nuevo del back-office y la documentación. Los parámetros de los nodos de Telegram se sacaron del propio n8n 2.45.7.
- **Cambios hechos por mí:**
  - El enlace lleva una firma (HMAC del número de incidencia con `webhook_secret`), para que nadie enlace su chat a una incidencia ajena cambiando el número. La comprueba la tienda, que es quien la crea: el secreto no sale de `config.php` ni hay que ponerlo en n8n.
  - El *secret token* que pone n8n en el webhook del bot sale del id del flujo y del nodo, que están en el repositorio. Por eso la protección real es la firma de la tienda.
  - `GET /bo-api/tickets/shop/{shopTicketId}` en el back-office (aviso a José): el enlace lleva el número de la tienda y para guardar el chat hace falta el id del back-office.
  - En el registro va el número de incidencia, pero no el chat del cliente. Los mensajes que no son el enlace solo reciben una explicación.
  - El paso de Telegram en el despliegue va como punto 8 aparte, para no chocar otra vez con los pasos 4 y 5 que cambia César en el #34.
- **Cómo lo validé:**
  - `php -l` en los dos PHP. Con `curl`: `POST /api/support` devuelve `telegram_url`; con la firma buena da 200, con la de otra incidencia o vacía da 403, y sin firma (Incidencias) sigue dando 200. `GET /bo-api/tickets/shop/16` da el ticket; 999 o texto dan 404, y sin clave da 401.
  - Los nodos de código del flujo, con mensajes de ejemplo: `/start` válido en privado, el mismo desde un grupo, texto suelto, `/start` sin código o con una firma mal formada, y cada error de la tienda y del back-office.
  - En Chrome, con `npm run dev`: al enviar la incidencia 18 sale el botón con el enlace firmado.
  - Prueba con el bot de verdad: pulsé el botón de la incidencia 18 e *Iniciar* en Telegram. El `/start` se recogió con `getUpdates` y se pasó a una copia del flujo con disparador manual ("Telegram (prueba local)", solo en mi n8n). Resultado `ok`: el chat quedó guardado en el cliente del ticket 18 y el bot me contestó "Listo...".
  - El mismo `/start` cambiando el número a la incidencia 17 (firma de otra) da `error` / `firma_no_valida`, no guarda el chat y el bot contesta que el enlace no es válido.
  - Después marqué el mensaje como leído en el bot, para que no le llegue al flujo del servidor cuando César lo publique.

## 2026-11-15 · Celia (C) · Tarea 2: respuesta aprobada al cliente por Telegram
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** que al aprobar una propuesta se mande al cliente la respuesta por Telegram si ha enlazado su chat (rama `feature/agente-respuesta`, sale de la del #15).
- **Partes asistidas por IA:** los tres nodos nuevos de "Incidencias", el cambio del endpoint del back-office y la documentación.
- **Errores detectados en la IA:** el script que añadía los nodos no los insertó a la primera, porque el fichero tenía saltos de línea de Windows; las conexiones sí se cambiaron. Lo vio la comprobación del flujo (conexiones a nodos que no existían) y se arregló.
- **Cambios hechos por mí:**
  - Después de Aprobar: buscar el chat del cliente, mandarle `customer_reply` (con el Markdown escapado) y luego marcar `ejecutado_simulado`. Si el envío falla, la propuesta se queda en `aprobado` y avisa "Errores", que es lo que de verdad ha pasado. Si se rechaza, no se manda nada.
  - `GET /bo-api/tickets/shop/{shopTicketId}` devuelve también `telegram_chat_id` (aviso a José). Solo lo usan los flujos; las herramientas del agente siguen sin ver el chat.
- **Cómo lo validé:** `php -l`; el endpoint con los tickets 16, 18 y 999; el aviso con un texto con `_` sale escapado. Prueba real: incidencia 19 de la clienta de prueba con Telegram enlazado → propuesta en el grupo → Aprobar → me llegó "Sobre tu incidencia 19: ..." al chat privado del bot y la propuesta 9 quedó `ejecutado_simulado`.
- **Revisión de los #34 y #36 de César (15/11):** tras resolver él los conflictos, comprobé que se fusionan con develop sin conflictos y que mantienen lo nuestro (guía con Incidencias, Gemini y chat; `reintentos.json` sin excluir `support.requested`). Aprobados los dos.
- **Conflictos y prueba de los caminos que faltaban (15/11):** el #38 y el #39 solo chocaban con develop en el ia-log; en la rama del #15 se dejan todas las entradas (primero las de César del 13 y 14/11). El #39 no se toca: lo comprobé con `git merge-tree` y, fusionado antes el #38, entra sin conflictos. En n8n local, la incidencia 20 (Laura, con Telegram enlazado) rechazada desde el grupo: propuesta `rechazado` y no se le manda nada. La 21 (cliente sin Telegram) aprobada: pasa de `aprobado` a `ejecutado_simulado` sin el nodo "Avisar al cliente".

## 2026-11-15 · José (B) · Tarea 2: métricas del cuadro de mando
- **Herramienta:** Claude Code desde VS Code.
- **Tarea:** `GET /bo-api/metrics` con las métricas del cuadro de mando y su justificación en `docs/metricas.md` (rama `feature/backoffice-metricas`, PR #40).
- **Partes asistidas por IA:**
  - El repaso de las tablas `bo_*` para ver qué métricas se pueden sacar de datos reales.
  - Las consultas de `backoffice/api/http/metrics.php` (ventas, preparación y envío, integración, soporte y agente).
  - La búsqueda de fuentes y el borrador de `docs/metricas.md`.
  - La actualización del contrato en `docs/backoffice-api.md`.
- **Errores detectados en la IA:**
  - Los mapas vacíos (errores por tipo, motivos, tipos de caso) salían en el JSON como `[]` en vez de `{}`. Se corrigió con `asMap()`.
  - Las pruebas en local no detectaron que el MySQL del servidor va en UTC: los datos de prueba tenían `placed_at` y `synced_at` creados con el mismo `NOW()`. César lo detectó al revisar el #40: `synced_at`, `created_at` y `reviewed_at` (`NOW()` y `DEFAULT CURRENT_TIMESTAMP`) salían en UTC y `placed_at` en hora de Madrid, y el retraso de sincronización daba unos −3400 s, escondidos por `GREATEST(…, 0)`.
- **Cambios hechos por mí:**
  - Elegí hacer un cuadro de mando completo: las 7 métricas del contrato más el retraso de sincronización, la preparación y envío, la mezcla de producto, los clientes que repiten y la supervisión del agente (tasa de aprobación, confianza y tiempo de revisión).
  - Decidí dejar las métricas de navegación (tiempo de visita y visitas sin compra) para un PR aparte, porque necesitan que los datos lleguen por n8n y tocan ficheros de César.
  - Revisé las métricas y las fuentes de `docs/metricas.md` antes de subirlas.
  - Con la propuesta de César, `db.php` fija la zona horaria de la sesión de MySQL (`SET time_zone` con el desfase de Madrid) y `backoffice/db/fix-hora-utc.sql` corrige una sola vez las filas que ya estaban guardadas en UTC.
- **Cómo lo validé:**
  - Back-office en local con MariaDB 10.11 (`ONLY_FULL_GROUP_BY` activado) y datos de prueba: con las tablas vacías todas las medias salen `null` y las series con 0; con 4 pedidos, 2 incidencias y 3 propuestas, las cifras coinciden con las calculadas a mano.
  - Sin `X-Api-Key` da 401; con `days=0` o `days=abc`, 422.

## 2026-11-16 · José (B) · Tarea 2: página del cuadro de mando y login del gestor
- **Herramienta:** Claude Code desde VS Code.
- **Tarea:** login del gestor (`bo_users`, `password_hash` y sesión) y página del cuadro de mando del back-office con los datos de `GET /bo-api/metrics` (rama `feature/backoffice-cuadro-mando`).
- **Partes asistidas por IA:**
  - `backoffice/api/lib/session.php` (sesión, caducidad tras 2 horas sin actividad, token de los formularios) y `requireApiKeyOrManager()` para que `/metrics` acepte la sesión.
  - `public/login.php`, `public/logout.php` y `public/index.php`.
  - `cuadro-mando.js` y `cuadro-mando.css`: cifras y barras en SVG sin librerías, tooltip, tabla "Ver los datos" en cada gráfico y modo oscuro.
  - `router.php` para probarlo en local con `php -S`.
- **Errores detectados en la IA:**
  - Las barras horizontales se dibujaban con un ancho fijo y, en las columnas estrechas, el texto se encogía hasta ser ilegible. Ahora se dibujan al ancho real de la columna y se redibujan al cambiar el tamaño de la ventana.
- **Cambios hechos por mí:**
  - Pedí un cuadro de mando completo con todas las métricas del #40.
  - Decidí dejar el bloque de navegación web (tiempo de visita y conversión del carrito) para cuando César y Celia confirmen cómo llegan esos datos por n8n.
- **Cómo lo validé:**
  - En local con MariaDB y datos de prueba (38 pedidos en 29 días, 141 ejecuciones, 10 incidencias y 10 propuestas), con Playwright: contraseña mala → mensaje de error; buena → cuadro de mando con todos los bloques; periodo de 7 días → 7 columnas; salir → vuelve al login y `/bo-api/metrics` sin clave da 401. Sin errores en la consola, en modo claro, oscuro y a 390 px de ancho sin desplazamiento horizontal.
  - Con la clave, `/metrics` sigue respondiendo igual para n8n; con una clave mala da 401 aunque haya sesión.

## 2026-11-17 · José (B) · Tarea 2: visitas de la tienda en el cuadro de mando y cierre de incidencias
- **Herramienta:** Claude Code (app de Claude, con acceso a la carpeta del repositorio).
- **Tarea:** recibir en el back-office las visitas que manda el flujo "Analítica" (#42 de César), enseñarlas en el cuadro de mando, registrar `page.viewed` en la tienda y cerrar el ticket cuando se ejecuta la propuesta del agente (rama `feature/metricas-web`).
- **Partes asistidas por IA:**
  - `POST /bo-api/web-metrics` (`backoffice/api/http/web_metrics.php`): validación de los días y sustitución de cada día con `INSERT ... ON DUPLICATE KEY UPDATE` en una transacción.
  - Tabla `bo_web_metrics` en `schema.sql` y `backoffice/db/web-metrics.sql` para crearla en el servidor sin borrar nada.
  - Bloque `web` y `visits_per_day` en `GET /bo-api/metrics` y bloque "Visitas de la tienda" en la página del cuadro de mando.
  - `page.viewed` en `BROWSER_EVENTS` (`api/http/orders.php`), el hook `usePageViews` del front y el nodo "Cerrar ticket" de `incidencias.json`.
  - Documentación: `docs/backoffice-api.md`, `docs/metricas.md` (apartado 6), `docs/integracion.md` y `docs/despliegue-tarea2.md`.
- **Errores detectados en la IA:**
  - En `docs/metricas.md` citó una página de ayuda de Google Analytics como "About Analytics sessions" cuando en realidad es "Dimensions and metrics". Se comprobaron los enlaces y se cambió por la página *Session*.
  - La primera versión del hook dejaba de contar una página si se pasaba por `/admin` y se volvía a ella.
  - En `docs/backoffice-api.md` escribió "cada hora (a y 10)" en vez de "a las y 10". Lo vio César en la revisión del #44 y lo corregí.
- **Cambios hechos por mí:**
  - Las reglas las fijaron Celia y César: cerrar solo después de "Ejecutar (simulado)" y no tras un rechazo, `page.viewed` sin producto, solo con la ruta y fuera de `INTEGRATED_EVENTS`, y el contrato de `{ "days": [...] }`.
  - Decidí que el front no mande `page.viewed` en `/admin` y que la confirmación se mande como `/pedido`, sin el código del pedido.
  - Revisé esta entrada del diario antes del commit.
  - Creé la rama `feature/metricas-web` sobre `feature/backoffice-cuadro-mando`, para encadenar el PR al #41, e hice el commit y el push desde VS Code.
- **Cómo lo validé:**
  - Con la IA, en un entorno aparte con MariaDB: `POST /web-metrics` con ayer y hoy → 200; repetirlo con otros valores sustituye el día sin duplicarlo; sin clave 401; lista vacía, fecha imposible, negativos, carrito mayor que visitas o un día repetido → 422 con los campos. `GET /metrics` sin visitas da `null` en las medias; con datos, tiempo medio = `visit_seconds / visits`.
  - En la tienda, `page.viewed` con `/catalogo` se guarda solo con la ruta (aunque llegue otro campo); con query o sin ruta, 400. Con Playwright: portada, catálogo con filtro, ficha y confirmación guardan `/`, `/catalogo`, `/producto/1` y `/pedido`; `/admin` no guarda nada.
  - Bloque "Visitas de la tienda" con Playwright, sin errores en la consola y a 390 px sin desplazamiento horizontal.
  - En el servidor, el 17/11 y con "Analítica" ya publicada, entré como gestor en `https://backoffice.theblacklotustcg.onl`. "Visitas de la tienda" sale sin errores y con los 30 días: del 19/10 al 16/11 con 0 visitas y el 17/11 con 1 (actualizado el 17/11/26 a las 21:10). El tiempo medio es 0 s porque la visita tiene un solo evento. "Visitas con carrito" marca 0 % y "De carrito a pedido" sale como "Sin datos", que es lo correcto si no hay visitas con carrito.

## 2026-11-16 · César (A) · Tarea 2: visitas de la tienda para el cuadro de mando (flujo "Analítica")
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** que el cuadro de mando de José tenga el tiempo medio de visita, el % de visitas que añaden al carrito y el % de esas que compran. Los datos están en `events` de la tienda y llegan al back-office por n8n: `GET /api/integration/visits` y el flujo "Analítica" (rama `feature/integracion-visitas`).
- **Partes asistidas por IA:**
  - La agrupación de los eventos en visitas (`IntegrationRepository::groupVisits`) y el endpoint con sus validaciones.
  - El flujo `analitica.json` y su documentación en `docs/integracion.md`.
  - El arnés de prueba de la agrupación.
- **Errores detectados en la IA:**
  - El endpoint del embudo de la rama `feature/integracion-embudo` contaba sesiones con `COUNT(DISTINCT session_id)`, pero el front guarda ese identificador en `localStorage` y no caduca: un mismo navegador sería una sola "visita" para siempre. Por eso las visitas se cortan tras 30 minutos sin actividad y el embudo no se ha subido.
- **Cambios hechos por mí:**
  - Decidí mandar la suma de segundos y no la media, para que el back-office pueda juntar varios días sin sesgo.
  - Decidí que "Analítica" pida ayer y hoy en cada vuelta (la de las 00:10 completa el día anterior) y que el back-office sustituya cada día, para que repetir una vuelta no duplique.
  - Los eventos de navegación no salen de la tienda: solo totales por día, sin sesiones ni datos de clientes.
- **Cómo lo validé:**
  - Arnés de `groupVisits`: 30 minutos justos siguen en la misma visita, 31 abren otra, otra sesión es otra visita y una compra sin carrito en esa visita no cuenta como conversión.
  - Tienda en local: `401` sin clave; `400` sin fechas, con `from` posterior a `to`, con más de 31 días y con el 30 de febrero. Con dos sesiones de prueba (vista de producto y carrito en una, vista en la otra): 2 visitas, 1 con carrito.
  - n8n 2.45.7 en local con "Ejecutar ahora" y un back-office falso: llega `POST /bo-api/web-metrics` con `X-Api-Key` y los `days` de ayer y hoy.

## 2026-11-17 · Celia (C) · Tarea 2: prueba completa en el servidor y número de incidencia en el grupo
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** probar el escenario 3 entero en el servidor, revisar el #41 y el #44 de José y arreglar el número que sale en el grupo (rama `fix/numero-incidencia-grupo`).
- **Partes asistidas por IA:** las pruebas en local de los dos PR, el cambio del nodo "Preparar propuesta" y la línea de `docs/integracion.md`.
- **Errores detectados:** en el servidor, la incidencia 5 de la tienda es el ticket 2 del back-office. El grupo veía "Incidencia 2" y al cliente le llegaba "Sobre tu incidencia 5". En local los dos números coincidían y no se había visto. Por eso la IA dio por hecho que la propuesta de la 5 no había llegado al grupo, cuando era la que salía como 2.
- **Cambios hechos por mí:** en el grupo va el número de la tienda, el mismo que ve el cliente, y entre paréntesis el ticket del back-office. Lo que se guarda en `POST /bo-api/proposals` sigue usando el id del back-office.
- **Cómo lo validé:**
  - En el servidor: incidencia 5 desde la web → botón de Telegram → *Iniciar*, y el bot contesta "Listo..." → propuesta en el grupo → Aprobar → me llegó la respuesta por el chat privado del bot.
  - El código de "Preparar propuesta" con datos de ejemplo (incidencia 5, ticket 2): el grupo ve "Incidencia 5 (ticket 2 del back-office)" y el cliente "Sobre tu incidencia 5".
- **Revisiones (16 y 17/11):**
  - #41 (login del gestor): en local, sin sesión redirige al login y `/bo-api/metrics` da 401; sin CSRF o con la contraseña mala no entra; cabeceras de seguridad y cookie `HttpOnly`. Con la contraseña buena entra y carga el cuadro. Aprobado, con la nota de cambiar en el servidor la contraseña de pruebas del gestor.
  - #44 (visitas, `page.viewed` y "Cerrar ticket"): probado en local junto con develop. `page.viewed` guarda solo la ruta, `web-metrics` valida y sustituye el día, el bloque de visitas sale bien y el PATCH a `cerrada` pone `closed_at`. Aprobado. Avisé de que la base del PR era la rama del #41 y no develop.

## 2026-11-16 · César (A) · Tarea 2: diagrama del flujo de datos de todo el sistema
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** un diagrama con los seis flujos de n8n, la tienda, el back-office y Telegram para la memoria (`docs/diagramas/flujo-datos-sistema.svg` y `.png`). El de `flujo-datos.svg` sigue como detalle de los pedidos.
- **Partes asistidas por IA:** el SVG (cajas, flechas numeradas y leyenda) y su conversión a PNG con resvg.
- **Cambios hechos por mí:** decidí qué pasos numerar (del evento al aviso de errores), marcar con A, B y C la parte de cada uno y dejar Analítica como pendiente de publicar.
- **Cómo lo validé:** revisé el PNG: cada flecha une los sistemas que se llaman de verdad en los flujos exportados y en `docs/integracion.md`.

## 2026-11-17 · César (A) · Tarea 2: Analítica publicada en el servidor
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** desplegar en el servidor el #42, el #43 y el #44 (tabla `bo_web_metrics`, back-office, "Incidencias" con "Cerrar ticket" y el arreglo de Celia, y "Analítica") y quitar "(pendiente de publicar)" del diagrama del sistema (rama `docs/analitica-publicada`).
- **Partes asistidas por IA:** los scripts del despliegue y el cambio del texto del diagrama.
- **Errores detectados:** `n8n execute` dentro del contenedor falla porque el puerto 5679 del *task broker* ya lo usa n8n; se lanza con `N8N_RUNNERS_BROKER_PORT` en otro puerto.
- **Cambios hechos por mí:** antes de reimportar "Incidencias" se compara con la copia de `~/import` y se guarda una copia; los **Ajustes** de producción (URLs, chat del grupo y revisor) se mantienen.
- **Cómo lo validé:**
  - En n8n están publicados los seis flujos y "Analítica" termina en `success`.
  - `bo_web_metrics` tiene el 16/11 y el 17/11, con la hora de Madrid en `updated_at`.
  - `/bo-api/health` y la página de login del back-office responden 200.

## 2026-11-17 · José (B) · Tarea 2: pantallas de pedidos, incidencias y registro del back-office
- **Herramienta:** Claude Code (app de Claude).
- **Tarea:** las pantallas que pide el plan para el gestor, además del cuadro de mando: pedidos (lista y detalle con líneas e historial), incidencias (con la propuesta del agente y la decisión) y registro de integración (rama `feature/backoffice-pantallas`).
- **Partes asistidas por IA:**
  - `backoffice/public/pedidos.php`, `pedido.php`, `incidencias.php`, `incidencia.php` y `registro.php`: páginas PHP de solo lectura, con la sesión del gestor, consultas preparadas y filtros por estado, resultado, flujo o evento.
  - Menú común, cabecera, paginación y etiquetas de los estados en `backoffice/api/lib/page.php`, y sus estilos en `cuadro-mando.css`, con modo oscuro.
- **Errores detectados en la IA:**
  - La primera versión buscaba los registros de un pedido solo por `order_code` en el `payload`. En los eventos de pedidos, n8n guarda el `data` del evento, que lleva `code`. Ahora se buscan los dos.
  - Al probar en local con `php -S ... router.php`, sin `-t public`, el CSS daba 404 y las páginas salían sin estilos. La guía de despliegue ya usaba `-t public`, pero el comentario de `router.php` no. Se ha corregido el comentario.
- **Cambios hechos por mí:**
  - Decidí hacerlas de solo lectura: los cambios de estado y las decisiones sobre las propuestas siguen llegando por n8n y por Telegram, y así el gestor no puede saltarse la revisión humana.
  - En las incidencias se muestra el número de la tienda, el mismo que ve el cliente, como en el arreglo de Celia para el grupo.
- **Cómo lo validé:**
  - En local con MariaDB y datos de prueba, usando Playwright: sin sesión, todas redirigen al login; con sesión, responden 200 y los filtros devuelven las filas esperadas; un pedido o una incidencia que no existen dan 404 con un aviso.
  - Sin errores en la consola, en modo claro y oscuro, y a 390 px sin desplazamiento horizontal: las tablas se desplazan dentro de su caja.
  - Pendiente: probarlo en el servidor cuando César reconstruya el back-office.

## 2026-11-17 · José (B) · Tarea 2: detalles de las pantallas del back-office tras revisarlas en el servidor
- **Herramienta:** Claude Code (app de Claude).
- **Tarea:** corregir tres cosas que vi al revisar en producción las pantallas del #47 (rama `fix/backoffice-pantallas-detalles`).
- **Partes asistidas por IA:**
  - Detalle del pedido: las líneas suman 21,10 € y el total es 27,93 €. La IA buscó en `PriceCalculator.php` de la tienda de dónde sale la diferencia: las líneas van sin IVA, y el total resta el cupón (2,11 €) y suma el envío (4,95 €) y el IVA (3,99 €). El back-office no recibe esas partes por separado, así que ahora la tabla muestra la suma de las líneas, la diferencia ("IVA, envío y descuento") y el total, con una nota.
  - Etiquetas de los tipos de caso del agente (`dano_envio` → "Daño en el envío") en la lista y el detalle de incidencias.
  - El motivo de la incidencia sin el código de pedido que la tienda le añade detrás ("Otro · PED-000018" → "Otro"), igual que en las métricas. Es solo de la pantalla: en la base de datos se guarda igual.
- **Errores detectados en la IA:** en la primera versión, la nota del pedido llevaba las reglas del envío de la tienda (4,95 €, gratis desde 50 €). Las quité, porque si cambian en la tienda el back-office diría algo falso.
- **Cambios hechos por mí:** revisé las cinco pantallas en el servidor y detecté los tres problemas. Decidí no tocar la justificación del agente ("corresponde a devolucion"), porque es texto del modelo y tiene que quedar como lo escribió.
- **Cómo lo validé:**
  - En local, con un pedido y una incidencia iguales a los del servidor y usando Playwright: el detalle muestra 21,10 €, +6,83 € y 27,93 €, las incidencias muestran "Devolución" y "Daño en el envío", y el motivo sale como "Otro". Sin errores en la consola y a 390 px sin desplazamiento horizontal.
  - Pendiente: comprobarlo en el servidor cuando César reconstruya el back-office.

## 2026-11-17 · César (A) · Tarea 2: pantallas del gestor en los diagramas y en el servidor
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** desplegar en el servidor las pantallas del back-office del #47 y poner en los diagramas lo que ya tiene el gestor (rama `docs/diagrama-pantallas-gestor`).
- **Partes asistidas por IA:** el script del despliegue y el cambio de texto de los dos diagramas.
- **Cambios hechos por mí:** en el diagrama del sistema, el gestor tiene cuadro de mando, pedidos, incidencias y registro de integración. En el de pedidos, "pedidos, registro y cuadro de mando", porque las incidencias no salen en ese diagrama.
- **Cómo lo validé:** en el servidor, con el repositorio en `5143081` y el back-office reconstruido, `/bo-api/health` y la página de login dan 200, y sin sesión el cuadro de mando, pedidos, incidencias y registro redirigen al login. Los PNG se revisaron después de generarlos.

## 2026-11-17 · Celia (C) · Tarea 2: README de la Tarea 2
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** poner al día el README para la entrega (rama `docs/readme-tarea2`). Seguía como en la Tarea 1 y el enunciado pide instrucciones de instalación y ejecución, usuarios de prueba y limitaciones conocidas.
- **Partes asistidas por IA:** la redacción, a partir de `docs/despliegue-tarea2.md`, `docs/integracion.md`, `docs/backoffice-api.md` y `docs/metricas.md`.
- **Cambios hechos por mí:**
  - Pedí las URL de los tres sistemas y el bot, el reparto de la Tarea 2, cómo arrancar en local el back-office y n8n, cómo probar los tres escenarios con la web y con los payloads, y las limitaciones de la Tarea 2.
  - Lo de la Tarea 1 se queda igual.
  - La contraseña del gestor la pongo yo, no la IA.
- **Cómo lo validé:**
  - Comprobé contra el código lo que dice el README: que las pantallas del gestor solo tienen filtros (formularios GET), los 12 reintentos de "Reintentos", que el cuadro de mando compara la confianza de aprobadas y rechazadas, y quién hizo cada fichero (`git log`).
  - Las tres URL de salud responden `ok`.

## 2026-11-17 · César (A) · Tarea 2: alta de los pedidos en pendiente de pago
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** corregir el historial de estados que vio José en el back-office (rama `fix/pedidos-alta-pendiente`).
- **Partes asistidas por IA:** el diagnóstico con el registro de integración, el cambio de "Transformar" y el SQL que corrige los datos del servidor.
- **Errores detectados en la IA:** la primera versión de "Transformar" daba de alta el pedido con el estado que tenía en la tienda al procesar el evento, no con el de creación. Si el `order.created` llegaba tarde (la primera carga del 14/11 y los pedidos que recuperó el ramal de huecos), el historial empezaba en el estado final: PED-000017 salía como "Alta como Cancelado" seguido de "Cancelado → Pagado → Cancelado". En PED-000015 y PED-000016 el alta ya era "pagado", así que el cambio a pagado se descartó como `duplicado` y no quedó en el historial.
- **Cambios hechos por mí:** lo detectó José revisando las pantallas del gestor. Decidí dar siempre de alta en `pendiente_pago`, porque en la tienda todos los pedidos nacen así (`OrderRepository.php`). Los cambios siguientes llevan el pedido a su estado real y en orden. Con el visto bueno de José, corregí el historial de los cinco pedidos afectados en el servidor, con una copia antes.
- **Cómo lo validé:**
  - En el servidor, después del SQL, PED-000014 a PED-000018 empiezan en `pendiente_pago` y siguen en orden hasta su estado actual, que no cambia.
  - El 15 y el 16 tienen el paso a pagado con los eventos 80 y 88, y no queda ningún alta distinta de `pendiente_pago`.

## 2026-11-19 · César (A) · Tarea 2: README, idempotencia y flujo "Errores"
- **Herramienta:** Claude Code (Claude Opus 5.5) desde VS Code.
- **Tarea:** repasar el README antes de grabar el vídeo y completar lo que faltaba de la parte A.
- **Partes asistidas por IA:** la revisión del README y la redacción de los cambios.
- **Errores detectados en la IA:** ninguno en estos cambios. Al revisar, la IA encontró dos textos que venían de la Tarea 1 y ya no valían: vaciar `events` antes del vídeo y el ejemplo de pasar un pedido pagado a enviado.
- **Cambios hechos por mí:** decidí qué se añadía. En el escenario 2, cómo se evitan los duplicados y el ejemplo correcto (de pagado a en preparación). Debajo de los payloads, qué hace "Errores". En el README y en `docs/despliegue.md`, el aviso de no vaciar `events`.
- **Cómo lo validé:**
  - La respuesta `duplicado` sale de `backoffice/api/http/orders.php` (por código del pedido y por `event_id`).
  - El mensaje de "Errores" lleva el flujo, el paso y la causa, como en `integration/n8n/workflows/errores.json`.
  - `pagado → enviado` no es una transición válida en `OrderStatusService`.
