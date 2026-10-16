import { getSessionId } from '../../lib/events.js';

// Llamadas de solo lectura al catálogo (GET /api/categories, /api/products y /api/products/{id}).
// Si falla lanzan un Error con el mensaje del servidor y el código HTTP en error.status.
async function request(path, signal) {
  let response;
  try {
    // La visita se identifica con la misma cabecera anónima que usa el resto de la tienda,
    // para que product.viewed quede unido a los demás eventos de esa visita
    response = await fetch(`/api${path}`, { headers: { 'X-Session-Id': getSessionId() }, signal });
  } catch (error) {
    if (error.name === 'AbortError') throw error;
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

/** Categorías (una por juego): [{ id, name, slug }]. */
export async function getCategories(signal) {
  return (await request('/categories', signal)).categories;
}

/** Productos activos; con `category` (slug) solo los de ese juego. */
export async function getProducts(category, signal) {
  const query = category ? `?category=${encodeURIComponent(category)}` : '';
  return (await request(`/products${query}`, signal)).products;
}

/** Ficha de un producto, con las demás versiones de la misma carta. Registra product.viewed en el servidor. */
export async function getProduct(id, signal) {
  return (await request(`/products/${encodeURIComponent(id)}`, signal)).product;
}
