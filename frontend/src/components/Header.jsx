import { Link, NavLink, useLocation, useNavigate } from 'react-router-dom';
import { Container } from './Container.jsx';
import { useCart } from '../context/CartContext.jsx';
import CartToast from '../pages/cart/CartToast.jsx';

const NAV = [
  { to: '/', label: 'Inicio', end: true },
  { to: '/catalogo', label: 'Catálogo' },
  { to: '/soporte', label: 'Soporte' },
];

/**
 * Cabecera: logo, navegación e icono de carrito con contador.
 * El icono abre el panel del carrito añadiendo ?isCartOpened=1 a la página actual.
 */
export default function Header({ cartButtonRef }) {
  const { itemCount } = useCart();
  const location = useLocation();
  const navigate = useNavigate();

  const cartParams = new URLSearchParams(location.search);
  cartParams.set('isCartOpened', '1');
  const cartLink = { pathname: location.pathname, search: `?${cartParams}` };

  return (
    <header className="header">
      <Container className="header__inner">
        <Link to="/" className="header__logo">
          <img src="logo.svg" alt="" className="header__logo-icon" />
          <span>The Black Lotus <span className="header__logo-accent">TCG</span></span>
        </Link>

        <nav className="header__nav" aria-label="Principal">
          {NAV.map(({ to, label, end }) => (
            <NavLink key={to} to={to} end={end} className="link">
              {label}
            </NavLink>
          ))}
        </nav>

        <div className="header__cart-wrap">
          <Link
            ref={cartButtonRef}
            to={cartLink}
            className="header__cart"
            aria-label={`Carrito, ${itemCount} ${itemCount === 1 ? 'artículo' : 'artículos'}`}
          >
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
              <path d="M3 4h2l2.4 11h10.2L20 7H6.2" strokeLinecap="round" strokeLinejoin="round" />
              <circle cx="9" cy="19.5" r="1.5" />
              <circle cx="17" cy="19.5" r="1.5" />
            </svg>
            {itemCount > 0 && <span className="header__cart-count" aria-hidden="true">{itemCount}</span>}
          </Link>
          <CartToast onViewCart={() => navigate(cartLink)} />
        </div>
      </Container>
    </header>
  );
}
