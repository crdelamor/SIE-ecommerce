# Matriz de decisión de implantación

Comparación de alternativas para el caso de la Tarea 2: que los pedidos e incidencias de la tienda **The Black Lotus TCG** lleguen solos a un back-office propio, con trazabilidad, un agente de IA supervisado y un cuadro de mando. Apartado 8 del enunciado.

## Contexto de la empresa

- Tienda online pequeña de cartas coleccionables (TCG): un catálogo de decenas de productos, pocos pedidos al día y uno o dos gestores.
- La tienda ya existe (Tarea 1): PHP 8 + MySQL en un hosting compartido de DonDominio, que solo admite PHP y MySQL.
- Datos personales de clientes (nombre, email, dirección): aplica el RGPD.
- Presupuesto prácticamente nulo: solo hay el dominio y los créditos gratuitos de estudiante para un servidor.

## Alternativas

| | Alternativa | Qué sería | Tipo del enunciado |
|---|---|---|---|
| **A1** | Híbrida: back-office propio + n8n autoalojado | Back-office PHP + MySQL propio y n8n en un servidor de la nube; la tienda envía eventos por webhook | e. arquitectura híbrida + f. plataforma de automatización |
| **A2** | SaaS | Make como integrador y un CRM SaaS (por ejemplo, HubSpot) como back-office (Zapier queda fuera: el enunciado no acepta herramientas 100 % no-code) | b. solución SaaS |
| **A3** | ERP parametrizable | Odoo Community autoalojado (módulos de ventas y helpdesk) y n8n o su API XML-RPC para integrarlo | c. ERP/CRM parametrizable |
| **A4** | Low-code | Una hoja de Google como "back-office" y n8n moviendo los datos | d. plataforma low-code/no-code |

No entran en la matriz **una tabla más en la base de datos de la tienda** ni **ampliar la administración de la tienda (`/#/admin`)**: no son un sistema distinto con almacenamiento y acceso propios, que es lo que pide el enunciado.

## Criterios y pesos

Los 12 criterios del enunciado. Los pesos suman 100 y reflejan lo que más importa a una tienda pequeña sin presupuesto que tiene que demostrar la integración: que funcione, que se integre con lo que ya hay, que cueste poco y que sea seguro.

| Criterio | Peso | Por qué pesa eso |
|---|---|---|
| Funcionalidad | 12 | Tiene que cubrir pedidos, incidencias, registro de integración, agente y métricas |
| Coste estimado | 12 | Presupuesto casi nulo |
| Integración | 12 | Es el objetivo del caso: unir la tienda con el back-office |
| Seguridad | 10 | Datos de clientes y claves de API |
| Esfuerzo de implantación | 8 | Equipo de 3 personas y pocas semanas |
| Dependencia del proveedor | 8 | Cambiar de proveedor no debería obligar a rehacerlo todo |
| Mantenimiento | 8 | Nadie se dedica a tiempo completo a sistemas |
| Cumplimiento normativo | 8 | RGPD: dónde están los datos y quién accede |
| Trazabilidad | 7 | Saber qué evento llegó, qué se hizo y qué falló |
| Escalabilidad | 5 | El volumen es bajo; no es crítico a corto plazo |
| Facilidad de evolución | 5 | Añadir más eventos o pantallas |
| Adecuación al tamaño | 5 | Que no sea desproporcionado para una microempresa |

## Matriz

Puntuación de 1 (muy mal) a 5 (muy bien) en cada criterio. Total = suma de puntuación × peso / 100 (sobre 5).

| Criterio (peso) | A1 Híbrida | A2 SaaS | A3 ERP | A4 Low-code |
|---|---|---|---|---|
| Funcionalidad (12) | 4 | 4 | 5 | 2 |
| Coste estimado (12) | 5 | 2 | 4 | 4 |
| Esfuerzo de implantación (8) | 2 | 4 | 1 | 5 |
| Escalabilidad (5) | 3 | 5 | 4 | 2 |
| Integración (12) | 5 | 4 | 4 | 3 |
| Seguridad (10) | 4 | 3 | 4 | 2 |
| Dependencia del proveedor (8) | 4 | 1 | 3 | 2 |
| Mantenimiento (8) | 2 | 5 | 2 | 4 |
| Cumplimiento normativo (8) | 4 | 2 | 4 | 2 |
| Trazabilidad (7) | 5 | 3 | 4 | 2 |
| Facilidad de evolución (5) | 4 | 3 | 3 | 2 |
| Adecuación al tamaño (5) | 4 | 3 | 2 | 4 |
| **Total (sobre 5)** | **3,94** | **3,22** | **3,49** | **2,86** |

### Justificación de las puntuaciones

- **A1 Híbrida.** Integración y trazabilidad máximas: el contrato de eventos es nuestro, cada ejecución queda en `bo_integration_log` y en el historial de n8n. Coste casi nulo (n8n y el back-office son software libre o propio, en un servidor con créditos de estudiante). Los datos están en nuestro servidor, lo que facilita el RGPD. Lo malo: hay que programar el back-office y mantener el servidor (copias de seguridad, actualizaciones de n8n), y un único servidor escala poco.
- **A2 SaaS.** Rápida de montar y sin mantenimiento, y escala sola. Pero el coste es una suscripción mensual que crece con las ejecuciones o los usuarios, los datos de clientes salen a proveedores externos (a menudo fuera de la UE, con transferencias internacionales que justificar) y todo depende de sus planes y precios. Además, el enunciado no acepta tecnologías 100 % no-code como Zapier: habría que añadir igualmente código propio para la validación y la lógica.
- **A3 ERP (Odoo Community).** La más completa en funcionalidad (ventas, clientes, helpdesk) y sin licencia en la edición Community. Pero es la que más cuesta implantar y mantener: parametrizar módulos, aprender su modelo de datos y su API, y un servidor con más recursos. Es desproporcionada para una tienda de este tamaño.
- **A4 Low-code.** La más rápida de montar, pero apenas lleva programación, n8n necesita credenciales OAuth de Google y una hoja de cálculo no es un sistema con acceso y almacenamiento propios de verdad: sin control de acceso fino, sin claves únicas que eviten duplicados, sin integridad entre pedidos y líneas, y con poca trazabilidad.

## Análisis de sensibilidad

Si se da más importancia a la operación que al coste (esfuerzo y mantenimiento con peso doble, coste a la mitad), los totales son A1 **3,60**, A2 3,47, A3 3,17 y A4 3,04. La híbrida sigue primera, aunque la SaaS se acerca: para una empresa con presupuesto y sin nadie técnico, A2 sería una opción razonable.

## Recomendación

**A1, arquitectura híbrida: back-office propio + n8n autoalojado en la nube.** Es la mejor puntuada y la que mejor encaja con el caso:

- Reutiliza lo que ya hay: el mismo stack de la tienda (PHP + MySQL) y su registro de eventos de la Tarea 1.
- Es un sistema distinto de verdad: otro servidor, otra base de datos, otro login, y los datos solo llegan por la integración.
- Deja trazabilidad completa (registro en el back-office e historial de n8n) y permite añadir el agente de IA con revisión humana dentro del mismo n8n.
- Cuesta lo mínimo y no ata a un proveedor: n8n y el back-office se pueden mover a otro servidor con sus copias de seguridad.

Riesgos que asumimos y cómo se cubren:

| Riesgo | Medida |
|---|---|
| El servidor o n8n se caen | La compra no depende de n8n: el evento queda en la tienda y el flujo "Reintentos" lo recupera |
| Mantenimiento del servidor | Un solo `docker-compose`, copias de seguridad de la base de datos y de los flujos exportados en el repositorio |
| Seguridad de las claves | Secretos solo en variables de entorno y credenciales de n8n; claves distintas para la tienda y el back-office |
| Crecimiento | Si el volumen creciera mucho, n8n admite modo cola con varios procesos, y el back-office podría pasar a un ERP (A3) usando el mismo contrato de eventos |

## Plataforma de despliegue

El enunciado exige que n8n y el back-office estén desplegados fuera de nuestros ordenadores. n8n necesita una base de datos persistente (PostgreSQL) para no perder los flujos ni el historial de ejecuciones, que es parte de la trazabilidad.

| Plataforma | Qué ofrece | Problema | Decisión |
|---|---|---|---|
| **Máquina virtual de Azure for Students + `docker-compose`** | Un servidor propio (Ubuntu, 2 vCPU y 4 GiB) pagado con el crédito de estudiante, sin tarjeta, con n8n + PostgreSQL y el back-office + MySQL, cada uno con su volumen (`deploy/docker-compose.yml`) | Hay que mantener el servidor (actualizaciones y copias de seguridad) | **Elegida** |
| Render (plan gratuito) | Imagen oficial `n8nio/n8n` | El servicio se duerme, el disco es efímero y la base de datos PostgreSQL gratuita caduca a los 30 días | Descartada |
| Railway | Plantilla de n8n con base de datos | Solo una prueba gratuita (30 días o 5 $) | Descartada |
| DonDominio (hosting de la tienda) | Ya lo tenemos pagado | Solo admite PHP y MySQL: no puede ejecutar n8n (Node.js) y el back-office no sería un sistema separado de la tienda | Descartada |
