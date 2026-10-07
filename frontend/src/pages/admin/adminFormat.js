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
