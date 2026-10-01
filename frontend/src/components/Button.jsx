import { Link } from 'react-router-dom';

/**
 * Botón con dos variantes: "primary" (por defecto) y "secondary".
 * Si recibe `to` se pinta como enlace de React Router; si no, como <button>.
 */
export default function Button({ variant = 'primary', to, className = '', children, ...props }) {
  const classes = `button${variant === 'secondary' ? ' button--secondary' : ''} ${className}`.trim();

  if (to) {
    return (
      <Link to={to} className={classes} {...props}>
        {children}
      </Link>
    );
  }
  return (
    <button type="button" className={classes} {...props}>
      {children}
    </button>
  );
}
