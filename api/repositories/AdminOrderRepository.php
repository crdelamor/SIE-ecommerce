<?php
declare(strict_types=1);

/**
 * Consultas de pedidos para la administración: lista con filtro por estado y
 * detalle con líneas, pagos e historial de estados.
 */
final class AdminOrderRepository
{
    /**
     * Una página de pedidos, del más reciente al más antiguo, y el total de pedidos
     * que cumplen los filtros (para la paginación).
     *
     * Filtros opcionales (ya validados por el controlador):
     *   status  estado del contrato
     *   from    'AAAA-MM-DD', desde ese día incluido
     *   to      'AAAA-MM-DD', hasta ese día incluido
     *   q       texto que aparece en el código del pedido o en el nombre del cliente
     *
     * @return array{orders: array, total: int}
     */
    public function findPage(array $filters, int $limit, int $offset): array
    {
        $where = [];
        $params = [];

        if (isset($filters['status'])) {
            $where[] = 'o.status = :status';
            $params['status'] = $filters['status'];
        }
        // Rangos sobre la columna, sin DATE(o.created_at): así MySQL puede usar el índice de created_at
        if (isset($filters['from'])) {
            $where[] = 'o.created_at >= :from';
            $params['from'] = $filters['from'] . ' 00:00:00';
        }
        if (isset($filters['to'])) {
            $where[] = 'o.created_at < :to_next_day';
            $params['to_next_day'] = (new DateTimeImmutable($filters['to']))->modify('+1 day')->format('Y-m-d 00:00:00');
        }
        if (isset($filters['q'])) {
            // % y _ son comodines de LIKE: se escapan para buscar el texto tal cual
            $like = '%' . addcslashes($filters['q'], '%_\\') . '%';
            $where[] = '(o.code LIKE :q_code OR c.name LIKE :q_name)';
            $params['q_code'] = $like;
            $params['q_name'] = $like;
        }

        // Solo se unen trozos de SQL fijos; los valores van siempre como parámetros
        $from = ' FROM orders o JOIN customers c ON c.id = o.customer_id'
            . ($where ? ' WHERE ' . implode(' AND ', $where) : '');

        $count = db()->prepare('SELECT COUNT(*)' . $from);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $stmt = db()->prepare(
            'SELECT o.id, o.code, o.created_at, o.status, o.total, c.name AS customer_name'
            . $from
            . ' ORDER BY o.created_at DESC, o.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        // LIMIT y OFFSET tienen que ir como enteros
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $orders = array_map(fn (array $row): array => [
            'id'            => (int) $row['id'],
            'code'          => $row['code'],
            'created_at'    => $row['created_at'],
            'customer_name' => $row['customer_name'],
            'total'         => (float) $row['total'],
            'status'        => $row['status'],
        ], $stmt->fetchAll());

        return ['orders' => $orders, 'total' => $total];
    }
}
