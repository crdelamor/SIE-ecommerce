<?php
declare(strict_types=1);

/** Error del catálogo con el código HTTP que debe devolver la API (por ejemplo 404). */
final class CatalogException extends RuntimeException
{
}

/**
 * Catálogo público: categorías, listado y ficha de producto.
 * El precio con IVA (price_with_tax) se calcula aquí, en el servidor: el navegador solo lo muestra.
 */
final class CatalogService
{
    public function __construct(private ProductRepository $products = new ProductRepository())
    {
    }

    /** Categorías (una por juego): id, name y slug. */
    public function categories(): array
    {
        return array_map(fn (array $row): array => [
            'id'   => (int) $row['id'],
            'name' => $row['name'],
            'slug' => $row['slug'],
        ], $this->products->findCategories());
    }

    /**
     * Productos activos de todo el catálogo o de la categoría con ese slug.
     *
     * @throws CatalogException 404 si el slug no corresponde a ninguna categoría
     */
    public function products(?string $categorySlug): array
    {
        $categoryId = null;
        if ($categorySlug !== null) {
            $category = $this->products->findCategoryBySlug($categorySlug);
            if ($category === null) {
                throw new CatalogException('La categoría no existe', 404);
            }
            $categoryId = (int) $category['id'];
        }

        return array_map(fn (array $row): array => $this->summary($row), $this->products->findActive($categoryId));
    }

    /**
     * Ficha de un producto, con las demás versiones de la misma carta, y registra product.viewed.
     *
     * @throws CatalogException 404 si el producto no existe o está desactivado
     */
    public function viewProduct(int $id): array
    {
        $row = $this->products->findActiveById($id);
        if ($row === null) {
            throw new CatalogException('El producto no existe', 404);
        }

        $product = $this->summary($row) + [
            'description' => $row['description'],
            'illustrator' => $row['illustrator'],
            'price'       => (float) $row['price'],
            'tax_rate'    => (float) $row['tax_rate'],
            'versions'    => $row['card_ref'] === null ? [] : $this->versions((string) $row['card_ref']),
        ];

        $this->logView($product);

        return $product;
    }

    /**
     * Precio con IVA de una unidad, en euros. Se calcula en céntimos y con el mismo redondeo
     * que el checkout (PriceCalculator), para que el precio que ve el cliente sea coherente.
     */
    public static function priceWithTax(string|float $price, string|float $taxRate): float
    {
        $cents = (int) round((float) $price * 100);
        $rate = (int) round((float) $taxRate * 100); // 21.00 -> 2100

        return (int) round($cents * (10000 + $rate) / 10000) / 100;
    }

    /** Datos del producto que necesitan el listado y la ficha. */
    private function summary(array $row): array
    {
        return [
            'id'             => (int) $row['id'],
            'name'           => $row['name'],
            'price_with_tax' => self::priceWithTax($row['price'], $row['tax_rate']),
            'stock'          => (int) $row['stock'],
            'image_url'      => $row['image_url'],
            'product_type'   => $row['product_type'],
            'set_name'       => $row['set_name'],
            'card_condition' => $row['card_condition'],
            'language'       => $row['language'],
            'is_foil'        => (bool) $row['is_foil'],
            'category'       => [
                'id'   => (int) $row['category_id'],
                'name' => $row['category_name'],
                'slug' => $row['category_slug'],
            ],
        ];
    }

    /** Otras versiones (y la propia) de una carta: lo justo para elegir entre ellas en la ficha. */
    private function versions(string $cardRef): array
    {
        return array_map(function (array $row): array {
            $summary = $this->summary($row);

            return [
                'id'             => $summary['id'],
                'name'           => $summary['name'],
                'card_condition' => $summary['card_condition'],
                'language'       => $summary['language'],
                'is_foil'        => $summary['is_foil'],
                'price_with_tax' => $summary['price_with_tax'],
                'stock'          => $summary['stock'],
                'image_url'      => $summary['image_url'],
            ];
        }, $this->products->findVersions($cardRef));
    }

    /**
     * Registra product.viewed con logEvent(). Es una lectura: si el evento no se puede guardar,
     * se anota en el log del servidor y la ficha se muestra igualmente.
     */
    private function logView(array $product): void
    {
        try {
            logEvent('product.viewed', 'product', $product['id'], [
                'category'       => $product['category']['slug'],
                'product_type'   => $product['product_type'],
                'card_condition' => $product['card_condition'],
                'language'       => $product['language'],
                'is_foil'        => $product['is_foil'],
            ]);
        } catch (Throwable $e) {
            error_log((string) $e);
        }
    }
}
