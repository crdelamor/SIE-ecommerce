import { useCallback, useState } from 'react';
import { Link, useSearchParams } from 'react-router-dom';
import Button from '../../components/Button.jsx';
import { Section } from '../../components/Container.jsx';
import EventsPage from './EventsPage.jsx';
import LoginPage from './LoginPage.jsx';
import OrderDetailPage from './OrderDetailPage.jsx';
import OrdersPage from './OrdersPage.jsx';
import SupportTicketsPage from './SupportTicketsPage.jsx';
import { logout } from './adminApi.js';
import './admin.css';

// Secciones del panel: valor de ?seccion= (vacío = pedidos) y texto del menú
// Texto del enlace de vuelta en la ficha del pedido, según la sección desde la que se abrió
const BACK_LABELS = { '': 'Volver a pedidos', incidencias: 'Volver a incidencias', eventos: 'Volver a eventos' };

const SECTIONS = [
  ['', 'Pedidos'],
  ['incidencias', 'Incidencias'],
  ['eventos', 'Eventos'],
];

// Solo recuerda qué administrador ha entrado para mostrarlo al recargar la página.
// No da acceso a nada: la API comprueba la cookie de sesión en cada petición y,
// si responde 401, se vuelve al login.
const STORAGE_KEY = 'sie-admin-user';

function loadUser() {
  try {
    return JSON.parse(sessionStorage.getItem(STORAGE_KEY));
  } catch {
    return null;
  }
}

function saveUser(user) {
  try {
    if (user) sessionStorage.setItem(STORAGE_KEY, JSON.stringify(user));
    else sessionStorage.removeItem(STORAGE_KEY);
  } catch {
    // Sin sessionStorage (modo privado estricto) solo se pierde el recordatorio al recargar
  }
}

/**
 * Entrada a la administración: muestra el login o el panel según haya sesión.
 * Sin tocar App.jsx, la vista se elige con la URL:
 *   #/admin                         lista de pedidos
 *   #/admin?seccion=incidencias     incidencias de soporte
 *   #/admin?seccion=eventos         visor de eventos
 *   ...&pedido=ID                   ficha de un pedido (vuelve a la sección de la que vino)
 */
export default function AdminPage() {
  const [searchParams] = useSearchParams();
  const orderId = searchParams.get('pedido');
  const knownSection = SECTIONS.some(([value]) => value && value === searchParams.get('seccion'));
  const section = knownSection ? searchParams.get('seccion') : '';
  const [user, setUser] = useState(loadUser);
  const [sessionExpired, setSessionExpired] = useState(false);

  function handleLogin(loggedUser) {
    saveUser(loggedUser);
    setUser(loggedUser);
    setSessionExpired(false);
  }

  // La API respondió 401: la sesión del servidor ya no existe (caducó o se reinició PHP)
  const handleUnauthorized = useCallback(() => {
    saveUser(null);
    setUser(null);
    setSessionExpired(true);
  }, []);

  async function handleLogout() {
    try {
      await logout();
    } catch {
      // Aunque falle la petición, se sale de la administración en esta pantalla
    }
    saveUser(null);
    setUser(null);
  }

  if (!user) {
    return (
      <LoginPage
        onLogin={handleLogin}
        notice={sessionExpired ? 'Tu sesión ha caducado. Vuelve a iniciar sesión.' : ''}
      />
    );
  }

  return (
    <Section>
      <div className="admin-bar">
        <h1>Administración</h1>
        <div className="admin-bar__user">
          <p className="text-muted">
            Sesión iniciada como <strong>{user.email}</strong>
          </p>
          <Button variant="secondary" onClick={handleLogout}>
            Cerrar sesión
          </Button>
        </div>
      </div>
      {/* Al cambiar de sección se quitan los filtros: cada lista tiene los suyos */}
      <nav className="admin-nav" aria-label="Secciones de la administración">
        {SECTIONS.map(([value, label]) => (
          <Link
            key={label}
            to={value ? `?seccion=${value}` : '?'}
            className="admin-nav__link"
            aria-current={section === value ? 'page' : undefined}
          >
            {label}
          </Link>
        ))}
      </nav>

      {orderId ? (
        <OrderDetailPage
          orderId={orderId}
          onUnauthorized={handleUnauthorized}
          backLabel={BACK_LABELS[section]}
        />
      ) : section === 'incidencias' ? (
        <SupportTicketsPage onUnauthorized={handleUnauthorized} />
      ) : section === 'eventos' ? (
        <EventsPage onUnauthorized={handleUnauthorized} />
      ) : (
        <OrdersPage onUnauthorized={handleUnauthorized} />
      )}
    </Section>
  );
}
