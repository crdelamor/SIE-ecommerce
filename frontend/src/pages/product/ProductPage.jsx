import { useEffect } from 'react';
import { Link, useParams } from 'react-router-dom';
import Button from '../../components/Button.jsx';
import { conditionLabel, formatPrice } from '../../components/productFormat.js';
import { useCart } from '../../context/CartContext.jsx';
import useAsync from '../../hooks/useAsync.js';
import { getProduct } from '../catalog/catalogApi.js';
import './product.css';

const PLACEHOLDER = `${import.meta.env.BASE_URL}placeholder.png`;

// Si la URL de la imagen falla, se muestra el placeholder (una sola vez)
function showPlaceholder(event) {
  if (!event.currentTarget.src.endsWith('placeholder.png')) {
    event.currentTarget.src = PLACEHOLDER;
  }
}

function stockText(stock) {
  if (stock === 0) return 'Agotado';
  if (stock <= 5) return stock === 1 ? 'Última unidad' : `Quedan ${stock} unidades`;
  return 'En stock';
}

/**
 * Ficha de producto: imagen, datos de la carta, precio con IVA, stock y botón "Añadir al carrito".
 * Las demás versiones de la misma carta (nueva, usada, foil...) se muestran para elegir entre ellas,
 * cada una con su propia imagen. Al pedir la ficha, la API registra product.viewed.
 */
export default function ProductPage() {
  const { id } = useParams();
  const { addItem } = useCart();

  const { status, data: product, error } = useAsync((signal) => getProduct(id, signal), [id]);

  // El título de la pestaña lleva el nombre del producto mientras se ve su ficha
  useEffect(() => {
    if (!product) return undefined;
    const previous = document.title;
    document.title = `${product.name} · theblacklotustcg`;
    return () => {
      document.title = previous;
    };
  }, [product]);

  if (status === 'loading') return <p role="status">Cargando producto…</p>;

  if (status === 'error') {
    return (
      <div role="alert" className="catalog-message">
        <p>
          {error.status === 404 || error.status === 400
            ? 'Este producto no existe o ya no está disponible.'
            : `No se ha podido cargar el producto. ${error.message}`}
        </p>
        <Button variant="secondary" to="/catalogo">
          Ver el catálogo
        </Button>
      </div>
    );
  }

  const isCard = product.product_type === 'single';
  const soldOut = product.stock === 0;

  // Añade el producto al carrito y abre el panel del carrito (el mismo ?isCartOpened=1 que usa la cabecera)
  function handleAdd() {
    addItem(product); // el aviso "Añadido al carrito" de la cabecera deja abrir el carrito
  }

  return (
    <>
      <nav aria-label="Migas de pan" className="breadcrumb">
        <Link to="/catalogo" className="link">Catálogo</Link>
        <span aria-hidden="true"> / </span>
        <Link to={`/catalogo?juego=${encodeURIComponent(product.category.slug)}`} className="link">
          {product.category.name}
        </Link>
      </nav>

      <div className="product-detail">
        <div className="product-detail__media">
          <img src={product.image_url || PLACEHOLDER} alt={product.name} onError={showPlaceholder} />
        </div>

        <div className="product-detail__info">
          <h1>
            {product.name}
            {product.is_foil && <span className="badge">Foil</span>}
          </h1>

          <p className="product-detail__price">
            {formatPrice(product.price_with_tax)} <span className="product-card__tax">IVA incluido</span>
          </p>
          <p className={soldOut ? 'product-detail__stock product-detail__stock--out' : 'product-detail__stock'}>
            {stockText(product.stock)}
          </p>

          <Button onClick={handleAdd} disabled={soldOut}>
            {soldOut ? 'Agotado' : 'Añadir al carrito'}
          </Button>

          <dl className="product-detail__facts">
            <dt>Juego</dt>
            <dd>{product.category.name}</dd>
            {product.set_name && (
              <>
                <dt>Edición</dt>
                <dd>{product.set_name}</dd>
              </>
            )}
            <dt>{isCard ? 'Estado' : 'Formato'}</dt>
            <dd>{conditionLabel(product)}</dd>
            {product.language && (
              <>
                <dt>Idioma</dt>
                <dd>{product.language}</dd>
              </>
            )}
            {product.illustrator && (
              <>
                <dt>Ilustrador</dt>
                <dd>{product.illustrator}</dd>
              </>
            )}
            {isCard && (
              <>
                <dt>Acabado</dt>
                <dd>{product.is_foil ? 'Foil' : 'Normal'}</dd>
              </>
            )}
          </dl>

          {product.description && <p>{product.description}</p>}
        </div>
      </div>

      {product.versions.length > 1 && (
        <section aria-labelledby="versions-title" className="versions">
          <h2 id="versions-title">Versiones de esta carta</h2>
          <ul className="versions__list">
            {product.versions.map((version) => {
              const label = [
                conditionLabel({ product_type: 'single', card_condition: version.card_condition }),
                version.language,
                version.is_foil ? 'Foil' : null,
              ]
                .filter(Boolean)
                .join(' · ');

              return (
                <li key={version.id}>
                  <Link
                    to={`/producto/${version.id}`}
                    className="versions__item"
                    aria-current={version.id === product.id ? 'true' : undefined}
                  >
                    <img
                      src={version.image_url || PLACEHOLDER}
                      alt=""
                      className="versions__image"
                      onError={showPlaceholder}
                    />
                    <span className="versions__text">
                      <span className="versions__label">{label}</span>
                      <span>{formatPrice(version.price_with_tax)}</span>
                      <span className="text-muted">{stockText(version.stock)}</span>
                    </span>
                  </Link>
                </li>
              );
            })}
          </ul>
        </section>
      )}
    </>
  );
}
