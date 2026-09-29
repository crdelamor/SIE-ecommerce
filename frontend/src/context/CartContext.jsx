import { createContext, useContext, useState } from 'react';

/**
 * Esqueleto del carrito (fase 0). Para conectar el botón "Añadir al carrito":
 *   const { addItem } = useCart();
 *   addItem(producto);
 */

const CartContext = createContext(null);

export function CartProvider({ children }) {
  const [items, setItems] = useState([]);

  function addItem(product) {
    setItems((current) => [...current, { productId: product.id, quantity: 1 }]);
  }

  return <CartContext.Provider value={{ items, addItem }}>{children}</CartContext.Provider>;
}

export function useCart() {
  return useContext(CartContext);
}
