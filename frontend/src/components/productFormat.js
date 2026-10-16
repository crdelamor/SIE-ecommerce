// Formato de presentación de los productos. Solo da forma a los datos que ya llegan de la API:
// ningún precio ni regla se calcula aquí.

/** Importe en euros, por ejemplo 4.24 -> "4,24 €". */
export function formatPrice(value) {
  return new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' }).format(value);
}

const CONDITIONS = {
  NM: 'Nueva (NM)',
  LP: 'Usada (LP)',
  MP: 'Muy usada (MP)',
};

/** Estado de conservación de una carta; los sobres van precintados y no tienen. */
export function conditionLabel(product) {
  if (product.product_type === 'booster') return 'Sobre precintado';
  return CONDITIONS[product.card_condition] ?? '';
}

/** Línea corta con los datos que distinguen un producto: edición, estado e idioma. */
export function productMeta(product) {
  return [product.set_name, conditionLabel(product), product.language].filter(Boolean).join(' · ');
}
