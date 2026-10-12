import { getSessionId } from '../../lib/events.js';

// Llamadas a la API del flujo de compra. Si falla lanza un Error con el mensaje del servidor.
async function request(path, body) {
  let response;
  try {
    response = await fetch(`/api${path}`, {
      method: body ? 'POST' : 'GET',
      headers: { 'Content-Type': 'application/json', 'X-Session-Id': getSessionId() },
      body: body ? JSON.stringify(body) : undefined,
    });
  } catch {
    throw new Error('No se ha podido conectar con el servidor');
  }

  const data = await response.json().catch(() => null);
  if (!response.ok) {
    const error = new Error(data?.error ?? `Error ${response.status}`);
    error.status = response.status;
    throw error;
  }
  return data;
}

// El carrito tal como lo espera la API: [{ product_id, quantity }]
function cartToItems(items) {
  return items.map((item) => ({ product_id: item.productId, quantity: item.quantity }));
}

// Totales calculados por el servidor (el error lleva .status: 400 cupón mal, 409 sin stock)
export function getQuote(items, coupon) {
  return request('/checkout/quote', { items: cartToItems(items), coupon });
}

export async function createOrder(items, coupon, customer, billing) {
  const data = await request('/orders', { items: cartToItems(items), coupon, customer, billing });
  return data.order;
}

// payment: { method: 'tarjeta_simulada', card_number } o { method: 'bizum_simulado', phone }
export async function payOrder(code, payment) {
  const data = await request(`/orders/${encodeURIComponent(code)}/pay`, payment);
  return data.payment;
}

export async function getOrder(code) {
  const data = await request(`/orders/${encodeURIComponent(code)}`);
  return data.order;
}
