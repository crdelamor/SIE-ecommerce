<?php
declare(strict_types=1);

/**
 * Acceso a las tablas categories y products para el catálogo público (B).
 * Solo se devuelven productos activos (active = 1).
 */
final class ProductRepository
{
    // Columnas de products y de su categoría que usan el listado y la ficha
    private const SELECT = 'SELECT p.id, p.name, p.description, p.price, p.tax_rate, p.stock, p.image_url,
                                   p.product_type, p.set_name, p.card_condition, p.language, p.illustrator,
                                   p.is_foil, p.card_ref,
                                   c.id AS category_id, c.name AS category_name, c.slug AS category_slug
                            FROM products p
                            JOIN categories c ON c.id = p.category_id';

    /** Todas las categorías (una por juego), en el orden en que se dieron de alta. */
    public function findCategories(): array
    {
        return db()->query('SELECT id, name, slug FROM categories ORDER BY id')->fetchAll();
    }

    /** La categoría con ese slug, o null si no existe. */
    public function findCategoryBySlug(string $slug): ?array
    {
        $stmt = db()->prepare('SELECT id, name, slug FROM categories WHERE slug = :slug');
        $stmt->execute(['slug' => $slug]);

        return $stmt->fetch() ?: null;
    }

    /** Productos activos, de todas las categorías o solo de una. Agrupados por juego y ordenados por nombre. */
    public function findActive(?int $categoryId): array
    {
        $where = ' WHERE p.active = 1' . ($categoryId !== null ? ' AND p.category_id = :category_id' : '');
        $stmt = db()->prepare(self::SELECT . $where . ' ORDER BY c.id, p.name, p.id');
        $stmt->execute($categoryId !== null ? ['category_id' => $categoryId] : []);

        return $stmt->fetchAll();
    }

    /** Un producto activo por su id, o null si no existe o está desactivado. */
    public function findActiveById(int $id): ?array
    {
        $stmt = db()->prepare(self::SELECT . ' WHERE p.active = 1 AND p.id = :id');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    /** Versiones activas de una misma carta (misma card_ref): primero las no foil, y por estado de mejor a peor. */
    public function findVersions(string $cardRef): array
    {
        $stmt = db()->prepare(
            self::SELECT . " WHERE p.active = 1 AND p.card_ref = :card_ref
             ORDER BY p.is_foil, FIELD(p.card_condition, 'NM', 'LP', 'MP'), p.language, p.id"
        );
        $stmt->execute(['card_ref' => $cardRef]);

        return $stmt->fetchAll();
    }
}
