import { useEffect, useState } from 'react';

/**
 * Carga datos de la API al montar la pantalla y cada vez que cambian `deps`.
 * `load` recibe una señal para cancelar la petición si se sale de la pantalla antes de que llegue.
 *
 *   const { status, data, error } = useAsync((signal) => getProduct(id, signal), [id]);
 *
 * `status` es 'loading', 'ready' o 'error'.
 */
export default function useAsync(load, deps) {
  const [state, setState] = useState({ status: 'loading', data: null, error: null });

  useEffect(() => {
    const controller = new AbortController();
    setState({ status: 'loading', data: null, error: null });

    load(controller.signal)
      .then((data) => setState({ status: 'ready', data, error: null }))
      .catch((error) => {
        // Una petición cancelada no es un error: la pantalla ya no la espera
        if (error.name !== 'AbortError') setState({ status: 'error', data: null, error });
      });

    return () => controller.abort();
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, deps);

  return state;
}
