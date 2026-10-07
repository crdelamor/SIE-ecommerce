import { useEffect, useRef, useState } from 'react';
import Button from '../../components/Button.jsx';
import StatusBadge from './StatusBadge.jsx';
import { changeOrderStatus, getOrder } from './adminApi.js';
import { STATUS_ACTIONS, STATUS_LABELS } from './adminFormat.js';

// Acciones que paran o cierran el pedido: se pintan como botón secundario
const SECONDARY_ACTIONS = ['cancelado', 'incidencia'];

/**
 * Tarjeta "Cambiar estado" de la ficha. Solo muestra botones para las transiciones
 * que devuelve el servidor (allowed_transitions) y pide confirmación antes de
 * aplicar el cambio. La validación real la vuelve a hacer la API.
 */
export default function OrderStatusActions({ order, onChanged, onUnauthorized }) {
  const [target, setTarget] = useState(null); // estado elegido, pendiente de confirmar
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState('');
  const [error, setError] = useState('');
  const confirmRef = useRef(null);

  // Al pedir confirmación, el foco va a la pregunta para que el teclado y el lector la sigan
  useEffect(() => {
    if (target) confirmRef.current?.focus();
  }, [target]);

  function choose(status) {
    setMessage('');
    setError('');
    setTarget(status);
  }

  async function confirm() {
    setSaving(true);
    setError('');
    try {
      const updated = await changeOrderStatus(order.id, target);
      setMessage(`Estado cambiado a «${STATUS_LABELS[updated.status] ?? updated.status}».`);
      setTarget(null);
      onChanged(updated);
    } catch (err) {
      if (err.status === 401) {
        onUnauthorized();
        return;
      }
      setTarget(null);
      if (err.status === 409) {
        // Otra persona pudo cambiar el pedido justo antes: se recarga el estado real
        setError(`${err.message}. Puede que alguien lo haya cambiado antes; se muestra el estado actual.`);
        try {
          onChanged(await getOrder(order.id));
        } catch {
          // Si tampoco se puede recargar, se queda el mensaje de error
        }
      } else {
        setError(err.message);
      }
    } finally {
      setSaving(false);
    }
  }

  const transitions = order.allowed_transitions;

  return (
    <section className="admin-card" aria-labelledby="status-actions-title">
      <h3 id="status-actions-title">Cambiar estado</h3>

      {message && (
        <p className="admin-success" role="status">
          {message}
        </p>
      )}
      {error && (
        <p className="admin-form__error" role="alert">
          {error}
        </p>
      )}

      {transitions.length === 0 && (
        <p className="text-muted">
          {order.status === 'incidencia'
            ? 'Las salidas de "Incidencia" están pendientes de acordar.'
            : 'Estado final: este pedido ya no cambia.'}
        </p>
      )}

      {transitions.length > 0 && target && (
        <div className="admin-confirm">
          <p ref={confirmRef} tabIndex={-1}>
            ¿Pasar el pedido <strong>{order.code}</strong> de <StatusBadge status={order.status} /> a{' '}
            <StatusBadge status={target} />?
          </p>
          <p className="text-muted admin-note">El cambio quedará registrado en el historial.</p>
          <div className="admin-actions">
            <Button onClick={confirm} disabled={saving}>
              {saving ? 'Guardando…' : 'Confirmar'}
            </Button>
            <Button variant="secondary" onClick={() => setTarget(null)} disabled={saving}>
              Volver
            </Button>
          </div>
        </div>
      )}

      {transitions.length > 0 && !target && (
        <div className="admin-actions">
          {transitions.map((status) => (
            <Button
              key={status}
              variant={SECONDARY_ACTIONS.includes(status) ? 'secondary' : 'primary'}
              onClick={() => choose(status)}
            >
              {STATUS_ACTIONS[status] ?? status}
            </Button>
          ))}
        </div>
      )}
    </section>
  );
}
