import { Outlet } from 'react-router-dom';
import Banner from './Banner.jsx';
import Header from './Header.jsx';
import Footer from './Footer.jsx';

/** Layout común: banner de prototipo + cabecera fijos arriba, contenido y pie. */
export default function Layout() {
  return (
    <>
      <div className="site-top">
        <Banner />
        <Header />
      </div>

      <main>
        <Outlet />
      </main>

      <Footer />
    </>
  );
}
