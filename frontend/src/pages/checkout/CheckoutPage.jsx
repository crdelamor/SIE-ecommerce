import { useEffect, useRef } from 'react';
import { useCart } from '../../context/CartContext.jsx';
import { trackEvent } from '../../lib/events.js';

export default function CheckoutPage() {
  const { itemCount } = useCart();
  const eventSent = useRef(false);

  // checkout.started al entrar en el checkout con productos (el ref evita enviarlo dos veces en desarrollo)
  useEffect(() => {
    if (!eventSent.current && itemCount > 0) {
      eventSent.current = true;
      trackEvent('checkout.started', null, null, { item_count: itemCount });
    }
  }, [itemCount]);

  return <h1>Checkout</h1>;
}
