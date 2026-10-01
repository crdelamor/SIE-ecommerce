import { Link, NavLink } from 'react-router-dom';
import { Container } from './Container.jsx';
import { useCart } from '../context/CartContext.jsx';

const NAV = [
  { to: '/', label: 'Inicio', end: true },
  { to: '/catalogo', label: 'Catálogo' },
  { to: '/soporte', label: 'Soporte' },
];

/** Cabecera: logo, navegación e icono de carrito con contador. */
export default function Header() {
  const { items } = useCart();
  const count = items.reduce((total, item) => total + item.quantity, 0);

  return (
    <header className="header">
      <Container className="header__inner">
        <Link to="/" className="header__logo">
          Tienda
        </Link>

        <nav className="header__nav" aria-label="Principal">
          {NAV.map(({ to, label, end }) => (
            <NavLink key={to} to={to} end={end} className="link">
              {label}
            </NavLink>
          ))}
        </nav>

        <Link to="/carrito" className="header__cart" aria-label={`Carrito, ${count} artículos`}>
          <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
            <path d="M3 4h2l2.4 11h10.2L20 7H6.2" strokeLinecap="round" strokeLinejoin="round" />
            <circle cx="9" cy="19.5" r="1.5" />
            <circle cx="17" cy="19.5" r="1.5" />
          </svg>
          {count > 0 && <span className="header__cart-count" aria-hidden="true">{count}</span>}
        </Link>
      </Container>
    </header>
  );
}
