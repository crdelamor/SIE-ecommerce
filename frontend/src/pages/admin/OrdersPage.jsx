import { useEffect, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import Button from '../../components/Button.jsx';
import AdminPagination from './AdminPagination.jsx';
import StatusBadge from './StatusBadge.jsx';
import { getOrders } from './adminApi.js';
import { STATUS_LABELS, formatDateTime, formatPrice, toIsoDateTime } from './adminFormat.js';

// Filtros que viven en la URL (#/admin?estado=pagado&desde=2026-09-01...), así la vista
// se puede recargar o compartir
const FILTER_PARAMS = ['estado', 'desde', 'hasta', 'buscar'];
const SEARCH_DELAY_MS = 300;

/**
 * Lista de pedidos con filtros por estado, fechas y búsqueda, y paginación.
 * Si la API responde 401, avisa a AdminPage para volver al login.
 */
export default function OrdersPage({ onUnauthorized }) {
  const [searchParams, setSearchParams] = useSearchParams();
  const status = searchParams.get('estado') ?? '';
  const from = searchParams.get('desde') ?? '';
  const to = searchParams.get('hasta') ?? '';
  const search = searchParams.get('buscar') ?? '';
  const page = Math.max(1, Number.parseInt(searchParams.get('pagina') ?? '1', 10) || 1);

  // El texto del buscador se guarda aparte y llega a la URL con un pequeño retraso
  const [searchInput, setSearchInput] = useState(search);
  const [result, setResult] = useState({ orders: [], total: 0, per_page: 20 });
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');

  /** Cambia un filtro en la URL y vuelve a la página 1. */
  function updateFilter(name, value) {
    setSearchParams(
      (params) => {
        if (value) params.set(name, value);
        else params.delete(name);
        params.delete('pagina');
        return params;
      },
      { replace: true }, // sin llenar el historial con cada cambio de filtro
    );
  }

  // Busca cuando se deja de escribir durante 300 ms, no con cada letra
  useEffect(() => {
    const value = searchInput.trim();
    if (value === search) return undefined;
    const timer = setTimeout(() => updateFilter('buscar', value), SEARCH_DELAY_MS);
    return () => clearTimeout(timer);
  }, [searchInput, search]);

  useEffect(() => {
    // Si cambian los filtros antes de que llegue la respuesta, se descarta la respuesta vieja
    let ignore = false;
    setLoading(true);
    setError('');

    getOrders({ status, from, to, q: search, page: page > 1 ? page : '' })
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
  }, [status, from, to, search, page, onUnauthorized]);

  function goToPage(newPage) {
    setSearchParams((params) => {
      if (newPage > 1) params.set('pagina', String(newPage));
      else params.delete('pagina');
      return params;
    });
  }

  /** Enlace a la ficha que conserva los filtros actuales, para volver a la misma lista. */
  function orderLink(id) {
    const params = new URLSearchParams(searchParams);
    params.set('pedido', String(id));
    return `?${params.toString()}`;
  }

  function clearFilters() {
    setSearchInput('');
    setSearchParams({}, { replace: true });
  }

  const hasFilters = FILTER_PARAMS.some((name) => searchParams.get(name));
  const { orders, total } = result;
  const totalPages = Math.max(1, Math.ceil(total / result.per_page));

  let summary = '';
  if (loading) summary = 'Cargando pedidos…';
  else if (!error) summary = `${total} ${total === 1 ? 'pedido' : 'pedidos'}`;

  return (
    <section aria-labelledby="orders-title">
      <h2 id="orders-title">Pedidos</h2>

      <form className="admin-filters" role="search" aria-label="Filtrar pedidos" onSubmit={(e) => e.preventDefault()}>
        <div className="admin-form__field admin-filters__search">
          <label htmlFor="orders-search" className="admin-form__label">
            Buscar
          </label>
          <input
            id="orders-search"
            className="admin-form__input"
            type="search"
            placeholder="Código o cliente"
            maxLength={100}
            value={searchInput}
            onChange={(e) => setSearchInput(e.target.value)}
          />
        </div>

        <div className="admin-form__field">
          <label htmlFor="orders-status" className="admin-form__label">
            Estado
          </label>
          <select
            id="orders-status"
            className="admin-form__input"
            value={status}
            onChange={(e) => updateFilter('estado', e.target.value)}
          >
            <option value="">Todos</option>
            {Object.entries(STATUS_LABELS).map(([value, label]) => (
              <option key={value} value={value}>
                {label}
              </option>
            ))}
          </select>
        </div>

        <div className="admin-form__field">
          <label htmlFor="orders-from" className="admin-form__label">
            Desde
          </label>
          <input
            id="orders-from"
            className="admin-form__input"
            type="date"
            value={from}
            max={to || undefined}
            onChange={(e) => updateFilter('desde', e.target.value)}
          />
        </div>

        <div className="admin-form__field">
          <label htmlFor="orders-to" className="admin-form__label">
            Hasta
          </label>
          <input
            id="orders-to"
            className="admin-form__input"
            type="date"
            value={to}
            min={from || undefined}
            onChange={(e) => updateFilter('hasta', e.target.value)}
          />
        </div>

        {hasFilters && (
          <Button variant="secondary" onClick={clearFilters}>
            Limpiar filtros
          </Button>
        )}
      </form>

      {/* aria-live: el lector de pantalla anuncia el resultado al cambiar los filtros */}
      <p className="text-muted" aria-live="polite">
        {summary}
      </p>

      {error && (
        <p className="admin-form__error" role="alert">
          {error}
        </p>
      )}

      {!loading && !error && orders.length === 0 && total > 0 && (
        <p>
          Esta página no existe.{' '}
          <Button variant="secondary" onClick={() => goToPage(1)}>
            Ir a la primera página
          </Button>
        </p>
      )}

      {!loading && !error && total === 0 && (
        <p>{hasFilters ? 'No hay pedidos con estos filtros.' : 'Todavía no hay pedidos.'}</p>
      )}

      {/* Mientras carga se mantiene la tabla anterior atenuada, para que la página no salte */}
      {!error && orders.length > 0 && (
        <div className="admin-table-wrap" aria-busy={loading}>
          <table className={`admin-table${loading ? ' is-loading' : ''}`}>
            <caption className="sr-only">Pedidos, del más reciente al más antiguo</caption>
            <thead>
              <tr>
                <th scope="col">Código</th>
                <th scope="col">Fecha</th>
                <th scope="col">Cliente</th>
                <th scope="col" className="admin-table__num">
                  Total
                </th>
                <th scope="col">Estado</th>
              </tr>
            </thead>
            <tbody>
              {orders.map((order) => (
                <tr key={order.id}>
                  <td>
                    <Link to={orderLink(order.id)} className="link admin-table__code">
                      {order.code}
                    </Link>
                  </td>
                  <td>
                    <time dateTime={toIsoDateTime(order.created_at)}>{formatDateTime(order.created_at)}</time>
                  </td>
                  <td>{order.customer_name}</td>
                  <td className="admin-table__num">{formatPrice(order.total)}</td>
                  <td>
                    <StatusBadge status={order.status} />
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
          onChange={goToPage}
          disabled={loading}
          label="Páginas de pedidos"
        />
      )}
    </section>
  );
}
