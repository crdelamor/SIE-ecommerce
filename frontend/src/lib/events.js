/**
 * Envía a la API un evento generado en el navegador (cart.item_added, checkout.started).
 * El servidor lo guarda con logEvent(). Si falla, la compra sigue sin interrumpirse.
 *
 *   trackEvent('cart.item_added', 'product', 3, { quantity: 1 });
 */
export function trackEvent(type, entityType = null, entityId = null, payload = {}) {
  fetch('/api/events', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Session-Id': getSessionId() },
    body: JSON.stringify({ type, entity_type: entityType, entity_id: entityId, payload }),
  }).catch(() => {});
}

/**
 * Identificador anónimo de la visita, guardado en localStorage.
 * Sirve para agrupar los eventos de un mismo visitante sin guardar datos personales.
 */
function getSessionId() {
  try {
    let id = localStorage.getItem('sie.sessionId');
    if (!id) {
      id = `${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
      localStorage.setItem('sie.sessionId', id);
    }
    return id;
  } catch {
    return '';
  }
}
