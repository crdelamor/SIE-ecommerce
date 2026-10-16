import { useEffect, useState } from 'react';
import { useLocation, useParams } from 'react-router-dom';
import Button from '../../components/Button.jsx';
import { getOrder } from './checkoutApi.js';
import { formatPrice } from './OrderSummary.jsx';

// Página de "gracias por tu pedido". Los datos salen de GET /api/orders/{code}, que no
// devuelve datos personales; el nombre solo se muestra si venimos del checkout.
export default function ConfirmationPage() {
  const { code } = useParams();
  const { firstName, emailTo } = useLocation().state ?? {};
  const [order, setOrder] = useState(null);
  const [error, setError] = useState('');

  useEffect(() => {
    getOrder(code)
      .then(setOrder)
      .catch((err) => setError(err.message));
  }, [code]);

  if (error) {
    return (
      <div className="confirmation">
        <h1>No encontramos el pedido</h1>
        <p>{error}</p>
        <Button to="/">Volver a la tienda</Button>
      </div>
    );
  }

  if (!order) {
    return <div className="confirmation"><p className="text-muted">Cargando pedido…</p></div>;
  }

  const paid = order.status !== 'pendiente_pago';

  return (
    <div className="confirmation">
      <span className="confirmation__check" aria-hidden="true">✓</span>
      <p className="text-muted">Pedido {order.code}</p>
      <h1>{firstName ? `¡Gracias, ${firstName}!` : '¡Gracias por tu compra!'}</h1>

      <section className="confirmation__box">
        <h2>{paid ? 'Tu pedido está confirmado' : 'Tu pedido está pendiente de pago'}</h2>
        <p>
          {paid
            ? 'Ya estamos preparando tus cartas. Guarda el código del pedido por si necesitas contactar con soporte.'
            : 'Todavía no hemos recibido el pago de este pedido.'}
        </p>
        {emailTo && <p>Te hemos enviado un email a <strong>{emailTo}</strong> con el resumen.</p>}
        <p className="text-muted">Es un prototipo académico: no se ha cobrado nada.</p>
      </section>

      <section className="confirmation__box">
        <h2>Resumen</h2>
        <ul className="confirmation__items">
          {order.items.map((line) => (
            <li key={line.name}>
              <span>{line.quantity} × {line.name}</span>
              <span>{formatPrice(line.line_total)}</span>
            </li>
          ))}
        </ul>
        <dl className="summary__totals">
          <div><dt>Subtotal (sin IVA)</dt><dd>{formatPrice(order.subtotal)}</dd></div>
          {order.discount > 0 && <div><dt>Descuento</dt><dd>−{formatPrice(order.discount)}</dd></div>}
          <div><dt>Envío</dt><dd>{order.shipping > 0 ? formatPrice(order.shipping) : 'Gratis'}</dd></div>
          <div><dt>IVA</dt><dd>{formatPrice(order.tax)}</dd></div>
          <div className="summary__total"><dt>Total</dt><dd>{formatPrice(order.total)}</dd></div>
        </dl>
      </section>

      <Button to="/catalogo">Seguir comprando</Button>
    </div>
  );
}
