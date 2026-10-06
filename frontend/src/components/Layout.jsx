import { useEffect, useRef } from 'react';
import { Outlet, useSearchParams } from 'react-router-dom';
import Banner from './Banner.jsx';
import Header from './Header.jsx';
import Footer from './Footer.jsx';
import { Container } from './Container.jsx';
import CartPanel from '../pages/cart/CartPanel.jsx';

/** Layout común: banner de prototipo + cabecera fijos arriba, contenido, pie y panel del carrito. */
export default function Layout() {
  const [searchParams, setSearchParams] = useSearchParams();
  const cartButtonRef = useRef(null);
  const returnFocusToCart = useRef(false);

  // El carrito se abre con ?isCartOpened=1 en la URL (por ejemplo #/catalogo?isCartOpened=1)
  const isCartOpened = searchParams.get('isCartOpened') === '1';

  function closeCart() {
    setSearchParams((params) => {
      params.delete('isCartOpened');
      return params;
    });
    returnFocusToCart.current = true;
  }

  // Al cerrar el carrito, el foco vuelve al botón que lo abrió (cuando la página ya no está inert)
  useEffect(() => {
    if (!isCartOpened && returnFocusToCart.current) {
      returnFocusToCart.current = false;
      cartButtonRef.current?.focus();
    }
  }, [isCartOpened]);

  return (
    <>
      {/* Con el carrito abierto, la página de detrás queda inactiva (inert) para el teclado */}
      <div className="site" inert={isCartOpened}>
        <div className="site-top">
          <Banner />
          <Header cartButtonRef={cartButtonRef} />
        </div>

        <main className="site-main">
          <Container>
            <Outlet />
          </Container>
        </main>

        <Footer />
      </div>

      <CartPanel open={isCartOpened} onClose={closeCart} />
    </>
  );
}
