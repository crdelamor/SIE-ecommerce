import { useCallback, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import Button from '../../components/Button.jsx';
import { Section } from '../../components/Container.jsx';
import LoginPage from './LoginPage.jsx';
import OrderDetailPage from './OrderDetailPage.jsx';
import OrdersPage from './OrdersPage.jsx';
import { logout } from './adminApi.js';
import './admin.css';

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
 * Mientras el grupo no añada subrutas admin/* en App.jsx, la vista se elige con la
 * URL: #/admin (lista de pedidos) o #/admin?pedido=ID (ficha de un pedido).
 */
export default function AdminPage() {
  const [searchParams] = useSearchParams();
  const orderId = searchParams.get('pedido');
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
      {orderId ? (
        <OrderDetailPage orderId={orderId} onUnauthorized={handleUnauthorized} />
      ) : (
        <OrdersPage onUnauthorized={handleUnauthorized} />
      )}
    </Section>
  );
}
