import { useEffect, useRef, useState } from 'react';

/**
 * Animación de entrada al hacer scroll.
 * Devuelve [ref, visible]: se pone el ref en el elemento y la clase "is-visible"
 * cuando `visible` es true (el CSS de .reveal hace el fade + desplazamiento).
 * Se observa una sola vez: tras aparecer, el elemento ya no vuelve a ocultarse.
 */
export default function useReveal() {
  const ref = useRef(null);
  const [visible, setVisible] = useState(false);

  useEffect(() => {
    const element = ref.current;
    if (!element) return undefined;

    // Navegadores sin IntersectionObserver: se muestra directamente
    if (!('IntersectionObserver' in window)) {
      setVisible(true);
      return undefined;
    }

    const observer = new IntersectionObserver(
      ([entry]) => {
        if (entry.isIntersecting) {
          setVisible(true);
          observer.disconnect();
        }
      },
      { threshold: 0.15 },
    );
    observer.observe(element);
    return () => observer.disconnect();
  }, []);

  return [ref, visible];
}
