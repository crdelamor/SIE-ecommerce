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

    /** Pedido completo para la ficha de administración, o null si no existe. */
    public function findById(int $id): ?array
    {
        $stmt = db()->prepare(
            'SELECT o.id, o.code, o.status, o.subtotal, o.discount, o.shipping, o.tax, o.total, o.created_at,
                    c.name, c.email, c.phone, c.address, c.city, c.postal_code
             FROM orders o
             JOIN customers c ON c.id = o.customer_id
             WHERE o.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        if ($row === false) {
            return null;
        }

        return [
            'id'         => (int) $row['id'],
            'code'       => $row['code'],
            'status'     => $row['status'],
            'created_at' => $row['created_at'],
            'subtotal'   => (float) $row['subtotal'],
            'discount'   => (float) $row['discount'],
            'shipping'   => (float) $row['shipping'],
            'tax'        => (float) $row['tax'],
            'total'      => (float) $row['total'],
            'customer'   => [
                'name'        => $row['name'],
                'email'       => $row['email'],
                'phone'       => $row['phone'],
                'address'     => $row['address'],
                'city'        => $row['city'],
                'postal_code' => $row['postal_code'],
            ],
            'items'      => $this->findItems($id),
            'payments'   => $this->findPayments($id),
            'history'    => $this->findHistory($id),
        ];
    }

    /**
     * Líneas del pedido. Nombre, precio, IVA y cantidad son la copia guardada al comprar;
     * los datos TCG (juego, set, estado, idioma, foil) se leen del producto ACTUAL porque
     * order_items no los guarda (pendiente con el grupo).
     */
    private function findItems(int $orderId): array
    {
        $stmt = db()->prepare(
            'SELECT oi.id, oi.product_id, oi.product_name, oi.unit_price, oi.tax_rate, oi.quantity, oi.line_total,
                    cat.name AS game, p.product_type, p.set_name, p.card_condition, p.language, p.is_foil
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             LEFT JOIN categories cat ON cat.id = p.category_id
             WHERE oi.order_id = :order_id
             ORDER BY oi.id'
        );
        $stmt->execute(['order_id' => $orderId]);

        return array_map(fn (array $row): array => [
            'id'             => (int) $row['id'],
            'product_id'     => (int) $row['product_id'],
            'product_name'   => $row['product_name'],
            'unit_price'     => (float) $row['unit_price'],
            'tax_rate'       => (float) $row['tax_rate'],
            'quantity'       => (int) $row['quantity'],
            'line_total'     => (float) $row['line_total'],
            'game'           => $row['game'],
            'product_type'   => $row['product_type'],
            'set_name'       => $row['set_name'],
            'card_condition' => $row['card_condition'],
            'language'       => $row['language'],
            'is_foil'        => $row['is_foil'] === null ? null : (bool) $row['is_foil'],
        ], $stmt->fetchAll());
    }

    private function findPayments(int $orderId): array
    {
        $stmt = db()->prepare(
            'SELECT id, method, status, amount, reference, created_at
             FROM payments WHERE order_id = :order_id ORDER BY created_at, id'
        );
        $stmt->execute(['order_id' => $orderId]);

        return array_map(fn (array $row): array => [
            'id'         => (int) $row['id'],
            'method'     => $row['method'],
            'status'     => $row['status'],
            'amount'     => (float) $row['amount'],
            'reference'  => $row['reference'],
            'created_at' => $row['created_at'],
        ], $stmt->fetchAll());
    }

    /**
     * Lee el estado del pedido y lo BLOQUEA hasta el final de la transacción
     * (SELECT ... FOR UPDATE): otro cambio simultáneo del mismo pedido espera a este.
     * Devuelve ['code', 'status'] o null si el pedido no existe.
     */
    public function lockForStatusChange(int $id): ?array
    {
        $stmt = db()->prepare('SELECT code, status FROM orders WHERE id = :id FOR UPDATE');
        $stmt->execute(['id' => $id]);

        return $stmt->fetch() ?: null;
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = db()->prepare('UPDATE orders SET status = :status WHERE id = :id');
        $stmt->execute(['status' => $status, 'id' => $id]);
    }

    /** $fromStatus es null en la primera fila del pedido (alta en pendiente_pago). */
    public function addStatusHistory(int $id, ?string $fromStatus, string $toStatus): void
    {
        $stmt = db()->prepare(
            'INSERT INTO order_status_history (order_id, from_status, to_status, changed_at)
             VALUES (:order_id, :from_status, :to_status, NOW())'
        );
        $stmt->execute(['order_id' => $id, 'from_status' => $fromStatus, 'to_status' => $toStatus]);
    }

    /**
     * Devuelve al stock las unidades de todas las líneas del pedido. Se suman antes por
     * producto: si un producto aparece en dos líneas, un UPDATE con JOIN directo solo
     * sumaría una de ellas.
     */
    public function restockOrderItems(int $orderId): void
    {
        $stmt = db()->prepare(
            'UPDATE products p
             JOIN (SELECT product_id, SUM(quantity) AS units
                   FROM order_items WHERE order_id = :order_id
                   GROUP BY product_id) AS sold ON sold.product_id = p.id
             SET p.stock = p.stock + sold.units'
        );
        $stmt->execute(['order_id' => $orderId]);
    }

    /** Historial de estados, del más antiguo al más reciente. */
    private function findHistory(int $orderId): array
    {
        $stmt = db()->prepare(
            'SELECT from_status, to_status, changed_at
             FROM order_status_history WHERE order_id = :order_id ORDER BY changed_at, id'
        );
        $stmt->execute(['order_id' => $orderId]);

        return $stmt->fetchAll();
    }
}
