import { useEffect, useRef, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import Button from '../../components/Button.jsx';
import { Section } from '../../components/Container.jsx';
import { sendSupportRequest } from './supportApi.js';
import './support.css';

// Mismas claves que SupportService::REASONS en la API
const REASONS = [
  ['estado_conservacion', 'La carta está en peor estado del anunciado'],
  ['idioma_version', 'Idioma o versión incorrectos (por ejemplo, sin foil)'],
  ['dano_envio', 'Ha llegado dañada en el envío'],
  ['falta_producto', 'Falta algún producto'],
  ['otro', 'Otro motivo'],
];

const FIELD_ORDER = ['name', 'email', 'order_code', 'reason', 'message'];
const MESSAGE_MAX = 2000;

/**
 * Formulario público de soporte e incidencias. El código de pedido es opcional y se
 * puede rellenar desde la URL (#/soporte?pedido=PED-000123). Todo se valida en la API:
 * aquí solo se muestran sus mensajes junto a cada campo.
 */
export default function SupportPage() {
  const [searchParams] = useSearchParams();
  const emptyForm = { name: '', email: '', order_code: searchParams.get('pedido') ?? '', reason: '', message: '' };

  const [form, setForm] = useState(emptyForm);
  const [fieldErrors, setFieldErrors] = useState({});
  const [error, setError] = useState('');
  const [sending, setSending] = useState(false);
  const [ticketId, setTicketId] = useState(null);
  const fieldRefs = useRef({});
  const successRef = useRef(null);

  // Tras enviar, el foco va al mensaje de confirmación para que se anuncie
  useEffect(() => {
    if (ticketId) successRef.current?.focus();
  }, [ticketId]);

  function update(name, value) {
    setForm((current) => ({ ...current, [name]: value }));
    // El error de un campo desaparece en cuanto se corrige
    if (fieldErrors[name]) setFieldErrors((current) => ({ ...current, [name]: undefined }));
  }

  async function handleSubmit(event) {
    event.preventDefault();
    setSending(true);
    setError('');
    setFieldErrors({});
    try {
      const result = await sendSupportRequest(form);
      setTicketId(result.ticket_id);
    } catch (err) {
      setError(err.message);
      setFieldErrors(err.fields);
      // El foco va al primer campo con error
      const first = FIELD_ORDER.find((name) => err.fields[name]);
      if (first) fieldRefs.current[first]?.focus();
    } finally {
      setSending(false);
    }
  }

  function startAgain() {
    setForm({ ...emptyForm, order_code: '' });
    setTicketId(null);
  }

  // Ayudas bajo cada campo (se leen junto al campo gracias a aria-describedby)
  const hints = {
    order_code: 'Opcional. Lo tienes en el email de confirmación del pedido.',
    message: `${form.message.length} / ${MESSAGE_MAX} caracteres`,
  };

  /** Props comunes de cada campo: id, valor, error enlazado y aria-invalid. */
  function fieldProps(name) {
    return {
      id: `support-${name}`,
      name,
      value: form[name],
      onChange: (e) => update(name, e.target.value),
      ref: (el) => {
        fieldRefs.current[name] = el;
      },
      className: 'support-form__input',
      'aria-invalid': fieldErrors[name] ? true : undefined,
      'aria-describedby': [fieldErrors[name] && `support-${name}-error`, hints[name] && `support-${name}-hint`]
        .filter(Boolean)
        .join(' ') || undefined,
    };
  }

  /** Ayuda y error que se muestran debajo de un campo. */
  function fieldMessages(name) {
    return (
      <>
        {hints[name] && (
          <p id={`support-${name}-hint`} className="support-form__hint">
            {hints[name]}
          </p>
        )}
        {fieldErrors[name] && (
          <p id={`support-${name}-error`} className="support-form__error">
            {fieldErrors[name]}
          </p>
        )}
      </>
    );
  }

  if (ticketId) {
    return (
      <Section>
        <div className="support">
          <h1 ref={successRef} tabIndex={-1}>
            Hemos recibido tu consulta
          </h1>
          <p role="status">
            Tu número de incidencia es el <strong>{ticketId}</strong>. Te responderemos en el email que nos has
            indicado.
          </p>
          <Button variant="secondary" onClick={startAgain}>
            Enviar otra consulta
          </Button>
        </div>
      </Section>
    );
  }

  return (
    <Section>
      <div className="support">
        <h1>Soporte</h1>
        <p>
          ¿Algún problema con tu pedido? Cuéntanos qué ha pasado y te responderemos lo antes posible. Si es sobre
          un pedido concreto, indica su código.
        </p>

        <form className="support-form" onSubmit={handleSubmit} noValidate>
          <div className="support-form__field">
            <label htmlFor="support-name" className="support-form__label">
              Nombre
            </label>
            <input {...fieldProps('name')} type="text" autoComplete="name" maxLength={120} required />
            {fieldMessages('name')}
          </div>

          <div className="support-form__field">
            <label htmlFor="support-email" className="support-form__label">
              Email
            </label>
            <input {...fieldProps('email')} type="email" autoComplete="email" maxLength={150} required />
            {fieldMessages('email')}
          </div>

          <div className="support-form__field">
            <label htmlFor="support-order_code" className="support-form__label">
              Código de pedido <span className="support-form__optional">(opcional)</span>
            </label>
            <input {...fieldProps('order_code')} type="text" placeholder="PED-000123" maxLength={10} />
            {fieldMessages('order_code')}
          </div>

          <div className="support-form__field">
            <label htmlFor="support-reason" className="support-form__label">
              Motivo
            </label>
            <select {...fieldProps('reason')} required>
              <option value="" disabled>
                Elige un motivo
              </option>
              {REASONS.map(([value, label]) => (
                <option key={value} value={value}>
                  {label}
                </option>
              ))}
            </select>
            {fieldMessages('reason')}
          </div>

          <div className="support-form__field">
            <label htmlFor="support-message" className="support-form__label">
              ¿Qué ha pasado?
            </label>
            <textarea {...fieldProps('message')} rows={6} maxLength={MESSAGE_MAX} required />
            {fieldMessages('message')}
          </div>

          {error && (
            <p className="support-form__error support-form__summary" role="alert">
              {error}
            </p>
          )}

          <p className="support-form__privacy">
            Usaremos estos datos solo para responder a tu consulta. Esta tienda es un prototipo académico: no
            escribas datos personales reales.
          </p>

          <Button type="submit" disabled={sending}>
            {sending ? 'Enviando…' : 'Enviar consulta'}
          </Button>
        </form>
      </div>
    </Section>
  );
}
