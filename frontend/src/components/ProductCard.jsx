import { Link } from 'react-router-dom';
import useReveal from '../hooks/useReveal.js';
import { useCart } from '../context/CartContext.jsx';
import Button from './Button.jsx';

// Solo formato de presentación; el precio ya llega calculado de la API
const formatPrice = (value) =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);

/**
 * Tarjeta de producto. `index` escalona la animación de entrada dentro de una lista.
 * El botón llama a addItem del carrito de A (useCart).
 */
export default function ProductCard({ product, index = 0 }) {
  const [ref, visible] = useReveal();
  const { addItem } = useCart();

  return (
    <article
      ref={ref}
      className={`product-card reveal${visible ? ' is-visible' : ''}`}
      style={{ '--reveal-delay': `calc(var(--reveal-stagger) * ${index})` }}
    >
      <Link to={`/producto/${product.id}`} className="product-card__media">
        <img src={product.image_url} alt={product.name} loading="lazy" />
      </Link>
      <div className="product-card__body">
        <h3 className="product-card__name">
          <Link to={`/producto/${product.id}`} className="link">
            {product.name}
          </Link>
        </h3>
        <p className="product-card__price">{formatPrice(product.price)}</p>
        <Button onClick={() => addItem(product)}>Añadir al carrito</Button>
      </div>
    </article>
  );
}
