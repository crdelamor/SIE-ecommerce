import { useState } from 'react';

export const formatPrice = (value) =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);

// Columna derecha del checkout: productos, código de descuento y totales que calcula la API.
// En el móvil sale arriba y plegada, como en Shopify.
export default function OrderSummary({ cartItems, quote, error, coupon, onApplyCoupon, onRemoveCoupon }) {
  const [open, setOpen] = useState(false);
  const [code, setCode] = useState('');
  const [couponError, setCouponError] = useState('');
  const [applying, setApplying] = useState(false);

  // La imagen sale del carrito; nombre, cantidad y precios, de la API
  const image = (productId) => cartItems.find((item) => item.productId === productId)?.imageUrl;

  async function handleApply(event) {
    event.preventDefault();
    if (!code.trim()) return;
    setApplying(true);
    setCouponError('');
    try {
      await onApplyCoupon(code.trim());
      setCode('');
    } catch (err) {
      setCouponError(err.message);
    }
    setApplying(false);
  }

  return (
    <aside className={`summary${open ? ' is-open' : ''}`} aria-labelledby="summary-title">
      <button type="button" className="summary__toggle" onClick={() => setOpen(!open)} aria-expanded={open}>
        <span>{open ? 'Ocultar resumen del pedido' : 'Mostrar resumen del pedido'}</span>
        {quote && <strong>{formatPrice(quote.total)}</strong>}
      </button>

      <div className="summary__body">
        <h2 id="summary-title" className="sr-only">Resumen del pedido</h2>

        {error && <p className="checkout-form__error" role="alert">{error}</p>}
        {!quote && !error && <p className="text-muted">Calculando…</p>}

        {quote && (
          <>
            <ul className="summary__items">
              {quote.items.map((line) => (
                <li key={line.product_id} className="summary__item">
                  <span className="summary__thumb">
                    {image(line.product_id) && <img src={image(line.product_id)} alt="" />}
                    <span className="summary__qty" aria-label={`${line.quantity} unidades`}>{line.quantity}</span>
                  </span>
                  <span className="summary__name">{line.name}</span>
                  <span>{formatPrice(line.line_total)}</span>
                </li>
              ))}
            </ul>

            {coupon ? (
              <p className="summary__coupon">
                Código <strong>{coupon}</strong> aplicado
                <button type="button" className="link summary__remove" onClick={onRemoveCoupon}>
                  Quitar
                </button>
              </p>
            ) : (
              <form className="summary__discount" onSubmit={handleApply}>
                <label htmlFor="coupon" className="sr-only">Código de descuento</label>
                <input
                  id="coupon"
                  className="checkout-form__input"
                  placeholder="Código de descuento"
                  value={code}
                  onChange={(e) => setCode(e.target.value)}
                  aria-invalid={couponError ? true : undefined}
                  aria-describedby={couponError ? 'coupon-error' : undefined}
                />
                <button type="submit" className="button button--secondary" disabled={applying || !code.trim()}>
                  Aplicar
                </button>
                {couponError && <p id="coupon-error" className="checkout-form__error">{couponError}</p>}
              </form>
            )}

            <dl className="summary__totals">
              <div><dt>Subtotal (sin IVA)</dt><dd>{formatPrice(quote.subtotal)}</dd></div>
              {quote.discount > 0 && (
                <div><dt>Descuento</dt><dd>−{formatPrice(quote.discount)}</dd></div>
              )}
              <div><dt>Envío</dt><dd>{quote.shipping > 0 ? formatPrice(quote.shipping) : 'Gratis'}</dd></div>
              <div><dt>IVA</dt><dd>{formatPrice(quote.tax)}</dd></div>
              <div className="summary__total"><dt>Total</dt><dd>{formatPrice(quote.total)}</dd></div>
            </dl>
          </>
        )}
      </div>
    </aside>
  );
}
