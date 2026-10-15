import { Link, Outlet } from 'react-router-dom';
import Banner from '../../components/Banner.jsx';
import { Container } from '../../components/Container.jsx';
import './checkout.css';

// Layout del checkout: sin menú ni pie con enlaces para que el cliente no se salga a mitad
// de la compra. Solo el logo y la cesta para volver al carrito. El aviso de prototipo se queda.
export default function CheckoutLayout() {
  return (
    <>
      <div className="site-top">
        <Banner />
        <header className="header">
          <Container className="header__inner">
            <Link to="/" className="header__logo">
              <img src="logo.svg" alt="" className="header__logo-icon" />
              <span>theblacklotus<span className="header__logo-accent">tcg</span></span>
            </Link>
            <Link to="/catalogo?isCartOpened=1" className="header__cart" aria-label="Volver al carrito">
              <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" aria-hidden="true">
                <path d="M3 4h2l2.4 11h10.2L20 7H6.2" strokeLinecap="round" strokeLinejoin="round" />
                <circle cx="9" cy="19.5" r="1.5" />
                <circle cx="17" cy="19.5" r="1.5" />
              </svg>
            </Link>
          </Container>
        </header>
      </div>

      <main>
        <Outlet />
      </main>
    </>
  );
}
