import { useState } from 'react';
import Button from '../../components/Button.jsx';
import { Section } from '../../components/Container.jsx';
import { login } from './adminApi.js';
import './admin.css';

/**
 * Formulario de acceso del administrador. La comprobación real la hace la API;
 * aquí solo se evita enviar campos vacíos.
 */
export default function LoginPage({ onLogin, notice = '' }) {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [sending, setSending] = useState(false);

  async function handleSubmit(event) {
    event.preventDefault();
    setError('');
    setSending(true);
    try {
      const user = await login(email.trim(), password);
      onLogin(user);
    } catch (err) {
      setError(err.message);
      setPassword('');
    } finally {
      setSending(false);
    }
  }

  return (
    <Section>
      <div className="admin-login">
        <h1>Acceso de administración</h1>
        <p className="text-muted">Solo para el personal de la tienda.</p>
        {notice && (
          <p className="admin-notice" role="status">
            {notice}
          </p>
        )}

        <form className="admin-form" onSubmit={handleSubmit}>
          <div className="admin-form__field">
            <label htmlFor="admin-email" className="admin-form__label">
              Email
            </label>
            <input
              id="admin-email"
              className="admin-form__input"
              type="email"
              autoComplete="username"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              aria-describedby={error ? 'admin-login-error' : undefined}
            />
          </div>

          <div className="admin-form__field">
            <label htmlFor="admin-password" className="admin-form__label">
              Contraseña
            </label>
            <input
              id="admin-password"
              className="admin-form__input"
              type="password"
              autoComplete="current-password"
              required
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              aria-describedby={error ? 'admin-login-error' : undefined}
            />
          </div>

          {/* role="alert": el lector de pantalla lee el error en cuanto aparece */}
          {error && (
            <p id="admin-login-error" className="admin-form__error" role="alert">
              {error}
            </p>
          )}

          <Button type="submit" disabled={sending}>
            {sending ? 'Entrando…' : 'Entrar'}
          </Button>
        </form>
      </div>
    </Section>
  );
}
