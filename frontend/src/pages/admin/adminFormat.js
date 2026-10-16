/** Textos y formatos compartidos por las pantallas de administración. */

// Mismo orden que el flujo del pedido; la API valida los valores, esto solo son las etiquetas
export const STATUS_LABELS = {
  pendiente_pago: 'Pendiente de pago',
  pagado: 'Pagado',
  en_preparacion: 'En preparación',
  enviado: 'Enviado',
  entregado: 'Entregado',
  cancelado: 'Cancelado',
  incidencia: 'Incidencia',
};

// Estados de una incidencia de soporte
export const TICKET_STATUS_LABELS = {
  abierta: 'Abierta',
  en_curso: 'En curso',
  cerrada: 'Cerrada',
};

// Qué significa cada tipo de evento (los del contrato y el de cambio de estado)
export const EVENT_LABELS = {
  'product.viewed': 'Producto visto',
  'cart.item_added': 'Añadido al carrito',
  'checkout.started': 'Checkout iniciado',
  'order.created': 'Pedido creado',
  'payment.simulated': 'Pago simulado',
  'order.status_changed': 'Cambio de estado del pedido',
  'support.requested': 'Incidencia de soporte',
};

// Texto del botón que lleva el pedido a cada estado
export const STATUS_ACTIONS = {
  pagado: 'Marcar como pagado',
  en_preparacion: 'Pasar a En preparación',
  enviado: 'Marcar como enviado',
  entregado: 'Marcar como entregado',
  cancelado: 'Cancelar pedido',
  incidencia: 'Marcar incidencia',
};

// Estado de conservación de una carta (los sobres no tienen)
export const CONDITION_LABELS = {
  NM: 'Near Mint (casi nueva)',
  LP: 'Lightly Played (poco uso)',
  MP: 'Moderately Played (uso moderado)',
};

export const PAYMENT_METHOD_LABELS = {
  tarjeta_simulada: 'Tarjeta (simulada)',
  bizum_simulado: 'Bizum (simulado)',
};

export const PAYMENT_STATUS_LABELS = {
  approved: 'Aprobado',
  rejected: 'Rechazado',
};

const dateTimeFormat = new Intl.DateTimeFormat('es-ES', { dateStyle: 'short', timeStyle: 'short' });
const priceFormat = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' });

/** '2026-09-30 12:00:00' (MySQL) -> '2026-09-30T12:00:00', válido para new Date() y <time dateTime>. */
export function toIsoDateTime(mysqlDateTime) {
  return mysqlDateTime.replace(' ', 'T');
}

export function formatDateTime(mysqlDateTime) {
  return dateTimeFormat.format(new Date(toIsoDateTime(mysqlDateTime)));
}

export function formatPrice(amount) {
  return priceFormat.format(amount);
}
