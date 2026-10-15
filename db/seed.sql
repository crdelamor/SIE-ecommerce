-- ============================================================
-- seed.sql - Datos de prueba (TODOS FICTICIOS)
-- Asignatura: Soluciones Informáticas para la Empresa (SIE)
-- Práctica 1 - Tienda de cartas coleccionables
-- Autor: José Gómez Reinaldos (alumno)
--
-- Importar DESPUÉS de schema.sql (en local y en el hosting).
--
-- Reglas usadas para los totales de los pedidos de ejemplo:
--   subtotal  = suma de precio sin IVA x cantidad
--   descuento = 10 % del subtotal con el cupón BIENVENIDA10
--   envío     = 4,95 EUR, gratis si el subtotal es de 50 EUR o más
--   IVA       = 21 % sobre (subtotal - descuento); el envío no lleva IVA
--   total     = subtotal - descuento + envío + IVA
--
-- Usuario administrador de prueba: ver README (la contraseña NO está aquí,
-- solo su hash generado con password_hash() de PHP).
-- Las imágenes son un placeholder: las ilustraciones reales de las cartas
-- tienen derechos de autor.
-- ============================================================

SET NAMES utf8mb4;

-- Categorías (una por juego)
INSERT INTO categories (id, name, slug) VALUES
    (1, 'Magic: The Gathering', 'magic'),
    (2, 'Riftbound', 'riftbound'),
    (3, 'Pokémon TCG', 'pokemon'),
    (4, 'Yu-Gi-Oh!', 'yu-gi-oh');

-- Productos (la misma carta en otro estado, idioma o foil es otro producto)
INSERT INTO products (id, category_id, name, description, price, tax_rate, stock, image_url, active,
                      product_type, set_name, card_condition, language, illustrator, is_foil, card_ref) VALUES
    (1, 1, 'Sol Ring (nueva)', 'Artefacto clásico de Commander que añade maná incoloro desde el primer turno. Carta suelta en perfecto estado.', 3.50, 21.00, 15, 'products/sol-ring-nm.png', 1, 'single', 'Commander Masters', 'NM', 'Inglés', 'Mark Tedin', 0, 'sol-ring'),
    (2, 1, 'Lightning Bolt (usada)', 'Conjuro instantáneo de daño directo, imprescindible en mazos rojos. Ejemplar con ligeros signos de uso, edición en español.', 1.90, 21.00, 8, 'products/lightning-bolt-lp.png', 1, 'single', 'Magic 2011', 'LP', 'Español', 'Christopher Moeller', 0, 'lightning-bolt'),
    (3, 1, 'Lightning Bolt (foil)', 'Mismo conjuro en versión foil, con acabado brillante. Ejemplar prácticamente nuevo.', 5.25, 21.00, 4, 'products/lightning-bolt-foil.png', 1, 'single', 'Magic 2011', 'NM', 'Inglés', 'Christopher Moeller', 1, 'lightning-bolt'),
    (5, 2, 'Sobre Riftbound: Origins', 'Sobre precintado de la primera edición de Riftbound, el juego de cartas de League of Legends.', 4.50, 21.00, 20, 'products/booster-riftbound-origins.png', 1, 'booster', 'Origins', NULL, 'Inglés', NULL, 0, NULL),
    (6, 3, 'Pikachu', 'Carta clásica de la edición Base Set. Ejemplar con desgaste moderado.', 9.50, 21.00, 3, 'products/pikachu-mp.png', 1, 'single', 'Base Set', 'MP', 'Inglés', 'Mitsuhiro Arita', 0, 'pikachu'),
    (7, 3, 'Mewtwo (holo)', 'Carta holográfica de la edición Base Set. Ejemplar con ligeros signos de uso.', 18.00, 21.00, 2, 'products/mewtwo-foil-lp.png', 1, 'single', 'Base Set', 'LP', 'Inglés', 'Ken Sugimori', 1, 'mewtwo'),
    (8, 3, 'Sobre Escarlata y Púrpura', 'Sobre precintado de la serie Escarlata y Púrpura en español.', 4.55, 21.00, 30, 'products/booster-escarlata-purpura.png', 1, 'booster', 'Escarlata y Púrpura', NULL, 'Español', NULL, 0, NULL),
    (9, 4, 'Dark Magician (usada)', 'Monstruo emblemático del juego. Ejemplar con ligeros signos de uso.', 12.00, 21.00, 5, 'products/dark-magician-lp.png', 1, 'single', 'Legend of Blue Eyes White Dragon', 'LP', 'Inglés', 'Kazuki Takahashi', 0, 'dark-magician'),
    (10, 4, 'Blue-Eyes White Dragon', 'Dragón icónico de la primera edición del juego, versión japonesa. Ejemplar con desgaste moderado.', 24.00, 21.00, 2, 'products/blue-eyes-mp.png', 1, 'single', 'Legend of Blue Eyes White Dragon', 'MP', 'Japonés', 'Kazuki Takahashi', 0, 'blue-eyes-white-dragon'),
    (11, 4, 'Sobre Maze of Millennia', 'Sobre precintado de la edición Maze of Millennia.', 4.20, 21.00, 25, 'products/booster-maze-of-millennia.png', 1, 'booster', 'Maze of Millennia', NULL, 'Inglés', NULL, 0, NULL),
    (12, 1, 'Sol Ring (usada)', 'Misma carta que el Sol Ring nuevo, en ejemplar usado con ligeros signos de uso. Más barata.', 2.80, 21.00, 6, 'products/sol-ring-lp.png', 1, 'single', 'Commander Masters', 'LP', 'Inglés', 'Mark Tedin', 0, 'sol-ring'),
    (13, 1, 'Sol Ring (foil)', 'Sol Ring en versión foil, con acabado brillante. Ejemplar prácticamente nuevo.', 8.90, 21.00, 3, 'products/sol-ring-foil.png', 1, 'single', 'Commander Masters', 'NM', 'Inglés', 'Mark Tedin', 1, 'sol-ring'),
    (14, 1, 'Lightning Bolt (nueva)', 'Conjuro instantáneo de daño directo en perfecto estado, edición en inglés.', 2.95, 21.00, 10, 'products/lightning-bolt-nm.png', 1, 'single', 'Magic 2011', 'NM', 'Inglés', 'Christopher Moeller', 0, 'lightning-bolt'),
    (15, 4, 'Dark Magician (nueva)', 'Monstruo emblemático del juego en perfecto estado.', 20.00, 21.00, 3, 'products/dark-magician-nm.png', 1, 'single', 'Legend of Blue Eyes White Dragon', 'NM', 'Inglés', 'Kazuki Takahashi', 0, 'dark-magician'),
    (16, 4, 'Dark Magician (foil)', 'Monstruo emblemático en versión foil, con acabado brillante. Ejemplar prácticamente nuevo.', 38.00, 21.00, 1, 'products/dark-magician-foil.png', 1, 'single', 'Legend of Blue Eyes White Dragon', 'NM', 'Inglés', 'Kazuki Takahashi', 1, 'dark-magician');

-- Administrador de prueba (hash de password_hash(), nunca la contraseña)
INSERT INTO users (id, email, password_hash, role) VALUES
    (1, 'admin@tcg-sie.example', '$2y$10$7bG1cX8LNMrwbQAeCN4y2OEejJk3LRurhEg/bnQTPfjSKjZsrGE1y', 'admin');

-- Clientes ficticios. Teléfono: 000000001 no es de ningún país y los demás van vacíos (campo opcional),
-- para que ningún número pueda ser de una persona real.
INSERT INTO customers (id, name, email, phone, address, city, postal_code) VALUES
    (1, 'Lucía Fernández Prado', 'lucia.fernandez@example.com', '000000001', 'Calle Falsa 12, 2ºA', 'Murcia', '30001'),
    (2, 'Marcos Ibáñez Soler', 'marcos.ibanez@example.com', NULL, 'Avenida de Prueba 45', 'Valencia', '46001'),
    (3, 'Elena Navarro Gil', 'elena.navarro@example.com', NULL, 'Plaza Inventada 3, 1ºB', 'Madrid', '28001'),
    (4, 'Pablo Ortega Ruiz', 'pablo.ortega@example.com', NULL, 'Calle Ejemplo 78', 'Sevilla', '41001');

-- Pedidos de ejemplo en distintos estados
INSERT INTO orders (id, code, customer_id, status, subtotal, discount, shipping, tax, total, created_at) VALUES
    (1, 'PED-000001', 1, 'pendiente_pago', 8.90, 0.00, 4.95, 1.87, 15.72, '2026-09-28 10:15:00'),
    (2, 'PED-000002', 2, 'pagado', 21.10, 2.11, 4.95, 3.99, 27.93, '2026-09-29 18:40:00'),
    (3, 'PED-000003', 3, 'enviado', 51.50, 0.00, 0.00, 10.82, 62.32, '2026-09-25 09:05:00'),
    (4, 'PED-000004', 4, 'cancelado', 14.25, 1.43, 4.95, 2.69, 20.46, '2026-09-30 12:00:00');

-- Líneas de pedido (precio sin IVA; se guarda copia del nombre y el IVA)
INSERT INTO order_items (id, order_id, product_id, product_name, unit_price, tax_rate, quantity, line_total) VALUES
    (1, 1, 1, 'Sol Ring (nueva)', 3.50, 21.00, 2, 7.00),
    (2, 1, 2, 'Lightning Bolt (usada)', 1.90, 21.00, 1, 1.90),
    (3, 2, 9, 'Dark Magician (usada)', 12.00, 21.00, 1, 12.00),
    (4, 2, 8, 'Sobre Escarlata y Púrpura', 4.55, 21.00, 2, 9.10),
    (5, 3, 10, 'Blue-Eyes White Dragon', 24.00, 21.00, 1, 24.00),
    (6, 3, 7, 'Mewtwo (holo)', 18.00, 21.00, 1, 18.00),
    (7, 3, 6, 'Pikachu', 9.50, 21.00, 1, 9.50),
    (8, 4, 3, 'Lightning Bolt (foil)', 5.25, 21.00, 1, 5.25),
    (9, 4, 5, 'Sobre Riftbound: Origins', 4.50, 21.00, 2, 9.00);

-- Pagos simulados (el pedido 1 sigue pendiente porque la tarjeta fue rechazada; el 4 se canceló sin pagar)
INSERT INTO payments (id, order_id, method, status, amount, reference, created_at) VALUES
    (1, 1, 'tarjeta_simulada', 'rejected', 15.72, 'SIM-000001', '2026-09-28 10:15:00'),
    (2, 2, 'tarjeta_simulada', 'approved', 27.93, 'SIM-000002', '2026-09-29 18:40:00'),
    (3, 3, 'bizum_simulado', 'approved', 62.32, 'SIM-000003', '2026-09-25 09:05:00');

-- Historial de estados
INSERT INTO order_status_history (id, order_id, from_status, to_status, changed_at) VALUES
    (1, 1, NULL, 'pendiente_pago', '2026-09-28 10:15:00'),
    (2, 2, NULL, 'pendiente_pago', '2026-09-29 18:40:00'),
    (3, 2, 'pendiente_pago', 'pagado', '2026-09-29 18:41:00'),
    (4, 3, NULL, 'pendiente_pago', '2026-09-25 09:05:00'),
    (5, 3, 'pendiente_pago', 'pagado', '2026-09-25 09:06:00'),
    (6, 3, 'pagado', 'en_preparacion', '2026-09-26 08:30:00'),
    (7, 3, 'en_preparacion', 'enviado', '2026-09-26 16:20:00'),
    (8, 4, NULL, 'pendiente_pago', '2026-09-30 12:00:00'),
    (9, 4, 'pendiente_pago', 'cancelado', '2026-09-30 12:30:00');
