import { Link } from 'react-router-dom';
import Button from '../../components/Button.jsx';
import { Section } from '../../components/Container.jsx';
import ProductCard from '../../components/ProductCard.jsx';
import useAsync from '../../hooks/useAsync.js';
import useReveal from '../../hooks/useReveal.js';
import { getCategories, getProducts } from '../catalog/catalogApi.js';
import './home.css';

// Explicación de cómo se clasifican las cartas (coincide con los estados del catálogo)
const CONDITIONS = [
  ['Nueva (NM)', 'Como recién sacada del sobre, sin marcas visibles.'],
  ['Usada (LP)', 'Ligeros signos de uso, como pequeños roces en las esquinas.'],
  ['Muy usada (MP)', 'Desgaste visible, pero perfectamente jugable.'],
  ['Foil', 'Acabado brillante. Cada versión es un producto distinto, con su propio precio y stock.'],
];

// Un producto de cada juego, para que los destacados no sean todos del mismo
function onePerGame(products) {
  const seen = new Set();
  return products.filter((product) => {
    if (seen.has(product.category.slug)) return false;
    seen.add(product.category.slug);
    return true;
  });
}

// Acceso a un juego: enlaza al catálogo ya filtrado. Aparece con la misma animación que las tarjetas.
function GameTile({ category, index }) {
  const [ref, visible] = useReveal();

  return (
    <Link
      ref={ref}
      to={`/catalogo?juego=${encodeURIComponent(category.slug)}`}
      className={`game-tile reveal${visible ? ' is-visible' : ''}`}
      style={{ '--reveal-delay': `calc(var(--reveal-stagger) * ${index})` }}
    >
      <span className="game-tile__name">{category.name}</span>
      <span className="game-tile__cta">Ver cartas y sobres →</span>
    </Link>
  );
}

/** Portada: presentación de la tienda, acceso a cada juego y productos destacados. */
export default function HomePage() {
  const categories = useAsync((signal) => getCategories(signal), []);
  const products = useAsync((signal) => getProducts('', signal), []);

  return (
    <>
      <div className="hero">
        <h1 className="text-hero">theblacklotustcg</h1>
        <p className="hero__lead">
          Cartas sueltas y sobres de Magic: The Gathering, Riftbound, Pokémon TCG y Yu-Gi-Oh!, con el estado, el
          idioma y el acabado de cada carta bien indicados.
        </p>
        <div className="hero__actions">
          <Button to="/catalogo">Ver el catálogo</Button>
          <Button variant="secondary" to="/soporte">
            ¿Dudas con tu pedido?
          </Button>
        </div>
      </div>

      <Section title="Elige tu juego">
        {categories.status === 'loading' && <p role="status">Cargando juegos…</p>}
        {categories.status === 'error' && (
          <p role="alert">No se han podido cargar los juegos. {categories.error.message}</p>
        )}
        {categories.status === 'ready' && (
          <div className="game-tiles">
            {categories.data.map((category, index) => (
              <GameTile key={category.id} category={category} index={index} />
            ))}
          </div>
        )}
      </Section>

      <Section title="Productos destacados">
        {products.status === 'loading' && <p role="status">Cargando productos…</p>}
        {products.status === 'error' && (
          <p role="alert">No se han podido cargar los productos. {products.error.message}</p>
        )}
        {products.status === 'ready' && (
          <div className="grid">
            {onePerGame(products.data).map((product, index) => (
              <ProductCard key={product.id} product={product} index={index} />
            ))}
          </div>
        )}
      </Section>

      <Section title="Cómo clasificamos las cartas">
        <dl className="conditions">
          {CONDITIONS.map(([name, description]) => (
            <div key={name} className="conditions__item">
              <dt>{name}</dt>
              <dd>{description}</dd>
            </div>
          ))}
        </dl>
      </Section>
    </>
  );
}
