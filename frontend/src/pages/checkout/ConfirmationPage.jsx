import { useEffect, useState } from 'react';
import { useLocation, useParams } from 'react-router-dom';
import Button from '../../components/Button.jsx';
import { getOrder } from './checkoutApi.js';
import { formatPrice } from './OrderSummary.jsx';

// Texto según el estado real del pedido (es también el enlace del correo, se puede abrir más tarde)
const STATUS_TEXT = {
  pendiente_pago: ['Tu pedido está pendiente de pago', 'Todavía no hemos recibido el pago de este pedido.'],
  pagado: ['Tu pedido está confirmado', 'Ya estamos preparando tus cartas. Guarda el código del pedido por si necesitas contactar con soporte.'],
  en_preparacion: ['Tu pedido está en preparación', 'Estamos preparando tus cartas para enviarlas.'],
  enviado: ['Tu pedido está en camino', 'Ya lo hemos enviado. Si llega con algún problema, escríbenos desde soporte.'],
  entregado: ['Tu pedido está entregado', 'Esperamos que disfrutes de tus cartas.'],
  cancelado: ['Tu pedido está cancelado', 'Este pedido se ha cancelado. Si tienes dudas, escríbenos desde soporte.'],
  incidencia: ['Tu pedido tiene una incidencia', 'Estamos revisándola y te escribiremos al email del pedido.'],
};

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

  // El ✓ y el "gracias" solo si el pedido está pagado y sigue adelante
  const ok = ['pagado', 'en_preparacion', 'enviado', 'entregado'].includes(order.status);
  const [title, text] = STATUS_TEXT[order.status] ?? [`Estado del pedido: ${order.status}`, ''];

  return (
    <div className="confirmation">
      {ok && <span className="confirmation__check" aria-hidden="true">✓</span>}
      <p className="text-muted">Pedido {order.code}</p>
      <h1>{ok ? (firstName ? `¡Gracias, ${firstName}!` : '¡Gracias por tu compra!') : title}</h1>

      <section className="confirmation__box">
        {ok && <h2>{title}</h2>}
        {text && <p>{text}</p>}
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
