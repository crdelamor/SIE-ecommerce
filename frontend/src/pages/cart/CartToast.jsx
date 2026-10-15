import { useEffect } from 'react';
import Button from '../../components/Button.jsx';
import { useCart } from '../../context/CartContext.jsx';
import './CartToast.css';

// Aviso "Añadido al carrito" bajo el icono de la cesta. Se cierra solo a los 2 segundos.
export default function CartToast({ onViewCart }) {
  const { added, closeAdded } = useCart();

  // cada vez que se añade algo (key nueva) vuelve a contar los 2 segundos
  useEffect(() => {
    if (!added) return;
    const timer = setTimeout(closeAdded, 2000);
    return () => clearTimeout(timer);
  }, [added?.key]);

  function handleViewCart() {
    closeAdded();
    onViewCart();
  }

  return (
    // role="status": el lector de pantalla lo anuncia sin quitar el foco de donde está
    <div role="status">
      {added && (
        <div className="cart-toast" key={added.key}>
          <p className="cart-toast__title">{added.full ? 'No quedan más unidades' : 'Añadido al carrito'}</p>
          <p className="cart-toast__name">
            {added.full
              ? `${added.name}: ${added.stock === 1 ? 'solo queda 1 y ya está' : `solo quedan ${added.stock} y ya están`} en tu carrito.`
              : added.name}
          </p>
          <Button onClick={handleViewCart}>Ver carrito</Button>
        </div>
      )}
    </div>
  );
}
