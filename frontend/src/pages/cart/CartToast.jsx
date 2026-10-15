import { useEffect, useState } from 'react';
import Button from '../../components/Button.jsx';
import { useCart } from '../../context/CartContext.jsx';
import './CartToast.css';

// Aviso "Añadido al carrito" bajo el icono de la cesta. Se cierra solo a los 4 segundos,
// pero no mientras el ratón está encima o el foco dentro (para llegar con el teclado a "Ver carrito").
export default function CartToast({ onViewCart }) {
  const { added, closeAdded } = useCart();
  const [hover, setHover] = useState(false);
  const [focus, setFocus] = useState(false);
  const paused = hover || focus;

  // cada vez que se añade algo (key nueva) o se quita la pausa vuelve a contar los 4 segundos
  useEffect(() => {
    if (!added || paused) return;
    const timer = setTimeout(closeAdded, 4000);
    return () => clearTimeout(timer);
  }, [added?.key, paused]);

  // si se cierra con el ratón o el foco dentro, que no se quede pausado el siguiente
  useEffect(() => {
    if (!added) {
      setHover(false);
      setFocus(false);
    }
  }, [added]);

  function handleViewCart() {
    closeAdded();
    onViewCart();
  }

  return (
    // role="status": el lector de pantalla lo anuncia sin quitar el foco de donde está
    <div role="status">
      {added && (
        <div
          className="cart-toast"
          key={added.key}
          onMouseEnter={() => setHover(true)}
          onMouseLeave={() => setHover(false)}
          onFocus={() => setFocus(true)}
          onBlur={(event) => setFocus(event.currentTarget.contains(event.relatedTarget))}
        >
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
