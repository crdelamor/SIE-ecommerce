import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import Button from '../../components/Button.jsx';
import AdminPagination from './AdminPagination.jsx';
import { eventsExportUrl, getEvents } from './adminApi.js';
import { EVENT_LABELS, formatDateTime, toIsoDateTime } from './adminFormat.js';

const FILTER_PARAMS = ['tipo', 'desde', 'hasta'];

/** {"product": {"game": "Pokémon"}} -> [["product.game", "Pokémon"]]. Las listas se muestran como JSON. */
function flattenPayload(data, prefix = '') {
  if (data === null || typeof data !== 'object' || Array.isArray(data)) {
    return [[prefix || 'valor', JSON.stringify(data)]];
  }
  return Object.entries(data).flatMap(([key, value]) => {
    const name = prefix ? `${prefix}.${key}` : key;
    if (value !== null && typeof value === 'object' && !Array.isArray(value)) return flattenPayload(value, name);
    return [[name, typeof value === 'string' ? value : JSON.stringify(value)]];
  });
}

/**
 * Visor de eventos: qué ha pasado en la tienda, del más reciente al más antiguo, con
 * filtros por tipo y fechas y exportación a CSV o JSON con esos mismos filtros.
 */
export default function EventsPage({ onUnauthorized }) {
  const [searchParams, setSearchParams] = useSearchParams();
  const type = searchParams.get('tipo') ?? '';
  const from = searchParams.get('desde') ?? '';
  const to = searchParams.get('hasta') ?? '';
  const page = Math.max(1, Number.parseInt(searchParams.get('pagina') ?? '1', 10) || 1);

  const [result, setResult] = useState({ events: [], total: 0, per_page: 20, types: [] });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  useEffect(() => {
    let ignore = false;
    setLoading(true);
    setError('');

    getEvents({ type, from, to, page: page > 1 ? page : '' })
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
  }, [type, from, to, page, onUnauthorized]);

  /** Cambia un parámetro de la URL; al cambiar un filtro se vuelve a la página 1. */
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

  function clearFilters() {
    setSearchParams({ seccion: 'eventos' }, { replace: true });
  }

  /** Enlace a la ficha del pedido que conserva la sección y los filtros, para volver aquí. */
  function orderLink(orderId) {
    const params = new URLSearchParams(searchParams);
    params.set('pedido', String(orderId));
    return `?${params.toString()}`;
  }

  const { events, total, types } = result;
  const totalPages = Math.max(1, Math.ceil(total / result.per_page));
  const hasFilters = FILTER_PARAMS.some((name) => searchParams.get(name));
  // El filtro de tipo ofrece los tipos que existen y, si viene en la URL, también ese
  const typeOptions = type && !types.includes(type) ? [...types, type] : types;

  let summary = '';
  if (loading) summary = 'Cargando eventos…';
  else if (!error) summary = `${total} ${total === 1 ? 'evento' : 'eventos'}`;

  return (
    <section aria-labelledby="events-title">
      <h2 id="events-title">Eventos</h2>

      <form className="admin-filters" aria-label="Filtrar eventos" onSubmit={(e) => e.preventDefault()}>
        <div className="admin-form__field">
          <label htmlFor="events-type" className="admin-form__label">
            Tipo
          </label>
          <select
            id="events-type"
            className="admin-form__input"
            value={type}
            onChange={(e) => setParam('tipo', e.target.value)}
          >
            <option value="">Todos</option>
            {typeOptions.map((value) => (
              <option key={value} value={value}>
                {EVENT_LABELS[value] ? `${EVENT_LABELS[value]} (${value})` : value}
              </option>
            ))}
          </select>
        </div>

        <div className="admin-form__field">
          <label htmlFor="events-from" className="admin-form__label">
            Desde
          </label>
          <input
            id="events-from"
            className="admin-form__input"
            type="date"
            value={from}
            max={to || undefined}
            onChange={(e) => setParam('desde', e.target.value)}
          />
        </div>

        <div className="admin-form__field">
          <label htmlFor="events-to" className="admin-form__label">
            Hasta
          </label>
          <input
            id="events-to"
            className="admin-form__input"
            type="date"
            value={to}
            min={from || undefined}
            onChange={(e) => setParam('hasta', e.target.value)}
          />
        </div>

        {hasFilters && (
          <Button variant="secondary" onClick={clearFilters}>
            Limpiar filtros
          </Button>
        )}
      </form>

      {/* La descarga la hace el navegador con la cookie de sesión; exporta con los filtros de arriba */}
      <div className="admin-actions admin-export">
        <a className="button button--secondary" href={eventsExportUrl('csv', { type, from, to })} download>
          Exportar CSV
        </a>
        <a className="button button--secondary" href={eventsExportUrl('json', { type, from, to })} download>
          Exportar JSON
        </a>
        <span className="text-muted admin-note">Se exportan todos los eventos que cumplen los filtros.</span>
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
        <p>{hasFilters ? 'No hay eventos con estos filtros.' : 'Todavía no hay eventos.'}</p>
      )}

      {!error && events.length > 0 && (
        <div className="admin-table-wrap" aria-busy={loading}>
          <table className={`admin-table admin-events${loading ? ' is-loading' : ''}`}>
            <caption className="sr-only">Eventos, del más reciente al más antiguo</caption>
            <thead>
              <tr>
                <th scope="col">Fecha</th>
                <th scope="col">Tipo</th>
                <th scope="col">Entidad</th>
                <th scope="col">Sesión</th>
                <th scope="col">Datos</th>
              </tr>
            </thead>
            <tbody>
              {events.map((event) => (
                <tr key={event.id}>
                  <td>
                    <time dateTime={toIsoDateTime(event.created_at)}>{formatDateTime(event.created_at)}</time>
                  </td>
                  <td>
                    {EVENT_LABELS[event.type] ?? event.type}
                    <br />
                    <code className="admin-code">{event.type}</code>
                  </td>
                  <td>
                    {event.entity_type === 'order' && event.entity_id ? (
                      <Link to={orderLink(event.entity_id)} className="link admin-table__code">
                        pedido #{event.entity_id}
                      </Link>
                    ) : event.entity_type ? (
                      `${event.entity_type} #${event.entity_id ?? '—'}`
                    ) : (
                      '—'
                    )}
                  </td>
                  <td>
                    {event.session_id ? (
                      <code className="admin-code" title={event.session_id}>
                        {event.session_id.slice(0, 10)}
                      </code>
                    ) : (
                      '—'
                    )}
                  </td>
                  <td>
                    {event.payload ? (
                      <dl className="admin-payload">
                        {flattenPayload(event.payload).map(([key, value]) => (
                          <div key={key}>
                            <dt>{key}</dt>
                            <dd>{value}</dd>
                          </div>
                        ))}
                      </dl>
                    ) : (
                      '—'
                    )}
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}

      {!error && (
        <AdminPagination
          page={page}
          totalPages={totalPages}
          onChange={(newPage) => setParam('pagina', newPage > 1 ? String(newPage) : '')}
          disabled={loading}
          label="Páginas de eventos"
        />
      )}
    </section>
  );
}
