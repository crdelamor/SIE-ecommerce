import { Link } from 'react-router-dom';
import useReveal from '../hooks/useReveal.js';
import { useCart } from '../context/CartContext.jsx';
import Button from './Button.jsx';

const PLACEHOLDER = `${import.meta.env.BASE_URL}placeholder.svg`;

// Solo formato de presentación; el importe ya llega calculado de la API
const formatPrice = (value) =>
  new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);

/**
 * Tarjeta de producto. `index` escalona la animación de entrada dentro de una lista.
 * Muestra `price_with_tax` (precio con IVA calculado por la API), el mismo que verá el cliente en el carrito.
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
      {/* Enlace de la imagen oculto a lectores de pantalla y teclado: el título ya enlaza al producto */}
      <Link to={`/producto/${product.id}`} className="product-card__media" aria-hidden="true" tabIndex={-1}>
        <img
          src={product.image_url || PLACEHOLDER}
          alt={product.name}
          loading="lazy"
          onError={(event) => {
            // Si la URL de la imagen falla, se muestra el placeholder (una sola vez)
            if (!event.currentTarget.src.endsWith('placeholder.svg')) {
              event.currentTarget.src = PLACEHOLDER;
            }
          }}
        />
      </Link>
      <div className="product-card__body">
        <h3 className="product-card__name">
          <Link to={`/producto/${product.id}`} className="link">
            {product.name}
          </Link>
        </h3>
        <p className="product-card__price">{formatPrice(product.price_with_tax)}</p>
        <Button onClick={() => addItem(product)}>Añadir al carrito</Button>
      </div>
    </article>
  );
}
