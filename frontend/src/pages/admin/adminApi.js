/**
 * Llamadas a la API de administración (/api/auth, /api/admin/*).
 * Si la API responde 401, la pantalla debe volver al login.
 */

/** Error de la API con su código HTTP (401 = sin sesión, 403 = sin permiso, 0 = sin conexión). */
export class ApiError extends Error {
  constructor(message, status) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
  }
}

async function request(path, { method = 'GET', body } = {}) {
  let response;
  try {
    response = await fetch(`/api${path}`, {
      method,
      headers: body ? { 'Content-Type': 'application/json' } : undefined,
      body: body ? JSON.stringify(body) : undefined,
      credentials: 'same-origin', // envía la cookie de sesión
    });
  } catch {
    throw new ApiError('No se ha podido conectar con el servidor', 0);
  }

  const data = await response.json().catch(() => null);
  if (!response.ok) {
    throw new ApiError(data?.error ?? `Error ${response.status}`, response.status);
  }
  return data;
}

/** Devuelve el usuario ({ id, email, role }) si el email y la contraseña son correctos. */
export async function login(email, password) {
  const data = await request('/auth/login', { method: 'POST', body: { email, password } });
  return data.user;
}

// Con cuerpo JSON vacío: el hosting rechaza con 403 los POST sin cuerpo y la sesión no se cerraba
export function logout() {
  return request('/auth/logout', { method: 'POST', body: {} });
}

/**
 * Una página de pedidos, del más reciente al más antiguo.
 * Filtros opcionales: { status, from, to, q, page } (los vacíos no se envían).
 * Devuelve { orders, total, page, per_page }.
 */
export function getOrders(filters = {}) {
  const params = new URLSearchParams();
  for (const [name, value] of Object.entries(filters)) {
    if (value) params.set(name, value);
  }
  const query = params.toString();
  return request(`/admin/orders${query ? `?${query}` : ''}`);
}

/** Ficha completa de un pedido (cliente, líneas, pagos, historial y transiciones permitidas). */
export async function getOrder(id) {
  const data = await request(`/admin/orders/${encodeURIComponent(id)}`);
  return data.order;
}

/** Una página de incidencias de soporte. `status` vacío = todas. Devuelve { tickets, total, page, per_page }. */
export function getSupportTickets({ status = '', page = 1 } = {}) {
  const params = new URLSearchParams();
  if (status) params.set('status', status);
  if (page > 1) params.set('page', String(page));
  const query = params.toString();
  return request(`/admin/support${query ? `?${query}` : ''}`);
}

/** Filtros del visor de eventos como query string ({ type, from, to, page }; los vacíos no se envían). */
function eventQuery(filters) {
  const params = new URLSearchParams();
  for (const [name, value] of Object.entries(filters)) {
    if (value) params.set(name, value);
  }
  return params.toString();
}

/** Una página de eventos. Devuelve { events, total, page, per_page, types }. */
export function getEvents(filters = {}) {
  const query = eventQuery(filters);
  return request(`/admin/events${query ? `?${query}` : ''}`);
}

/** Dirección de descarga de la exportación (csv o json) con los mismos filtros del visor. */
export function eventsExportUrl(format, { type, from, to } = {}) {
  return `/api/admin/events/export?${eventQuery({ format, type, from, to })}`;
}

/** Cambia el estado del pedido. El servidor valida la transición (409 si no se permite). */
export async function changeOrderStatus(id, status) {
  const data = await request(`/admin/orders/${encodeURIComponent(id)}/status`, {
    method: 'PATCH',
    body: { status },
  });
  return data.order;
}
