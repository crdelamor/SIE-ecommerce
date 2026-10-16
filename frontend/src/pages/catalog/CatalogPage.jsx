import { Link, useSearchParams } from 'react-router-dom';
import Button from '../../components/Button.jsx';
import ProductCard from '../../components/ProductCard.jsx';
import useAsync from '../../hooks/useAsync.js';
import { getCategories, getProducts } from './catalogApi.js';
import './catalog.css';

/**
 * Catálogo con filtro por juego (categoría). El juego elegido va en la URL (#/catalogo?juego=magic),
 * así se puede enlazar desde la página principal y el botón "atrás" funciona.
 * El filtrado lo hace la API; aquí solo se muestran los productos que devuelve.
 */
export default function CatalogPage() {
  const [searchParams] = useSearchParams();
  const game = searchParams.get('juego') ?? '';

  const categories = useAsync((signal) => getCategories(signal), []);
  const products = useAsync((signal) => getProducts(game, signal), [game]);

  const gameName = categories.data?.find((category) => category.slug === game)?.name;

  return (
    <>
      <h1>{gameName ?? 'Catálogo'}</h1>

      <nav className="filters" aria-label="Filtrar por juego">
        <Link to="/catalogo" className="filters__item" aria-current={game === '' ? 'true' : undefined}>
          Todos
        </Link>
        {categories.data?.map((category) => (
          <Link
            key={category.id}
            to={`/catalogo?juego=${encodeURIComponent(category.slug)}`}
            className="filters__item"
            aria-current={game === category.slug ? 'true' : undefined}
          >
            {category.name}
          </Link>
        ))}
      </nav>

      {products.status === 'loading' && <p role="status">Cargando productos…</p>}

      {products.status === 'error' && (
        <div role="alert" className="catalog-message">
          <p>
            {products.error.status === 404
              ? 'Ese juego no existe en nuestro catálogo.'
              : `No se han podido cargar los productos. ${products.error.message}`}
          </p>
          <Button variant="secondary" to="/catalogo">
            Ver todo el catálogo
          </Button>
        </div>
      )}

      {products.status === 'ready' && products.data.length === 0 && (
        <p className="catalog-message">Todavía no hay productos en este juego.</p>
      )}

      {products.status === 'ready' && products.data.length > 0 && (
        <>
          <p className="text-muted" role="status">
            {products.data.length === 1 ? '1 producto' : `${products.data.length} productos`}
          </p>
          <div className="grid">
            {products.data.map((product, index) => (
              <ProductCard key={product.id} product={product} index={index} />
            ))}
          </div>
        </>
      )}
    </>
  );
}
