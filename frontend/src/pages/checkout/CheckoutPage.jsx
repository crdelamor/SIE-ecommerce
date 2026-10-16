import { useEffect, useRef, useState } from 'react';
import { useNavigate } from 'react-router-dom';
import Button from '../../components/Button.jsx';
import { useCart } from '../../context/CartContext.jsx';
import { trackEvent } from '../../lib/events.js';
import { createOrder, getQuote, payOrder } from './checkoutApi.js';
import OrderSummary, { formatPrice } from './OrderSummary.jsx';

const EMPTY_FORM = {
  email: '',
  firstName: '', lastName: '', address: '', address2: '', postalCode: '', city: '', phone: '',
  cardNumber: '', cardExpiry: '', cardCvc: '', cardName: '', bizumPhone: '',
  billFirstName: '', billLastName: '', billAddress: '', billPostalCode: '', billCity: '',
};

const POSTAL_CODE = /^\d{5}$/;

// Misma validación que hace el servidor (allí se vuelve a comprobar). Devuelve { campo: mensaje }.
function validate(form, payment, billingSame) {
  const errors = {};
  const empty = (name) => !form[name].trim();

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email.trim())) errors.email = 'Escribe un email válido';
  if (empty('firstName')) errors.firstName = 'Escribe tu nombre';
  if (empty('lastName')) errors.lastName = 'Escribe tus apellidos';
  if (empty('address')) errors.address = 'Escribe tu dirección';
  if (!POSTAL_CODE.test(form.postalCode.trim())) errors.postalCode = 'El código postal tiene 5 números';

  if (payment === 'tarjeta_simulada') {
    if (!/^\d{16}$/.test(form.cardNumber.replace(/\s/g, ''))) errors.cardNumber = 'La tarjeta tiene 16 números';
    if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(form.cardExpiry.trim())) errors.cardExpiry = 'Usa el formato MM/AA';
    if (!/^\d{3}$/.test(form.cardCvc.trim())) errors.cardCvc = '3 números';
    if (empty('cardName')) errors.cardName = 'Escribe el nombre de la tarjeta';
  } else if (!/^[67]\d{8}$/.test(form.bizumPhone.replace(/\s/g, ''))) {
    errors.bizumPhone = 'Escribe un móvil de 9 números';
  }

  if (!billingSame) {
    if (empty('billFirstName')) errors.billFirstName = 'Escribe el nombre';
    if (empty('billLastName')) errors.billLastName = 'Escribe los apellidos';
    if (empty('billAddress')) errors.billAddress = 'Escribe la dirección';
    if (!POSTAL_CODE.test(form.billPostalCode.trim())) errors.billPostalCode = 'El código postal tiene 5 números';
  }
  return errors;
}

export default function CheckoutPage() {
  const { items, itemCount, clearCart } = useCart();
  const navigate = useNavigate();
  const [form, setForm] = useState(EMPTY_FORM);
  const [payment, setPayment] = useState('tarjeta_simulada');
  const [billingSame, setBillingSame] = useState(true);
  const [errors, setErrors] = useState({});
  const [payError, setPayError] = useState('');
  const [paying, setPaying] = useState(false);
  const [coupon, setCoupon] = useState(null);
  const [quote, setQuote] = useState(null);
  const [quoteError, setQuoteError] = useState('');
  // Pedido ya creado en un intento de pago rechazado: se reintenta el pago sobre el mismo
  const [pendingOrder, setPendingOrder] = useState(null);
  const eventSent = useRef(false);

  // checkout.started al entrar en el checkout con productos (el ref evita enviarlo dos veces en desarrollo)
  useEffect(() => {
    if (!eventSent.current && itemCount > 0) {
      eventSent.current = true;
      trackEvent('checkout.started', null, null, { item_count: itemCount });
    }
  }, [itemCount]);

  // Los totales los calcula siempre la API
  useEffect(() => {
    if (items.length === 0) return;
    getQuote(items, coupon)
      .then((data) => {
        setQuote(data);
        setQuoteError('');
      })
      .catch((err) => setQuoteError(err.message));
  }, [items, coupon]);

  async function applyCoupon(code) {
    const data = await getQuote(items, code); // si el cupón no vale, lanza el error y lo enseña el resumen
    setQuote(data);
    setCoupon(data.coupon);
  }

  function handleChange(event) {
    const { name, value } = event.target;
    setForm({ ...form, [name]: value });
    if (errors[name]) setErrors({ ...errors, [name]: undefined });
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setPayError('');

    const newErrors = validate(form, payment, billingSame);
    setErrors(newErrors);
    const firstError = Object.keys(newErrors)[0];
    if (firstError) {
      document.getElementById(`checkout-${firstError}`).focus();
      return;
    }

    const customer = {
      name: `${form.firstName.trim()} ${form.lastName.trim()}`,
      email: form.email,
      phone: form.phone,
      address: form.address2.trim() ? `${form.address.trim()}, ${form.address2.trim()}` : form.address,
      city: form.city,
      postal_code: form.postalCode,
    };
    const billing = billingSame
      ? null
      : {
          name: `${form.billFirstName.trim()} ${form.billLastName.trim()}`,
          address: form.billAddress,
          city: form.billCity,
          postal_code: form.billPostalCode,
        };
    // La tarjeta solo se usa para el pago simulado; la caducidad y el CVC no se envían
    const paymentData =
      payment === 'tarjeta_simulada'
        ? { method: payment, card_number: form.cardNumber }
        : { method: payment, phone: form.bizumPhone };

    setPaying(true);
    try {
      // Si el pago anterior se rechazó y no ha cambiado nada, se paga el mismo pedido.
      // Si ha cambiado algo, se crea otro y el servidor cancela el anterior (devuelve su stock).
      const orderData = JSON.stringify({ items, coupon, customer, billing });
      let code = pendingOrder?.data === orderData ? pendingOrder.code : null;
      if (!code) {
        code = (await createOrder(items, coupon, customer, billing, pendingOrder?.code ?? null)).code;
        setPendingOrder({ code, data: orderData });
      }

      const result = await payOrder(code, paymentData);
      if (result.approved) {
        // el email solo viaja en el estado de la navegación, la API de confirmación no lo devuelve
        const emailTo = result.email_sent ? form.email.trim() : null;
        navigate(`/pedido/${code}`, { state: { firstName: form.firstName.trim(), emailTo } });
        clearCart();
        return;
      }
      setPayError('El banco ha rechazado el pago. Prueba con otra tarjeta o con Bizum.');
    } catch (err) {
      setPayError(err.message);
    }
    setPaying(false);
  }

  // Props de cada campo: id, valor y el error enlazado para el lector de pantalla
  function field(name) {
    return {
      id: `checkout-${name}`,
      name,
      value: form[name],
      onChange: handleChange,
      className: 'checkout-form__input',
      'aria-invalid': errors[name] ? true : undefined,
      'aria-describedby': errors[name] ? `checkout-${name}-error` : undefined,
    };
  }

  function input(name, label, props = {}) {
    return (
      <div className="checkout-form__field">
        <label htmlFor={`checkout-${name}`} className="checkout-form__label">{label}</label>
        <input type="text" {...props} {...field(name)} />
        {errors[name] && (
          <p id={`checkout-${name}-error`} className="checkout-form__error">{errors[name]}</p>
        )}
      </div>
    );
  }

  const optional = (text) => (
    <>
      {text} <span className="checkout-form__optional">(opcional)</span>
    </>
  );

  if (items.length === 0) {
    return (
      <div className="checkout-empty">
        <h1>Tu carrito está vacío</h1>
        <Button to="/catalogo">Ver el catálogo</Button>
      </div>
    );
  }

  return (
    <div className="checkout-page">
      <OrderSummary
        cartItems={items}
        quote={quote}
        error={quoteError}
        coupon={coupon}
        onApplyCoupon={applyCoupon}
        onRemoveCoupon={() => setCoupon(null)}
      />

      <form className="checkout-main" onSubmit={handleSubmit} noValidate>
        <h1 className="sr-only">Finalizar compra</h1>

        <section className="checkout-section">
          <h2>Contacto</h2>
          {input('email', 'Email', { type: 'email', autoComplete: 'email' })}
        </section>

        <section className="checkout-section">
          <h2>Entrega</h2>
          <div className="checkout-form__field">
            <label htmlFor="checkout-country" className="checkout-form__label">País</label>
            <select id="checkout-country" className="checkout-form__input" disabled>
              <option>España</option>
            </select>
          </div>
          <div className="checkout-form__row">
            {input('firstName', 'Nombre', { autoComplete: 'given-name' })}
            {input('lastName', 'Apellidos', { autoComplete: 'family-name' })}
          </div>
          {input('address', 'Dirección', { autoComplete: 'address-line1' })}
          {input('address2', optional('Casa, piso, puerta, etc.'), { autoComplete: 'address-line2' })}
          <div className="checkout-form__row">
            {input('postalCode', 'Código postal', { inputMode: 'numeric', maxLength: 5, autoComplete: 'postal-code' })}
            {input('city', optional('Ciudad'), { autoComplete: 'address-level2' })}
          </div>
          {input('phone', optional('Teléfono'), { type: 'tel', autoComplete: 'tel' })}
        </section>

        <section className="checkout-section">
          <h2>Método de envío</h2>
          <div className="choice is-selected">
            <span>Envío estándar (2-4 días laborables)</span>
            <strong>{quote ? (quote.shipping > 0 ? formatPrice(quote.shipping) : 'Gratis') : '…'}</strong>
          </div>
        </section>

        <section className="checkout-section">
          <h2>Pago</h2>
          <p className="text-muted">Pago simulado: no se cobra nada. Las tarjetas de prueba están en el README.</p>
          <fieldset className="choices">
            <legend className="sr-only">Método de pago</legend>
            <label className={`choice${payment === 'tarjeta_simulada' ? ' is-selected' : ''}`}>
              <input
                type="radio"
                name="payment"
                checked={payment === 'tarjeta_simulada'}
                onChange={() => setPayment('tarjeta_simulada')}
              />
              Tarjeta de prueba
            </label>
            {payment === 'tarjeta_simulada' && (
              <div className="choice__body">
                {input('cardNumber', 'Número de tarjeta', { inputMode: 'numeric', autoComplete: 'off' })}
                <div className="checkout-form__row">
                  {input('cardExpiry', 'Caducidad (MM/AA)', { autoComplete: 'off', maxLength: 5 })}
                  {input('cardCvc', 'CVC', { inputMode: 'numeric', autoComplete: 'off', maxLength: 3 })}
                </div>
                {input('cardName', 'Nombre en la tarjeta', { autoComplete: 'off' })}
              </div>
            )}

            <label className={`choice${payment === 'bizum_simulado' ? ' is-selected' : ''}`}>
              <input
                type="radio"
                name="payment"
                checked={payment === 'bizum_simulado'}
                onChange={() => setPayment('bizum_simulado')}
              />
              Bizum (simulado)
            </label>
            {payment === 'bizum_simulado' && (
              <div className="choice__body">
                {input('bizumPhone', 'Móvil', { type: 'tel', autoComplete: 'off' })}
              </div>
            )}
          </fieldset>
        </section>

        <section className="checkout-section">
          <h2>Dirección de facturación</h2>
          <fieldset className="choices">
            <legend className="sr-only">Dirección de facturación</legend>
            <label className={`choice${billingSame ? ' is-selected' : ''}`}>
              <input type="radio" name="billing" checked={billingSame} onChange={() => setBillingSame(true)} />
              La misma que la dirección de envío
            </label>
            <label className={`choice${!billingSame ? ' is-selected' : ''}`}>
              <input type="radio" name="billing" checked={!billingSame} onChange={() => setBillingSame(false)} />
              Usar una dirección de facturación distinta
            </label>
            {!billingSame && (
              <div className="choice__body">
                <div className="checkout-form__row">
                  {input('billFirstName', 'Nombre')}
                  {input('billLastName', 'Apellidos')}
                </div>
                {input('billAddress', 'Dirección')}
                <div className="checkout-form__row">
                  {input('billPostalCode', 'Código postal', { inputMode: 'numeric', maxLength: 5 })}
                  {input('billCity', optional('Ciudad'))}
                </div>
              </div>
            )}
          </fieldset>
        </section>

        {payError && (
          <p className="checkout-form__error checkout-pay-error" role="alert">
            {payError}
          </p>
        )}

        <Button type="submit" className="checkout-pay" disabled={paying || !quote || Boolean(quoteError)}>
          {paying ? 'Procesando pago…' : 'Pagar ahora'}
        </Button>
      </form>
    </div>
  );
}
