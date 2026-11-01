# Contrato de la API del back-office

API de `backoffice/api/` (Tarea 2). La llama n8n; las pantallas del back-office usan su propio login.
Los campos son los de las tablas `bo_*` del plan de trabajo v2 (sección 2.2) y las rutas, las de la sección 2.3.
Nadie cambia este contrato sin avisar al grupo.

> El back-office es otro sistema: no lee la base de datos de la tienda y no comparte código con `/api`. Todo lo que sabe le llega por n8n.

## Reglas comunes

| Tema | Regla |
|---|---|
| URL base | `https://backoffice.theblacklotustcg.onl/bo-api` (subdominio acordado; en local, `http://localhost:8080/bo-api` o el puerto que se use). El despliegue se describe en `docs/despliegue-tarea2.md`. |
| Formato | JSON en UTF-8. Las peticiones con cuerpo llevan `Content-Type: application/json`. |
| Autenticación | Cabecera `X-Api-Key: <clave del back-office>`. Es distinta de la clave de la tienda y solo está en las variables de entorno de la plataforma y en las credenciales de n8n. `GET /bo-api/health` no la pide; `GET /bo-api/metrics` acepta también la sesión del gestor. |
| Importes | Número con 2 decimales (`15.72`). En la base de datos son `DECIMAL(10,2)`. |
| Fechas | ISO 8601 con zona (`2026-11-05T10:02:11+01:00`). |
| Booleanos | `true` / `false` (en la base de datos, `TINYINT(1)`). |
| Errores | Siempre `{ "error": "mensaje" }`, como en la API de la tienda. En el 422 se añade `"fields"` con la lista de campos que faltan o no son válidos. |

### Códigos de respuesta

| Código | Cuándo |
|---|---|
| `201` | Se ha creado el registro. |
| `200` | Consulta correcta, registro actualizado o **duplicado** (el registro ya existía y no se vuelve a crear). |
| `401` | Falta la cabecera `X-Api-Key` o no es válida. |
| `404` | El pedido, ticket o propuesta no existe. |
| `405` | Método no permitido en esa ruta. |
| `422` | Faltan campos obligatorios o tienen un valor no válido. |
| `500` | Error interno (sin detalles en la respuesta; se apunta en el log del servidor). |

Ejemplos de error:

```json
{ "error": "Falta la cabecera X-Api-Key o no es válida" }
```

```json
{ "error": "Faltan campos obligatorios o no son válidos", "fields": ["customer.email", "lines[0].quantity"] }
```

### Valores cerrados

| Campo | Valores |
|---|---|
| Estado del pedido (`status`) | `pendiente_pago`, `pagado`, `en_preparacion`, `enviado`, `entregado`, `cancelado`, `incidencia` (los mismos de la tienda) |
| Estado de la carta (`card_condition`) | `NM`, `LP`, `MP` o `null` en sobres |
| Estado del ticket (`status`) | `abierta`, `en_curso`, `cerrada` |
| Prioridad del ticket (`priority`) | `baja`, `media`, `alta` |
| Estado final de la propuesta (`final_status`) | `pendiente_revision`, `aprobado`, `rechazado`, `ejecutado_simulado` |
| Resultado del registro (`result`) | `ok`, `error`, `duplicado`, `pendiente` |

---

## Salud

### `GET /bo-api/health`

Comprueba que el back-office desplegado responde y llega a su base de datos. No pide clave ni devuelve datos sensibles.

Respuesta `200`:

```json
{ "status": "ok", "database": "ok", "time": "2026-11-05T10:02:11+01:00" }
```

Si no llega a la base de datos responde `503` con `{ "status": "error", "database": "error" }`.

---

## Pedidos

### `POST /bo-api/orders`

Crea el pedido con su cliente y sus líneas. Lo llama el flujo "Pedidos" de n8n (escenario 1).

- El cliente se busca por `email`: si ya existe en `bo_customers` se reutiliza; si no, se crea.
- Se guarda el estado inicial en `bo_order_status_log` (`from_status` = `null`) con el `event_id`.
- `synced_at` lo pone el back-office con la hora en que recibe el pedido.
- Todo va en una transacción: o se crean pedido, cliente y líneas, o no se crea nada.

Cabeceras: `X-Api-Key`, `Content-Type: application/json`.

Cuerpo:

```json
{
  "event_id": 812,
  "code": "PED-000014",
  "customer": { "name": "Lucía Ficticia", "email": "lucia@example.com" },
  "status": "pagado",
  "total": 15.72,
  "placed_at": "2026-11-05T10:02:11+01:00",
  "lines": [
    {
      "product_name": "Black Lotus (Unlimited)",
      "game": "Magic: The Gathering",
      "set_name": "Unlimited Edition",
      "card_condition": "NM",
      "language": "Inglés",
      "is_foil": false,
      "quantity": 1,
      "unit_price": 12.99,
      "line_total": 12.99
    }
  ]
}
```

Obligatorios: `event_id`, `code`, `customer.name`, `customer.email`, `status`, `total`, `placed_at` y al menos una línea con `product_name`, `game`, `quantity` (> 0), `unit_price` y `line_total`. `set_name`, `card_condition` y `language` pueden ser `null` (por ejemplo, en un sobre); `is_foil` es `false` si no llega.

Respuesta `201`:

```json
{ "id": 1, "code": "PED-000014", "result": "creado" }
```

Respuesta `200` si el `code` ya existía (no se toca nada):

```json
{ "id": 1, "code": "PED-000014", "result": "duplicado" }
```

Errores: `401`, `422`.

### `PATCH /bo-api/orders/{code}/status`

Cambia el estado del pedido y guarda el cambio en `bo_order_status_log`. Lo llama el flujo "Pedidos" (escenario 2) y el flujo "Reintentos".

Cuerpo:

```json
{ "event_id": 815, "status": "enviado", "changed_at": "2026-11-06T09:30:00+01:00" }
```

Obligatorios: `event_id`, `status`. Si no llega `changed_at`, se usa la hora del servidor.

Respuesta `200`:

```json
{ "code": "PED-000014", "from_status": "pagado", "to_status": "enviado", "result": "actualizado" }
```

Respuesta `200` si ese `event_id` ya se aplicó a este pedido (no se repite el cambio):

```json
{ "code": "PED-000014", "result": "duplicado" }
```

Errores: `401`, `404` si el pedido aún no está en el back-office (n8n lo deja como `pendiente` en el registro para reintentarlo), `422`.

### `GET /bo-api/orders/{code}`

Pedido con cliente, líneas e historial de estados. Es la herramienta `consultar_pedido` del agente, así que **no devuelve el email del cliente** (al modelo solo le llegan los datos del pedido).

Respuesta `200`:

```json
{
  "id": 1,
  "code": "PED-000014",
  "customer_id": 3,
  "customer_name": "Lucía Ficticia",
  "status": "enviado",
  "total": 15.72,
  "placed_at": "2026-11-05T10:02:11+01:00",
  "synced_at": "2026-11-05T10:02:13+01:00",
  "lines": [
    {
      "product_name": "Black Lotus (Unlimited)",
      "game": "Magic: The Gathering",
      "set_name": "Unlimited Edition",
      "card_condition": "NM",
      "language": "Inglés",
      "is_foil": false,
      "quantity": 1,
      "unit_price": 12.99,
      "line_total": 12.99
    }
  ],
  "history": [
    { "from_status": null, "to_status": "pagado", "changed_at": "2026-11-05T10:02:11+01:00", "event_id": 812 },
    { "from_status": "pagado", "to_status": "enviado", "changed_at": "2026-11-06T09:30:00+01:00", "event_id": 815 }
  ]
}
```

Errores: `401`, `404`.

---

## Incidencias (tickets)

### `POST /bo-api/tickets`

Crea el ticket asociado al cliente y, si lo hay, al pedido. Lo llama el flujo "Incidencias" (escenario 3).

- El cliente se busca o se crea por `email`, igual que en los pedidos.
- `order_code` es opcional (en la tienda el pedido del ticket es opcional). No hace falta que el pedido exista en el back-office.
- `status` empieza en `abierta`, `priority` en `media` y `opened_at` es la fecha que llega de la tienda.

Cuerpo:

```json
{
  "event_id": 820,
  "shop_ticket_id": 1,
  "customer": { "name": "Lucía Ficticia", "email": "lucia@example.com" },
  "order_code": "PED-000014",
  "reason": "La carta ha llegado doblada",
  "opened_at": "2026-11-06T18:15:00+01:00"
}
```

Obligatorios: `event_id`, `shop_ticket_id`, `customer.name`, `customer.email`, `reason`, `opened_at`.

Respuesta `201`:

```json
{ "id": 1, "shop_ticket_id": 1, "result": "creado" }
```

Respuesta `200` si el `shop_ticket_id` ya existía:

```json
{ "id": 1, "shop_ticket_id": 1, "result": "duplicado" }
```

Errores: `401`, `422`.

### `PATCH /bo-api/tickets/{id}`

Cambia el estado, la prioridad o el chat de Telegram del ticket. `{id}` es el id del back-office (`bo_tickets.id`). Lo usan la herramienta `clasificar_incidencia` (prioridad) y el flujo "Telegram" (chat).

Todos los campos son opcionales, pero tiene que llegar al menos uno:

```json
{ "status": "en_curso", "priority": "alta", "telegram_chat_id": "123456789" }
```

- `telegram_chat_id` se guarda en el **cliente** del ticket (`bo_customers.telegram_chat_id`), porque es donde está en la tabla. El flujo "Telegram" comprueba la firma del enlace antes de llamar aquí.
- Al pasar a `cerrada` se rellena `closed_at`; si se reabre, vuelve a `null`.

Respuesta `200`:

```json
{ "id": 1, "status": "en_curso", "priority": "alta", "closed_at": null, "result": "actualizado" }
```

Errores: `401`, `404`, `422` (cuerpo vacío o valor fuera de la lista).

### `GET /bo-api/customers/{id}/tickets`

Incidencias anteriores del cliente. Es la herramienta `consultar_historial_cliente` del agente; tampoco devuelve el email ni el chat de Telegram.

Respuesta `200`:

```json
{
  "customer_id": 3,
  "tickets": [
    {
      "id": 1,
      "shop_ticket_id": 1,
      "order_code": "PED-000014",
      "reason": "La carta ha llegado doblada",
      "status": "cerrada",
      "priority": "alta",
      "opened_at": "2026-11-06T18:15:00+01:00",
      "closed_at": "2026-11-07T12:00:00+01:00"
    }
  ]
}
```

Un cliente sin incidencias devuelve `"tickets": []`. Errores: `401`, `404` si el cliente no existe.

---

## Propuestas del agente

### `POST /bo-api/proposals`

Guarda la salida estructurada del agente de IA. Es la herramienta `registrar_propuesta`. Siempre se crea con `final_status` = `pendiente_revision`, aunque llegue otro valor: la decisión la toma una persona.

Cuerpo:

```json
{
  "ticket_id": 1,
  "case_type": "producto_danado",
  "consulted_data": {
    "order": "PED-000014",
    "lines": 1,
    "previous_tickets": 0
  },
  "proposed_action": "Ofrecer el cambio de la carta por otra en estado NM",
  "justification": "La carta se vendió como NM y el cliente no tiene incidencias anteriores",
  "confidence": 0.82,
  "risks": "Sin foto del daño; conviene pedirla antes de enviar la nueva"
}
```

Obligatorios: `ticket_id`, `case_type`, `consulted_data` (objeto JSON), `proposed_action`, `justification`, `confidence` (número entre 0 y 1). `risks` es opcional.

Respuesta `201`:

```json
{ "id": 1, "ticket_id": 1, "final_status": "pendiente_revision" }
```

Errores: `401`, `404` si el ticket no existe, `422`.

### `PATCH /bo-api/proposals/{id}`

Registra la decisión humana (botones Aprobar / Rechazar de Telegram) y, después, que la acción se ha ejecutado.

Cuerpo:

```json
{ "final_status": "aprobado", "reviewed_by": "@revisor_pruebas" }
```

- `final_status`: `aprobado` o `rechazado` desde `pendiente_revision`; `ejecutado_simulado` solo desde `aprobado`.
- `reviewed_by` es obligatorio al aprobar o rechazar: el alias de Telegram (cuenta de pruebas) de quien aprueba, nunca un nombre real. `reviewed_at` lo pone el back-office.
- Cualquier otro paso (por ejemplo, de `rechazado` a `aprobado`) da `422`.

Respuesta `200`:

```json
{ "id": 1, "final_status": "aprobado", "reviewed_by": "@revisor_pruebas", "reviewed_at": "2026-11-06T18:20:00+01:00" }
```

Errores: `401`, `404`, `422`.

---

## Registro de integración

### `POST /bo-api/integration-log`

Registra el resultado de cada ejecución de n8n, en todas las ramas (bien, error, duplicado o pendiente). Es la base de la trazabilidad y de las métricas de integración.

Cuerpo:

```json
{
  "event_id": 815,
  "event_type": "order.status_changed",
  "workflow": "Pedidos",
  "result": "pendiente",
  "error_type": "pedido_no_existe",
  "message": "El back-office devolvió 404 para PED-000014",
  "payload": { "code": "PED-000014", "status": "enviado" },
  "n8n_execution_id": "1234"
}
```

Obligatorios: `event_type`, `workflow`, `result`. `event_id` puede ser `null` en un evento incompleto que llegó sin él. `error_type` y `message` se esperan cuando `result` no es `ok`. Los tipos de error están en `docs/integracion.md` (C). Los del flujo de pedidos (A) son `payload_incompleto`, `secreto_invalido`, `pedido_no_existe`, `tienda_no_disponible` y `backoffice_no_disponible`.

Cada ejecución es una fila nueva, también los reintentos: así se ve cuántas veces se intentó cada evento.

Respuesta `201`:

```json
{ "id": 42 }
```

Errores: `401`, `422`.

### `GET /bo-api/integration-log?result=pendiente`

Lista los registros con ese resultado. El flujo "Reintentos" la llama cada 5 minutos con `result=pendiente`. Solo devuelve los eventos cuyo **último** registro sigue en `pendiente`: si un reintento posterior quedó en `ok`, ese evento ya no sale.

Parámetros: `result` (obligatorio, uno de los cuatro valores) y `limit` (opcional, 50 por defecto, máximo 200).

Respuesta `200`:

```json
{
  "items": [
    {
      "id": 42,
      "event_id": 815,
      "event_type": "order.status_changed",
      "workflow": "Pedidos",
      "result": "pendiente",
      "error_type": "pedido_no_existe",
      "message": "El back-office devolvió 404 para PED-000014",
      "payload": { "code": "PED-000014", "status": "enviado" },
      "n8n_execution_id": "1234",
      "created_at": "2026-11-06T09:30:02+01:00"
    }
  ]
}
```

Errores: `401`, `422` si falta `result` o no es válido.

---

## Cuadro de mando

### `GET /bo-api/metrics`

Datos del cuadro de mando, calculados en el momento con consultas a las tablas `bo_*`. Ninguna cifra está escrita a mano. Acepta `X-Api-Key` o la sesión del gestor. La justificación de cada métrica va en `docs/metricas.md`.

Respuesta `200` (las métricas definitivas se cierran en `docs/metricas.md`):

```json
{
  "generated_at": "2026-11-07T12:00:00+01:00",
  "orders_per_day": [ { "day": "2026-11-05", "orders": 3 }, { "day": "2026-11-06", "orders": 1 } ],
  "total_amount": 58.40,
  "orders_by_status": { "pagado": 2, "enviado": 1, "cancelado": 1 },
  "top_games": [ { "game": "Magic: The Gathering", "units": 4 } ],
  "top_products": [ { "product_name": "Black Lotus (Unlimited)", "units": 2 } ],
  "tickets": { "open": 1, "avg_resolution_hours": 17.75 },
  "integration": { "ok": 10, "error": 2, "duplicado": 1, "pendiente": 1, "errors_by_type": { "payload_incompleto": 1, "pedido_no_existe": 1 } },
  "proposals": { "total": 3, "aprobado": 2, "rechazado": 1, "pendiente_revision": 0, "ejecutado_simulado": 2 }
}
```

Errores: `401`.

---

## Decisiones tomadas en este contrato

1. **Duplicados con 200, no con error** (acordado). El plan pide `200 duplicado`. Así n8n no entra en la rama de error por un evento repetido y puede apuntar `result: duplicado` en el registro.
2. **Cliente por email** (acordado). `bo_customers` no tiene un id de la tienda, así que el email es la forma de no duplicar clientes entre pedidos y tickets.
3. **`product_name` en las líneas** (acordado). La guía de arranque no lo nombraba, pero está en `bo_order_lines` (2.2) y sin él no se puede saber qué carta es.
4. **`telegram_chat_id` en el cliente.** El plan lo pone en `bo_customers` y no en `bo_tickets`; `PATCH /tickets/{id}` lo guarda en el cliente del ticket.
5. **Prioridades `baja`/`media`/`alta`.** El plan no las fija; se proponen estas tres y se cierran con C.
6. **Las herramientas del agente no devuelven email ni chat.** El plan dice que al modelo solo le llegan motivo, mensaje y datos del pedido.
7. **Idempotencia del cambio de estado por `event_id`.** `bo_order_status_log` guarda el `event_id`, así que un reintento del mismo cambio no crea otra fila de historial.
8. **El agente no decide el estado final.** `POST /proposals` siempre empieza en `pendiente_revision` y las transiciones de `PATCH /proposals/{id}` están cerradas.
9. **`GET /orders/{code}` y `GET /customers/{id}/tickets` también piden `X-Api-Key`.** Aunque solo leen, el back-office tiene datos de clientes.
10. **`reviewed_by` guarda el alias de Telegram** de quien aprueba (cuenta de pruebas), nunca un nombre real. Propuesto por A; lo confirma C.
