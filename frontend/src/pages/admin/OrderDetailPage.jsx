import { useEffect, useRef, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import OrderStatusActions from './OrderStatusActions.jsx';
import StatusBadge from './StatusBadge.jsx';
import { getOrder } from './adminApi.js';
import {
  CONDITION_LABELS,
  PAYMENT_METHOD_LABELS,
  PAYMENT_STATUS_LABELS,
  formatDateTime,
  formatPrice,
  toIsoDateTime,
} from './adminFormat.js';

function DateTime({ value }) {
  return <time dateTime={toIsoDateTime(value)}>{formatDateTime(value)}</time>;
}

/**
 * Ficha de un pedido: cliente, importes, líneas con los datos de cada carta,
 * pagos e historial de estados. Se abre con #/admin?pedido=ID y conserva los
 * filtros de la lista para volver a la misma vista.
 */
export default function OrderDetailPage({ orderId, onUnauthorized, backLabel = 'Volver a pedidos' }) {
  const [searchParams] = useSearchParams();
  const [order, setOrder] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState('');
  const titleRef = useRef(null);
  const focusedOrderId = useRef(null);

  // Enlace de vuelta: los mismos filtros de la lista, sin el pedido
  const backParams = new URLSearchParams(searchParams);
  backParams.delete('pedido');
  const backTo = `?${backParams.toString()}`;

  useEffect(() => {
    let ignore = false;
    setLoading(true);
    setError('');

    getOrder(orderId)
      .then((data) => {
        if (!ignore) setOrder(data);
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
  }, [orderId, onUnauthorized]);

  // Al abrir la ficha, el foco va al título: el lector de pantalla anuncia el cambio de vista.
  // Solo la primera vez por pedido, no cada vez que se actualiza tras cambiar el estado.
  useEffect(() => {
    if (order && focusedOrderId.current !== order.id) {
      focusedOrderId.current = order.id;
      titleRef.current?.focus();
    }
  }, [order]);

  const backLink = (
    <Link to={backTo} className="link admin-back">
      ← {backLabel}
    </Link>
  );

  if (loading) {
    return (
      <>
        {backLink}
        <p className="text-muted" aria-live="polite">
          Cargando pedido…
        </p>
      </>
    );
  }

  if (error) {
    return (
      <>
        {backLink}
        <p className="admin-form__error" role="alert">
          {error}
        </p>
      </>
    );
  }

  const { customer } = order;

  return (
    <article aria-labelledby="order-title">
      {backLink}

      <header className="admin-detail-header">
        <h2 id="order-title" ref={titleRef} tabIndex={-1}>
          Pedido {order.code}
        </h2>
        <StatusBadge status={order.status} />
        <p className="text-muted">
          Realizado el <DateTime value={order.created_at} />
        </p>
      </header>

      <div className="admin-cards">
        <section className="admin-card" aria-labelledby="customer-title">
          <h3 id="customer-title">Cliente</h3>
          <dl className="admin-dl">
            <dt>Nombre</dt>
            <dd>{customer.name}</dd>
            <dt>Email</dt>
            <dd>
              <a className="link" href={`mailto:${customer.email}`}>
                {customer.email}
              </a>
            </dd>
            <dt>Teléfono</dt>
            <dd>{customer.phone ?? '—'}</dd>
            <dt>Envío</dt>
            <dd>
              {customer.address}
              <br />
              {customer.postal_code} {customer.city}
            </dd>
          </dl>
        </section>

        <section className="admin-card" aria-labelledby="totals-title">
          <h3 id="totals-title">Importe</h3>
          <dl className="admin-dl admin-dl--amounts">
            <dt>Subtotal (sin IVA)</dt>
            <dd>{formatPrice(order.subtotal)}</dd>
            <dt>Descuento</dt>
            <dd>{order.discount > 0 ? `−${formatPrice(order.discount)}` : '—'}</dd>
            <dt>Envío</dt>
            <dd>{order.shipping > 0 ? formatPrice(order.shipping) : 'Gratis'}</dd>
            <dt>IVA</dt>
            <dd>{formatPrice(order.tax)}</dd>
            <dt className="admin-dl__total">Total</dt>
            <dd className="admin-dl__total">{formatPrice(order.total)}</dd>
          </dl>
        </section>

        <OrderStatusActions order={order} onChanged={setOrder} onUnauthorized={onUnauthorized} />
      </div>

      <section aria-labelledby="items-title" className="admin-detail-block">
        <h3 id="items-title">Productos</h3>
        <div className="admin-table-wrap">
          <table className="admin-table">
            <thead>
              <tr>
                <th scope="col">Producto</th>
                <th scope="col">Juego</th>
                <th scope="col">Set</th>
                <th scope="col">Estado</th>
                <th scope="col">Idioma</th>
                <th scope="col">Foil</th>
                <th scope="col" className="admin-table__num">
                  Cant.
                </th>
                <th scope="col" className="admin-table__num">
                  Precio ud.
                </th>
                <th scope="col" className="admin-table__num">
                  Total
                </th>
              </tr>
            </thead>
            <tbody>
              {order.items.map((item) => (
                <tr key={item.id}>
                  <th scope="row">{item.product_name}</th>
                  <td>{item.game ?? '—'}</td>
                  <td>{item.set_name ?? '—'}</td>
                  <td>
                    {item.card_condition ? (
                      <abbr title={CONDITION_LABELS[item.card_condition]}>{item.card_condition}</abbr>
                    ) : item.product_type === 'booster' ? (
                      'Sellado'
                    ) : (
                      '—'
                    )}
                  </td>
                  <td>{item.language ?? '—'}</td>
                  <td>{item.is_foil === null ? '—' : item.is_foil ? 'Sí' : 'No'}</td>
                  <td className="admin-table__num">{item.quantity}</td>
                  <td className="admin-table__num">{formatPrice(item.unit_price)}</td>
                  <td className="admin-table__num">{formatPrice(item.line_total)}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
        <p className="text-muted admin-note">
          Precios sin IVA. Juego, set, estado, idioma y foil son los datos actuales del producto.
        </p>
      </section>

      <section aria-labelledby="payments-title" className="admin-detail-block">
        <h3 id="payments-title">Pagos</h3>
        {order.payments.length === 0 ? (
          <p className="text-muted">No hay pagos registrados.</p>
        ) : (
          <div className="admin-table-wrap">
            <table className="admin-table">
              <thead>
                <tr>
                  <th scope="col">Fecha</th>
                  <th scope="col">Método</th>
                  <th scope="col">Resultado</th>
                  <th scope="col">Referencia</th>
                  <th scope="col" className="admin-table__num">
                    Importe
                  </th>
                </tr>
              </thead>
              <tbody>
                {order.payments.map((payment) => (
                  <tr key={payment.id}>
                    <td>
                      <DateTime value={payment.created_at} />
                    </td>
                    <td>{PAYMENT_METHOD_LABELS[payment.method] ?? payment.method}</td>
                    <td>
                      <span className={`admin-payment admin-payment--${payment.status}`}>
                        {PAYMENT_STATUS_LABELS[payment.status] ?? payment.status}
                      </span>
                    </td>
                    <td>{payment.reference ?? '—'}</td>
                    <td className="admin-table__num">{formatPrice(payment.amount)}</td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        )}
      </section>

      <section aria-labelledby="history-title" className="admin-detail-block">
        <h3 id="history-title">Historial de estados</h3>
        <ol className="admin-timeline">
          {order.history.map((change, index) => (
            <li key={index}>
              <DateTime value={change.changed_at} />
              <span>
                {change.from_status ? (
                  <>
                    <StatusBadge status={change.from_status} /> → <StatusBadge status={change.to_status} />
                  </>
                ) : (
                  <>
                    Pedido creado: <StatusBadge status={change.to_status} />
                  </>
                )}
              </span>
            </li>
          ))}
        </ol>
      </section>
    </article>
  );
}
