# Contrato de integración (Tarea 2)

Qué envía la tienda a n8n, qué le puede pedir n8n a la tienda y qué valores usamos en el registro de integración y en el agente. Lo que n8n envía al back-office está en `docs/backoffice-api.md` (B).
Nadie cambia este contrato sin avisar al grupo.

```
Tienda (logEvent)  --webhook-->  n8n  --API-->  Back-office
        ^                         |
        +---- GET /api/integration/* (datos completos)
```

## 1. Sobre de los eventos

La tienda guarda el evento con `logEvent()` y, cuando la transacción ya se ha confirmado, lo envía al webhook de n8n (`POST /webhook/tienda`) con este sobre. Dentro de `data` va el mismo payload que guarda `logEvent()`.

```json
{
  "event_id": 227,
  "type": "support.requested",
  "entity_type": "ticket",
  "entity_id": 5,
  "occurred_at": "2026-11-12T09:18:03+01:00",
  "source": "tienda",
  "data": { "reason": "dano_envio", "order_code": "PED-000027", "order_linked": true }
}
```

| Campo | Tipo | Obligatorio | Notas |
|---|---|---|---|
| `event_id` | entero > 0 | sí | `events.id` de la tienda. Es la clave de la idempotencia y de los reintentos. |
| `type` | texto | sí | Uno de los 4 tipos del apartado 2. |
| `entity_type` | texto | no | `order` o `ticket`. |
| `entity_id` | entero | no | Id en la tienda. En `support.requested` es el id del ticket (`shop_ticket_id` en el back-office). |
| `occurred_at` | fecha ISO 8601 con zona | sí | `events.created_at`. En `order.status_changed` se usa como `changed_at` del back-office. |
| `source` | texto | no | Siempre `tienda`. |
| `data` | objeto | sí | Depende del tipo (apartado 2). |

- **Cabecera** `X-Webhook-Secret` con el secreto compartido. Está en `api/config.php` (`integration.webhook_secret`) y en la credencial `Tienda X-Webhook-Secret` de n8n, nunca en el repositorio.
- **Respuesta del webhook**: n8n responde `200` en cuanto lo recibe (no espera al back-office), así la compra no se retrasa. Con el secreto malo o sin él, `403` y el flujo no se ejecuta.
- **Límite**: la tienda espera como mucho 2 s. Si n8n no responde, la compra sigue, deja de enviar y el flujo "Reintentos" recupera el evento con `GET /api/integration/events`.
- Los eventos de navegación (`product.viewed`, `cart.item_added`, `checkout.started`) no se envían: se quedan en la tienda.

## 2. Eventos integrados

| Tipo | Lo lanza | `data` | Obligatorios en `data` | Flujo de n8n |
|---|---|---|---|---|
| `order.created` | `OrderService` al confirmar el checkout | `code`, `total`, `items`, `replaces` | `code` | Pedidos (escenario 1) |
| `payment.simulated` | `PaymentService` al pagar | `code`, `method`, `status` (`approved`/`rejected`), `amount` | `code`, `status` | Pedidos (solo registro) |
| `order.status_changed` | `OrderStatusService` (pago aprobado o administración) | `code`, `from`, `to`, `admin_id`, `restocked` | `code`, `from`, `to` | Pedidos (escenario 2) |
| `support.requested` | `SupportService` al enviar el formulario de soporte | `reason`, `order_code`, `order_linked` | ninguno | Incidencias (escenario 3) |

Notas:

- El evento lleva solo lo mínimo. Los datos completos (cliente, líneas, mensaje) los pide n8n a la tienda con `X-Api-Key` (apartado 3).
- `support.requested` no lleva nombre, email ni mensaje. `reason` es la clave del motivo (`estado_conservacion`, `idioma_version`, `dano_envio`, `falta_producto`, `otro`). `order_code` es lo que escribió el cliente; `order_linked` es `true` solo si el pedido existe y el email coincide con el del pedido.
- **Validación en n8n** (nodo "Validar"): `event_id` entero positivo, `type` y `occurred_at` presentes, `type` conocido y los obligatorios de `data` de la tabla. Si falla algo, rama de error con `payload_incompleto` y la lista de campos.

Ejemplos reales de una prueba en local: `integration/payloads/escenario1.json`, `escenario2.json`, `escenario3.json` y `incompleto.json` (sin `data.code`).

## 3. Endpoints de la tienda para n8n

Todos piden la cabecera `X-Api-Key` con la clave de integración de la tienda (`integration.api_key`), distinta de la del back-office. Sin clave o con una mala, `401`.

| Método y ruta | Dueño | Devuelve |
|---|---|---|
| `GET /api/integration/orders/{code}` | A | Pedido con cliente (nombre y email), líneas con los datos TCG y pagos sin referencia. `404` si no existe. |
| `GET /api/integration/events?after_id=&limit=` | A | Eventos integrados con id mayor que `after_id`, con el mismo sobre. `400` si falta `after_id` o `limit` no va de 1 a 500. |
| `GET /api/integration/tickets/{id}` | C | Incidencia para dar de alta el ticket en el back-office. `404` si no existe. |

### `GET /api/integration/tickets/{id}`

```json
{
  "id": 5,
  "status": "abierta",
  "subject": "Daño en el envío · PED-000027",
  "message": "La carta ha llegado doblada por una esquina.",
  "opened_at": "2026-11-12T09:18:02+01:00",
  "order_code": "PED-000027",
  "customer": { "name": "Laura Prueba", "email": "laura.prueba@example.com" }
}
```

- `order_code` solo viene si el ticket quedó vinculado al pedido (mismo email). Si el cliente escribió un código que no es suyo, llega `null`, así un ticket nunca se une al pedido de otra persona en el back-office.
- Sin dirección ni teléfono: el back-office no los necesita.
- El flujo "Incidencias" lo pasa a `POST /bo-api/tickets` así: `shop_ticket_id` = `id`, `reason` = `subject`, `opened_at`, `order_code` y `customer`. El `message` no se guarda en el back-office: solo se le pasa al agente.

## 4. Registro de integración

Cada ejecución acaba en `POST /bo-api/integration-log`, también en la rama de error, con el id de ejecución de n8n. Campo `workflow`: `Pedidos`, `Reintentos`, `Incidencias` o `Telegram`.

| `result` | Cuándo |
|---|---|
| `ok` | Se ha aplicado en el back-office (también `desfasado` del cambio de estado). |
| `duplicado` | El back-office ya lo tenía (mismo `code`, `shop_ticket_id` o `event_id`). |
| `pendiente` | No se ha podido aplicar por algo que se arregla solo (red, 5xx, pedido que aún no está). Lo reintenta "Reintentos". |
| `error` | No se arregla reintentando (datos mal, clave mala, no existe en la tienda). |

### Tipos de error (`error_type`)

| `error_type` | `result` | Flujo | Cuándo |
|---|---|---|---|
| `payload_incompleto` | error | todos | Falta un campo obligatorio del sobre o de `data`. |
| `secreto_invalido` | — | Pedidos | Secreto del webhook malo. n8n responde 403 antes de ejecutar el flujo, así que no llega al registro: solo se ve en el `error_log` de la tienda. |
| `clave_no_valida` | error | todos | La tienda o el back-office responden 401. |
| `datos_no_validos` | error | todos | El back-office responde 422, o la tienda o el back-office dan otro 4xx. |
| `tienda_no_disponible` | pendiente | todos | La tienda no responde o da 5xx. |
| `backoffice_no_disponible` | pendiente | todos | El back-office no responde o da 5xx. |
| `pedido_no_existe` | pendiente | Pedidos | El back-office da 404 al cambiar el estado: el pedido aún no ha llegado. |
| `pedido_no_existe_en_tienda` | error | Pedidos | La tienda da 404 al pedir el pedido. |
| `ticket_no_existe_en_tienda` | error | Incidencias | La tienda da 404 al pedir la incidencia. |
| `agente_no_disponible` | pendiente | Incidencias | El modelo de IA no responde o se ha pasado del límite gratuito. El ticket ya está creado; falta la propuesta. |
| `salida_agente_no_valida` | error | Incidencias | El agente devuelve un JSON que no cumple el apartado 5. No se guarda la propuesta. |
| `firma_no_valida` | error | Telegram | El enlace de Telegram no lleva una firma válida para ese ticket. No se guarda el chat. |

## 5. Salida del agente de IA

El agente del flujo "Incidencias" devuelve siempre este JSON (parser estructurado de n8n). Se guarda con `POST /bo-api/proposals` y cubre lo que pide el apartado 6 del enunciado.

```json
{
  "case_type": "dano_envio",
  "consulted_data": { "order": "PED-000027", "order_status": "entregado", "lines": 2, "previous_tickets": 0 },
  "proposed_action": "Pedir una foto de la carta y ofrecer el cambio por otra en el mismo estado",
  "customer_reply": "Hola, sentimos que la carta haya llegado así. ¿Nos puedes mandar una foto por aquí?",
  "priority": "media",
  "justification": "El pedido está entregado y el cliente no tiene incidencias anteriores",
  "confidence": 0.8,
  "risks": "Sin foto no se puede comprobar el daño"
}
```

| Enunciado (6) | Campo |
|---|---|
| a. Tipo de caso | `case_type` |
| b. Datos consultados | `consulted_data` (lo que devolvieron `consultar_pedido` y `consultar_historial_cliente`) |
| c. Acción propuesta | `proposed_action` y `customer_reply` (texto para el cliente, solo se envía si se aprueba) |
| d. Justificación | `justification` |
| e. Confianza o prioridad | `confidence` (0 a 1) y `priority` (`baja`/`media`/`alta`) |
| f. Riesgos | `risks` |
| g. Estado final | `final_status` en el back-office: siempre empieza en `pendiente_revision`; lo cambia una persona desde Telegram |

`bo_agent_proposals` no tiene columnas para `priority` ni `customer_reply`, así que no hace falta cambiar la tabla:

- `priority` se guarda en el ticket con `PATCH /bo-api/tickets/{id}` (herramienta `clasificar_incidencia`, que se puede deshacer).
- `customer_reply` se guarda dentro de `proposed_action`, después de la acción: `Pedir una foto... | Respuesta al cliente: "Hola, ..."`. Así la persona que revisa ve en Telegram el texto exacto que se enviará, y queda registrado en el back-office.

### Tipos de caso (`case_type`)

Los cinco motivos del formulario más dos. El agente puede corregir el motivo que eligió el cliente si el mensaje dice otra cosa (por ejemplo, motivo `otro` con un mensaje de una carta rota → `dano_envio`).

| `case_type` | Caso |
|---|---|
| `estado_conservacion` | La carta no está en el estado vendido (NM, LP, MP). |
| `idioma_version` | Idioma, edición o versión (foil o no) distintos. |
| `dano_envio` | La carta o el sobre han llegado dañados. |
| `falta_producto` | Falta una carta o un producto del pedido. |
| `devolucion` | El cliente quiere devolver el pedido. |
| `consulta` | Pregunta sobre el estado del pedido o la tienda, sin problema con el producto. |
| `otro` | No encaja en ninguno. |

## 6. Decisiones de C sobre el contrato del back-office

Respuesta a los puntos que me dejó José en el PR #24 y el #27:

1. `telegram_chat_id` en el cliente y no en el ticket: de acuerdo. Así un cliente con varios tickets solo tiene que enlazar Telegram una vez.
2. Prioridades `baja` / `media` / `alta`: de acuerdo.
3. Las herramientas del agente no devuelven email ni chat de Telegram: de acuerdo.
4. Las propuestas se crean siempre en `pendiente_revision` y solo pasan a `aprobado`/`rechazado`, y de `aprobado` a `ejecutado_simulado`: de acuerdo.
5. `reviewed_by` guarda el alias de Telegram de quien aprueba (cuenta de pruebas), nunca un nombre real: de acuerdo.
6. `event_id` en el alta del ticket: no hace falta añadirlo a `bo_tickets`. La idempotencia va por `shop_ticket_id` y la trazabilidad del evento queda en `bo_integration_log`.
7. El ejemplo de `POST /bo-api/proposals` usa `producto_danado`; con esta lista sería `dano_envio`.
8. `support.requested` llega al webhook de "Pedidos". "Pedidos" se lo pasará al flujo "Incidencias" (nodo *Execute Workflow*) y "Incidencias" registra su resultado con `workflow: Incidencias`. Hasta que esté hecho, "Reintentos" lo deja fuera.
