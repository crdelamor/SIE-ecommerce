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

export function logout() {
  return request('/auth/logout', { method: 'POST' });
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
