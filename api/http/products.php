<?php
declare(strict_types=1);

/**
 * Catálogo público (B). Rutas abiertas, sin sesión:
 *
 *   GET /categories              Lista las categorías (una por juego)
 *   GET /products?category=slug  Lista los productos activos (todos o los de un juego)
 *   GET /products/{id}           Ficha del producto. Registra product.viewed
 */

require_once __DIR__ . '/../repositories/ProductRepository.php';
require_once __DIR__ . '/../services/CatalogService.php';

// -> 200 { "categories": [ { id, name, slug } ] }
$router->get('/categories', function (): void {
    jsonResponse(['categories' => (new CatalogService())->categories()]);
});

// ?category=magic (opcional)
// -> 200 { "products": [ { id, name, price_with_tax, stock, image_url, product_type, set_name,
//                          card_condition, language, is_foil, category: { id, name, slug } } ] }
// -> 400 si el slug no tiene forma válida · 404 si no existe esa categoría
$router->get('/products', function (): void {
    $category = queryParam('category');
    if ($category !== null && !preg_match('/^[a-z0-9-]{1,100}$/', $category)) {
        jsonError('La categoría no es válida', 400);
    }

    try {
        jsonResponse(['products' => (new CatalogService())->products($category)]);
    } catch (CatalogException $e) {
        jsonError($e->getMessage(), $e->getCode());
    }
});

// -> 200 { "product": { ...los campos del listado, description, illustrator, price (sin IVA), tax_rate,
//                       versions: [ { id, name, card_condition, language, is_foil, price_with_tax, stock, image_url } ] } }
// -> 400 si el id no es un entero positivo · 404 si no existe o está desactivado
$router->get('/products/{id}', function (array $params): void {
    $id = $params['id'];
    if (!ctype_digit($id) || strlen($id) > 9 || (int) $id < 1) {
        jsonError('El identificador del producto no es válido', 400);
    }

    try {
        jsonResponse(['product' => (new CatalogService())->viewProduct((int) $id)]);
    } catch (CatalogException $e) {
        jsonError($e->getMessage(), $e->getCode());
    }
});
