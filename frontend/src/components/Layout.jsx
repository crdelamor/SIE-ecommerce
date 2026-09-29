import { Link, Outlet } from 'react-router-dom';

/**
 * Layout base (fase 0). B lo sustituirá por sus componentes de cabecera y pie.
 */
export default function Layout() {
  return (
    <>
      <header>
        <nav>
          <Link to="/">Inicio</Link> · <Link to="/catalogo">Catálogo</Link> ·{' '}
          <Link to="/carrito">Carrito</Link> · <Link to="/soporte">Soporte</Link> ·{' '}
          <Link to="/admin">Administración</Link>
        </nav>
      </header>

      <main>
        <Outlet />
      </main>

      <footer>
        <p>Prototipo académico – sin actividad comercial real</p>
      </footer>
    </>
  );
}
