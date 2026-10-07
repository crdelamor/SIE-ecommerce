import { useEffect, useRef } from 'react';
import { useCart } from '../../context/CartContext.jsx';
import Button from '../../components/Button.jsx';
import { CloseIcon } from '../../components/Icons.jsx';
import './CartPanel.css';

/**
 * Panel lateral del carrito. Se abre cuando la URL lleva ?isCartOpened=1 (lo controla Layout).
 * Se cierra con el botón, con Escape o haciendo clic fuera del panel.
 */
export default function CartPanel({ open, onClose }) {
  const { items, itemCount, updateQuantity, removeItem } = useCart();
  const closeButtonRef = useRef(null);

  // Al abrir: foco en el botón de cerrar y la página de detrás sin scroll
  useEffect(() => {
    if (!open) return undefined;
    closeButtonRef.current?.focus();
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';
    return () => {
      document.body.style.overflow = previousOverflow;
    };
  }, [open]);

  useEffect(() => {
    if (!open) return undefined;
    function handleKeyDown(event) {
      if (event.key === 'Escape') onClose();
    }
    document.addEventListener('keydown', handleKeyDown);
    return () => document.removeEventListener('keydown', handleKeyDown);
  }, [open, onClose]);

  return (
    <>
      <div className={`cart-overlay${open ? ' is-open' : ''}`} onClick={onClose} aria-hidden="true" />

      <aside
        className={`cart-drawer${open ? ' is-open' : ''}`}
        role="dialog"
        aria-modal="true"
        aria-labelledby="cart-title"
        inert={!open}
      >
        <div className="cart-drawer__header">
          <h2 id="cart-title" className="cart-drawer__title">
            {items.length === 0 ? (
              'Carrito vacío'
            ) : (
              <>
                Tu carrito
                <span className="cart-drawer__count">
                  {itemCount === 1 ? '1 producto' : `${itemCount} productos`}
                </span>
              </>
            )}
          </h2>
          <button ref={closeButtonRef} type="button" className="cart-drawer__close" onClick={onClose} aria-label="Cerrar carrito">
            <CloseIcon />
          </button>
        </div>

        {items.length === 0 ? (
          <div className="cart-drawer__body cart-empty">
            <Button variant="secondary" to="/catalogo">
              Ver el catálogo
            </Button>
          </div>
        ) : (
          <>
            <ul className="cart-drawer__body cart-list">
              {items.map((item, index) => (
                <li key={item.productId} className="cart-item" style={{ '--index': index }}>
                  {item.imageUrl ? (
                    <img className="cart-item__image" src={item.imageUrl} alt="" />
                  ) : (
                    <span className="cart-item__image" aria-hidden="true">
                      {item.name.charAt(0)}
                    </span>
                  )}

                  <div>
                    <p className="cart-item__name">{item.name}</p>
                    <div className="cart-item__actions">
                      <div className="quantity" role="group" aria-label={`Cantidad de ${item.name}`}>
                        <button
                          type="button"
                          className="quantity__button"
                          onClick={() => updateQuantity(item.productId, item.quantity - 1)}
                          disabled={item.quantity <= 1}
                          aria-label="Quitar una unidad"
                        >
                          −
                        </button>
                        <span className="quantity__value" aria-live="polite">
                          {item.quantity}
                        </span>
                        <button
                          type="button"
                          className="quantity__button"
                          onClick={() => updateQuantity(item.productId, item.quantity + 1)}
                          aria-label="Añadir una unidad"
                        >
                          +
                        </button>
                      </div>

                      <button
                        type="button"
                        className="cart-item__remove link"
                        onClick={() => removeItem(item.productId)}
                        aria-label={`Eliminar ${item.name} del carrito`}
                      >
                        Eliminar
                      </button>
                    </div>
                  </div>
                </li>
              ))}
            </ul>

            <div className="cart-drawer__footer">
              <p className="cart-drawer__note">Los impuestos y el envío se calculan en el checkout.</p>
              <Button to="/checkout">Tramitar pedido</Button>
              <Button variant="secondary" onClick={onClose}>
                Seguir comprando
              </Button>
            </div>
          </>
        )}
      </aside>
    </>
  );
}
