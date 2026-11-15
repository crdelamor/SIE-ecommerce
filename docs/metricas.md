# Métricas del cuadro de mando

Todas las métricas las calcula `GET /bo-api/metrics` (`backoffice/api/http/metrics.php`) en el momento de la petición, con consultas a las tablas `bo_*` del back-office. Ninguna cifra está escrita a mano y el back-office no lee la base de datos de la tienda: todo lo que cuentan estas consultas ha llegado por n8n.

Criterios comunes:

- **Venta**: pedido en `pagado`, `en_preparacion`, `enviado`, `entregado` o `incidencia`. Los `pendiente_pago` todavía no se han pagado y los `cancelado` no cuentan como ingreso. `orders_by_status` sí cuenta todos.
- **Series diarias**: los últimos `days` días (30 por defecto, de 1 a 365), con 0 en los días sin datos para que el gráfico no salte días.
- **Fechas**: en hora de Madrid, la misma con la que guarda el back-office.
- Si no hay datos para calcular una media o un porcentaje, el valor es `null` y el cuadro de mando muestra "sin datos" en vez de un 0 que no es real.

## 1. Ventas

| Métrica | Campo | Cálculo | Qué decisión permite |
|---|---|---|---|
| Pedidos por día | `orders_per_day` | `COUNT(*)` de `bo_orders` por `DATE(placed_at)` | Ver los días con más actividad y si una acción (cupón, novedad) cambia el ritmo de pedidos. |
| Importe total y pedido medio | `sales.total_amount`, `sales.avg_amount` | `SUM(total)` y `SUM(total) / COUNT(*)` de las ventas | El pedido medio indica si compensa empujar ventas cruzadas (sobres junto a cartas sueltas) o fijar un mínimo para el envío gratis. |
| Pedidos por estado | `orders_by_status` | `COUNT(*)` por `status` | Pedidos atascados en `pendiente_pago` o `en_preparacion` que hay que revisar. |
| Tasa de cancelación | `cancel_rate` | `cancelado / todos los pedidos` | Si sube, revisar stock, precios o el proceso de pago. |
| Juegos más vendidos | `top_games` | `SUM(quantity)` y `SUM(line_total)` de `bo_order_lines` por `game` | En qué juego conviene tener más stock y centrar la comunicación. |
| Productos más vendidos | `top_products` | Igual, por `product_name` (los 5 primeros) | Reposición de los productos que más rotan. |
| Mezcla de producto | `product_mix` | Unidades de sobres (`card_condition` nulo), cartas sueltas y foil | Equilibrio entre producto sellado y cartas sueltas, que tienen otro margen y otra gestión de stock. |
| Clientes que repiten | `customers.buyers`, `customers.repeat_buyers` | Clientes con al menos una venta y con más de una | Si merece la pena invertir en fidelizar (cupones de segunda compra, avisos de novedades). |

Fuentes: los informes de ventas de Shopify usan el pedido medio, las ventas a lo largo del tiempo y las ventas por producto como indicadores básicos de una tienda online ([Shopify Help Center, *Sales reports*](https://help.shopify.com/manual/reports-and-analytics/shopify-reports/report-types/order-sales); [*Order reports*](https://help.shopify.com/manual/reports-and-analytics/shopify-reports/report-types/order-reports)). Sobre la repetición de compra: Reichheld y Sasser, *Zero Defections: Quality Comes to Services*, Harvard Business Review, 1990 ([resumen en Bain](https://www.bain.com/insights/zero-defections-quality-comes-to-services-harvard-business-review-hbr/)), que relaciona la retención de clientes con la rentabilidad.

## 2. Preparación y envío

| Métrica | Campo | Cálculo | Qué decisión permite |
|---|---|---|---|
| De pagado a enviado | `fulfilment.paid_to_shipped_hours` | Media de horas entre la primera entrada en `pagado` y la primera en `enviado` (`bo_order_status_log`) | Si el almacén tarda demasiado en preparar los pedidos. |
| De enviado a entregado | `fulfilment.shipped_to_delivered_hours` | Igual, de `enviado` a `entregado` | Comparar con lo que se promete al cliente y, si hace falta, cambiar de transportista. |

Cada media va con el número de pedidos con los que se ha calculado (`*_orders`), para no sacar conclusiones con uno o dos pedidos.

Fuente: Shopify separa el tiempo de preparación (*processing time*) del tiempo de tránsito, y recomienda medirlo y reducirlo ([Shopify Help Center, *Understanding and reducing processing time*](https://help.shopify.com/manual/shipping/understanding-shipping/processing-times)).

## 3. Integración (n8n)

| Métrica | Campo | Cálculo | Qué decisión permite |
|---|---|---|---|
| Ejecuciones por resultado | `integration.ok`, `error`, `duplicado`, `pendiente` | `COUNT(*)` de `bo_integration_log` por `result` | Si la integración funciona. Cada fila es una ejecución, también los reintentos. |
| Errores por tipo | `integration.errors_by_type` | `COUNT(*)` de los `error` por `error_type` (tipos de `docs/integracion.md`) | Qué corregir primero: no es lo mismo `payload_incompleto` (fallo de la tienda) que `agente_no_disponible` (límite del modelo). |
| Ejecuciones por flujo | `integration_by_workflow` | `COUNT(*)` por `workflow` | Cuánto trabajo recae en Reintentos. Si es mucho, el aviso directo de la tienda está fallando. |
| Ejecuciones por día | `integration_per_day` | Correctas (`ok` + `duplicado`) y fallidas (`error` + `pendiente`) por día | Ver cuándo empezó un problema y si se arregló. |
| Retraso de sincronización | `sync_delay` | `synced_at − placed_at` de `bo_orders`: mediana y pedidos en menos de 1 minuto, de 1 a 10 y más de 10 | Cuánto tarda un pedido en verse en el back-office. Un pedido que llega en segundos ha ido por el aviso directo; uno de 5 a 7 minutos, por Reintentos. |

Para el retraso se usa la mediana y no la media: los pedidos que se recuperaron horas después (por ejemplo, los anteriores a la integración) dispararían la media.

Fuente: Google, *Site Reliability Engineering*, capítulo 6, *Monitoring Distributed Systems* ([sre.google](https://sre.google/sre-book/monitoring-distributed-systems/)). De sus cuatro señales básicas, aquí se miden los errores (ejecuciones fallidas y por tipo), el tráfico (ejecuciones por día y por flujo) y la latencia (retraso de sincronización).

## 4. Soporte

| Métrica | Campo | Cálculo | Qué decisión permite |
|---|---|---|---|
| Incidencias abiertas | `tickets.open` | `abierta` + `en_curso` | Carga pendiente del equipo de soporte. |
| Por estado y prioridad | `tickets.by_status`, `tickets.by_priority` | `COUNT(*)` por `status` y por `priority` (la pone `clasificar_incidencia`) | Si se acumulan las de prioridad alta. |
| Motivos más frecuentes | `tickets.top_reasons` | Los 5 `reason` más repetidos | Atacar la causa (embalaje, plazos de envío) en vez de responder caso a caso. |
| Con pedido | `tickets.with_order` | Incidencias con `order_code` | Cuántas son de un pedido concreto y cuántas son consultas generales. |
| Tiempo medio de resolución | `tickets.avg_resolution_hours` | Media de `closed_at − opened_at` de las cerradas | Si el equipo responde a tiempo. |
| Clientes con Telegram | `tickets.customers_telegram` (sobre `tickets.customers`) | Clientes con incidencias que han enlazado su chat | Si los clientes usan el seguimiento por Telegram. |

Ahora mismo ningún flujo cierra incidencias, así que `avg_resolution_hours` será `null` hasta que se puedan cerrar desde la pantalla del gestor.

Fuente: Zendesk mide la resolución como el tiempo entre que se crea y se resuelve el ticket ([Zendesk, *About native Support time duration metrics*](https://support.zendesk.com/hc/en-us/articles/4408834848154)).

## 5. Agente de IA y supervisión humana

| Métrica | Campo | Cálculo | Qué decisión permite |
|---|---|---|---|
| Propuestas por estado | `proposals.by_status` | `COUNT(*)` por `final_status` | Propuestas que esperan a una persona (`pendiente_revision`). |
| Tasa de aprobación | `proposals.approval_rate` | (`aprobado` + `ejecutado_simulado`) / (las anteriores + `rechazado`) | Si el agente propone cosas que el equipo acepta. Si baja, revisar el prompt o las herramientas. |
| Confianza media | `proposals.avg_confidence` | Media de `confidence`: todas, aprobadas y rechazadas | Si la confianza que declara el agente sirve de algo. Si las rechazadas tienen una confianza parecida a las aprobadas, no se puede usar para dejarle decidir solo. |
| Por tipo de caso | `proposals.by_case_type` | `COUNT(*)` por `case_type` | En qué casos trabaja más el agente. |
| Tiempo de revisión | `proposals.avg_review_minutes` | Media de `reviewed_at − created_at` | Cuánto espera el cliente por la parte humana del proceso. |

Fuentes: el Reglamento (UE) 2024/1689 de inteligencia artificial, artículo 14, pide que las personas puedan supervisar el sistema y decidir no usar su resultado ([AI Act Service Desk, artículo 14](https://ai-act-service-desk.ec.europa.eu/en/ai-act/article-14)). La tasa de aprobación y el tiempo de revisión muestran que esa supervisión se hace. La comparación de la confianza se basa en Guo et al., *On Calibration of Modern Neural Networks*, ICML 2017 ([PMLR](https://proceedings.mlr.press/v70/guo17a.html)): la confianza que da un modelo no tiene por qué corresponder con lo que acierta, y hay que comprobarlo con datos.
