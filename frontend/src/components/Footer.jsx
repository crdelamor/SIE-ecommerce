import { Link } from 'react-router-dom';
import { Container } from './Container.jsx';

/** Pie de página. Repite el aviso de prototipo. La administración no se enlaza: se entra directamente por #/admin. */
export default function Footer() {
  return (
    <footer className="footer">
      <Container className="footer__inner">
        <p>theblacklotustcg · Prototipo académico - sin actividad comercial real</p>
        <nav className="footer__links" aria-label="Secundaria">
          <Link to="/soporte" className="link">Soporte</Link>
        </nav>
      </Container>
    </footer>
  );
}
