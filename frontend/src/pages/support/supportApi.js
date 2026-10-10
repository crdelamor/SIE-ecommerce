/** Llamada a la API del formulario público de soporte. */

/** Error del formulario: `fields` trae el mensaje de cada campo no válido (422). */
export class SupportError extends Error {
  constructor(message, fields = {}) {
    super(message);
    this.name = 'SupportError';
    this.fields = fields;
  }
}

// Mismo identificador anónimo de la visita que usan los eventos de la tienda (lib/events.js),
// para que support.requested quede unido al resto de eventos de esa visita.
function sessionId() {
  try {
    return localStorage.getItem('sie.sessionId') ?? '';
  } catch {
    return '';
  }
}

/** Envía la incidencia. Devuelve { ticket_id, status } o lanza SupportError. */
export async function sendSupportRequest(data) {
  let response;
  try {
    response = await fetch('/api/support', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-Session-Id': sessionId() },
      body: JSON.stringify(data),
    });
  } catch {
    throw new SupportError('No se ha podido conectar con el servidor. Inténtalo de nuevo en unos minutos.');
  }

  const body = await response.json().catch(() => null);
  if (!response.ok) {
    throw new SupportError(body?.error ?? `Error ${response.status}`, body?.fields ?? {});
  }
  return body;
}
