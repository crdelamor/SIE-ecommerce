import { createContext, useContext, useEffect, useState } from 'react';
import { trackEvent } from '../lib/events.js';

// Carrito guardado en localStorage. Los precios no se guardan, los calcula el servidor.
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
  // Último producto añadido, para el aviso "Añadido al carrito" (key cambia aunque se añada el mismo)
  const [added, setAdded] = useState(null);

  useEffect(() => {
    try {
      localStorage.setItem(STORAGE_KEY, JSON.stringify(items));
    } catch {
      // si no hay localStorage simplemente no se guarda
    }
  }, [items]);

  function addItem(product, quantity = 1) {
    // No se deja meter más unidades de las que hay (el servidor lo vuelve a comprobar al pagar)
    const inCart = items.find((item) => item.productId === product.id)?.quantity ?? 0;
    if (inCart + quantity > product.stock) {
      setAdded({ name: product.name, key: Date.now(), stock: product.stock, full: true });
      return;
    }

    setItems((current) => {
      const existing = current.find((item) => item.productId === product.id);
      if (existing) {
        return current.map((item) =>
          item.productId === product.id
            ? { ...item, quantity: item.quantity + quantity, stock: product.stock }
            : item,
        );
      }
      return [
        ...current,
        { productId: product.id, name: product.name, imageUrl: product.image_url ?? null, quantity, stock: product.stock },
      ];
    });
    setAdded({ name: product.name, key: Date.now() });
    trackEvent('cart.item_added', 'product', product.id, { quantity });
  }

  // El "+" del carrito no pasa del stock que se vio al añadir el producto
  function updateQuantity(productId, quantity) {
    setItems((current) =>
      current.map((item) =>
        item.productId === productId && !(quantity > item.stock) ? { ...item, quantity } : item,
      ),
    );
  }

  function removeItem(productId) {
    setItems((current) => current.filter((item) => item.productId !== productId));
  }

  function closeAdded() {
    setAdded(null);
  }

  // Se vacía al terminar la compra
  function clearCart() {
    setItems([]);
  }

  const itemCount = items.reduce((total, item) => total + item.quantity, 0);

  return (
    <CartContext.Provider value={{ items, itemCount, addItem, updateQuantity, removeItem, clearCart, added, closeAdded }}>
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  return useContext(CartContext);
}
