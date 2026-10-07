import { Link } from 'react-router-dom';
import { Container } from './Container.jsx';

/** Pie de página. Repite el aviso de prototipo y enlaza a la administración. */
export default function Footer() {
  return (
    <footer className="footer">
      <Container className="footer__inner">
        <p>La Bóveda TCG · Prototipo académico - sin actividad comercial real</p>
        <nav className="footer__links" aria-label="Secundaria">
          <Link to="/soporte" className="link">Soporte</Link>
          <Link to="/admin" className="link">Administración</Link>
        </nav>
      </Container>
    </footer>
  );
}
