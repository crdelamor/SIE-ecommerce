import { useEffect, useRef } from 'react';
import { useLocation } from 'react-router-dom';
import { trackEvent } from '../lib/events.js';

// La confirmación lleva el código del pedido en la ruta: se manda sin él
function pagePath(pathname) {
  return pathname.startsWith('/pedido/') ? '/pedido' : pathname;
}

/**
 * Registra page.viewed con la ruta de cada página de la tienda (sin query) al cambiar de página.
 * La administración no cuenta como visita a la tienda.
 */
export default function usePageViews() {
  const { pathname } = useLocation();
  const last = useRef(null);

  useEffect(() => {
    const path = pagePath(pathname);
    // StrictMode repite el efecto en desarrollo: una sola vez por página
    if (path === last.current) return;
    last.current = path;
    if (path.startsWith('/admin')) return;
    trackEvent('page.viewed', null, null, { path });
  }, [pathname]);
}
