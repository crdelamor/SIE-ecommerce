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

## 2026-10-14 · José (B) · Catálogo: API de productos, página principal, catálogo y ficha
- **Herramienta:** Claude Code.
- **Tarea:** terminar mi parte del plan: `api/http/products.php` (categorías, listado y ficha, con `product.viewed` y `price_with_tax`), la página principal, el catálogo con filtro por juego y la ficha de producto con las versiones de cada carta, y la limpieza previa a la entrega (quitar la página de muestra `/muestra` y añadir un favicon).
- **Partes asistidas por IA:** `api/http/products.php`, `api/services/CatalogService.php`, `api/repositories/ProductRepository.php`, la columna `card_ref` de `db/schema.sql` y `db/seed.sql`, `frontend/src/pages/home`, `catalog` y `product` (páginas, estilos y `catalogApi.js`), `hooks/useAsync.js`, `components/productFormat.js`, los cambios en `ProductCard.jsx` y `components.css`, y el favicon.
- **Errores detectados en la IA:**
  - Un manejador de eventos se llamó `useFallback`; por convención `use…` se reserva para hooks y se renombró a `showPlaceholder`.
  - Con `npm run dev` Vite se cerraba por un problema del vigilante de ficheros con rutas cortas de Windows; las pruebas se hicieron con `vite preview` sobre el resultado compilado.
  - La captura de pantalla de móvil engañaba (Chrome sin ventana tiene un ancho mínimo y la captura completa no desplaza la página, así que los bloques animados salían vacíos). Se cambió por Puppeteer con 390 px exactos y scroll real.
  - La prueba automática detectó que faltaba un favicon (el navegador recibía un 404 en cada página) y se añadió.
  - Los fallos de contraste que avisó la auditoría automática en el catálogo eran un artefacto: se medía con las tarjetas a medias de la animación de entrada; con la animación terminada no hay ninguno.
- **Cambios hechos por mí:**
  - Decidí que las versiones de una misma carta (nueva, usada, foil) se agrupan con una columna nueva `products.card_ref`, para poder mostrarlas en la ficha, cada una con su imagen. Es un cambio de `schema.sql` y de `seed.sql`, que hay que avisar al grupo y reimportar.
  - Decidí que si falla el guardado de `product.viewed` la ficha se muestra igualmente y el error queda en el log del servidor, porque es una lectura.
  - El payload de `product.viewed` es `category`, `product_type`, `card_condition`, `language` e `is_foil`, sin datos personales.
  - Mantuve el `Section` y el `Container` tal cual: Celia ya evitó el margen doble con una regla de CSS.
- **Cómo lo validé:**
  - `php -l` en toda la API y `npm run build` sin errores.
  - 43 comprobaciones de la API con una base de datos simulada que lee el `seed.sql` real: contenido de los tres endpoints, precios con IVA, filtros, errores 400 y 404 (también con intentos de inyección), versiones, que un producto retirado o desactivado no salga, y que `product.viewed` quede registrado con la sesión y sin datos personales. Si falla el guardado del evento, la ficha sigue respondiendo 200.
  - 33 comprobaciones en Chrome real con Puppeteer: navegación de la portada al catálogo, filtro por juego, botón atrás, ficha, añadir al carrito (se abre el panel y el carrito guarda el producto), cambio de versión, producto agotado, error de producto o juego inexistente, acceso con teclado, y móvil de 390 px sin desbordamiento horizontal. La auditoría de accesibilidad automática (axe, WCAG A y AA) no encuentra violaciones. Las animaciones aparecen con el scroll y con "reducir movimiento" se ven desde el principio.
  - No se ha probado contra MySQL real, porque no hay MySQL en el equipo donde se desarrolló; las consultas son sencillas, pero hay que importar `schema.sql` y `seed.sql` y repetir las tres llamadas a la API contra la base de datos.
