<?php
declare(strict_types=1);

// Consultas del flujo de compra (A)
final class OrderRepository
{
    // Productos activos con esos ids, indexados por id
    public function findProducts(array $ids): array
    {
        if (!$ids) {
            return [];
        }

        $marks = implode(',', array_fill(0, count($ids), '?'));
        $stmt = db()->prepare(
            "SELECT id, name, price, tax_rate, stock FROM products WHERE active = 1 AND id IN ($marks)"
        );
        $stmt->execute(array_values($ids));

        $products = [];
        foreach ($stmt->fetchAll() as $row) {
            $products[(int) $row['id']] = $row;
        }
        return $products;
    }
}
