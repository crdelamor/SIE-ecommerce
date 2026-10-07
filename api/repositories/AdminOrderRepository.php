<?php
declare(strict_types=1);

/**
 * Consultas de pedidos para la administración: lista con filtro por estado y
 * detalle con líneas, pagos e historial de estados.
 */
final class AdminOrderRepository
{
    /** Pedidos del más reciente al más antiguo; si llega $status, solo los de ese estado. */
    public function findAll(?string $status = null): array
    {
        $sql = 'SELECT o.id, o.code, o.created_at, o.status, o.total, c.name AS customer_name
                FROM orders o
                JOIN customers c ON c.id = o.customer_id';
        $params = [];

        if ($status !== null) {
            $sql .= ' WHERE o.status = :status';
            $params['status'] = $status;
        }
        $sql .= ' ORDER BY o.created_at DESC, o.id DESC';

        $stmt = db()->prepare($sql);
        $stmt->execute($params);

        return array_map(fn (array $row): array => [
            'id'            => (int) $row['id'],
            'code'          => $row['code'],
            'created_at'    => $row['created_at'],
            'customer_name' => $row['customer_name'],
            'total'         => (float) $row['total'],
            'status'        => $row['status'],
        ], $stmt->fetchAll());
    }
}
