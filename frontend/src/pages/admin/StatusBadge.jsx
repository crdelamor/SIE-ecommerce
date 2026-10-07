import { STATUS_LABELS } from './adminFormat.js';

/** Etiqueta con el estado del pedido: el texto siempre dice el estado; el color solo lo refuerza. */
export default function StatusBadge({ status }) {
  return <span className={`admin-status admin-status--${status}`}>{STATUS_LABELS[status] ?? status}</span>;
}
