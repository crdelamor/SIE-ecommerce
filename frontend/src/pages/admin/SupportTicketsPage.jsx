import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import AdminPagination from './AdminPagination.jsx';
import { getSupportTickets } from './adminApi.js';
import { TICKET_STATUS_LABELS, formatDateTime, toIsoDateTime } from './adminFormat.js';

/**
 * Incidencias que llegan desde el formulario público de soporte, de la más reciente
 * a la más antigua, con filtro por estado. Si la incidencia está vinculada a un
 * pedido, enlaza con su ficha. Si la API responde 401, vuelve al login.
 */
export default function SupportTicketsPage({ onUnauthorized }) {
  const [searchParams, setSearchParams] = useSearchParams();
  const status = searchParams.get('estado') ?? '';
  const page = Math.max(1, Number.parseInt(searchParams.get('pagina') ?? '1', 10) || 1);

  const [result, setResult] = useState({ tickets: [], total: 0, per_page: 20 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let ignore = false;
    setLoading(true);
    setError('');

    getSupportTickets({ status, page })
      .then((data) => {
        if (!ignore) setResult(data);
      })
      .catch((err) => {
        if (ignore) return;
        if (err.status === 401) onUnauthorized();
        else setError(err.message);
      })
      .finally(() => {
        if (!ignore) setLoading(false);
      });

    return () => {
      ignore = true;
    };
  }, [status, page, onUnauthorized]);

  /** Cambia un parámetro de la URL; al cambiar el filtro se vuelve a la página 1. */
  function setParam(name, value) {
    setSearchParams(
      (params) => {
        if (value) params.set(name, value);
        else params.delete(name);
        if (name !== 'pagina') params.delete('pagina');
        return params;
      },
      { replace: name !== 'pagina' },
    );
  }

  /** Enlace a la ficha del pedido que conserva la sección y el filtro, para volver aquí. */
  function orderLink(orderId) {
    const params = new URLSearchParams(searchParams);
    params.set('pedido', String(orderId));
    return `?${params.toString()}`;
  }

  const { tickets, total } = result;
  const totalPages = Math.max(1, Math.ceil(total / result.per_page));

  let summary = '';
  if (loading) summary = 'Cargando incidencias…';
  else if (!error) summary = `${total} ${total === 1 ? 'incidencia' : 'incidencias'}`;

  return (
    <section aria-labelledby="tickets-title">
      <h2 id="tickets-title">Incidencias</h2>

      <div className="admin-filters">
        <div className="admin-form__field">
          <label htmlFor="tickets-status" className="admin-form__label">
            Estado
          </label>
          <select
            id="tickets-status"
            className="admin-form__input"
            value={status}
            onChange={(e) => setParam('estado', e.target.value)}
          >
            <option value="">Todas</option>
            {Object.entries(TICKET_STATUS_LABELS).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </select>
        </div>
      </div>

      <p className="text-muted" aria-live="polite">
        {summary}
      </p>

      {error && (
        <p className="admin-form__error" role="alert">
          {error}
        </p>
      )}

      {!loading && !error && total === 0 && (
        <p>{status ? 'No hay incidencias en este estado.' : 'Todavía no hay incidencias.'}</p>
      )}

      {!error && tickets.length > 0 && (
        <ul className={`admin-tickets${loading ? ' is-loading' : ''}`} aria-busy={loading}>
          {tickets.map((ticket) => (
            <li key={ticket.id} className="admin-card admin-ticket">
              <article aria-labelledby={`ticket-${ticket.id}-title`}>
                <header className="admin-ticket__header">
                  <h3 id={`ticket-${ticket.id}-title`}>
                    #{ticket.id} · {ticket.subject}
                  </h3>
                  <span className={`admin-status admin-ticket-status--${ticket.status}`}>
                    {TICKET_STATUS_LABELS[ticket.status] ?? ticket.status}
                  </span>
                </header>

                <p className="admin-ticket__meta text-muted">
                  <time dateTime={toIsoDateTime(ticket.created_at)}>{formatDateTime(ticket.created_at)}</time>
                  {' · '}
                  {ticket.name} ·{' '}
                  <a className="link" href={`mailto:${ticket.email}`}>
                    {ticket.email}
                  </a>
                </p>

                <p className="admin-ticket__message">{ticket.message}</p>

                <p className="admin-ticket__order">
                  {ticket.order ? (
                    <Link to={orderLink(ticket.order.id)} className="link admin-table__code">
                      Ver pedido {ticket.order.code}
                    </Link>
                  ) : (
                    <span className="text-muted">Sin pedido vinculado</span>
                  )}
                </p>
              </article>
            </li>
          ))}
        </ul>
      )}

      {!error && (
        <AdminPagination
          page={page}
          totalPages={totalPages}
          onChange={(newPage) => setParam('pagina', newPage > 1 ? String(newPage) : '')}
          disabled={loading}
          label="Páginas de incidencias"
        />
      )}
    </section>
  );
}
