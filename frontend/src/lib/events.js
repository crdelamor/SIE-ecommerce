// Manda un evento del navegador a la API. Si falla no pasa nada, la compra sigue.
export function trackEvent(type, entityType = null, entityId = null, payload = {}) {
  fetch('/api/events', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'X-Session-Id': getSessionId() },
    body: JSON.stringify({ type, entity_type: entityType, entity_id: entityId, payload }),
  }).catch(() => {});
}

// Id anónimo de la visita para agrupar sus eventos (sin datos personales)
export function getSessionId() {
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
