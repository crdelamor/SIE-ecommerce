import { createContext, useContext, useEffect, useState } from 'react';
import { trackEvent } from '../lib/events.js';

/**
 * Carrito de la tienda. Se guarda en localStorage para no perderlo al recargar.
 *
 *   const { addItem } = useCart();
 *   addItem(producto);  // producto tal cual llega de la API (id, name, image_url...)
 *
 * Solo guarda qué productos y cuántos. Los precios y totales los calculará el servidor.
 */

const STORAGE_KEY = 'sie.cart';

const CartContext = createContext(null);

function loadCart() {
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY));
    return Array.isArray(saved) ? saved : [];
  } catch {
    return [];
  }
}

export function CartProvider({ children }) {
  const [items, setItems] = useState(loadCart);

  useEffect(() => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    } catch {
      // Sin localStorage el carrito funciona igual, pero no se conserva al recargar
    }
  }, [items]);

  function addItem(product, quantity = 1) {
    setItems((current) => {
      const existing = current.find((item) => item.productId === product.id);
      if (existing) {
        return current.map((item) =>
          item.productId === product.id ? { ...item, quantity: item.quantity + quantity } : item,
        );
      }
      return [
        ...current,
        { productId: product.id, name: product.name, imageUrl: product.image_url ?? null, quantity },
      ];
    });
    trackEvent('cart.item_added', 'product', product.id, { quantity });
  }

  function updateQuantity(productId, quantity) {
    setItems((current) =>
      current.map((item) => (item.productId === productId ? { ...item, quantity } : item)),
    );
  }

  function removeItem(productId) {
    setItems((current) => current.filter((item) => item.productId !== productId));
  }

  const itemCount = items.reduce((total, item) => total + item.quantity, 0);

  return (
    <CartContext.Provider value={{ items, itemCount, addItem, updateQuantity, removeItem }}>
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  return useContext(CartContext);
}
