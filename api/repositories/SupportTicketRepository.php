<?php
declare(strict_types=1);

/**
 * Acceso a la tabla support_tickets.
 */
final class SupportTicketRepository
{
    /** Id y email del cliente del pedido con ese código, o null si no existe. */
    public function findOrderByCode(string $code): ?array
    {
        $stmt = db()->prepare(
            'SELECT o.id, c.email
             FROM orders o JOIN customers c ON c.id = o.customer_id
             WHERE o.code = :code'
        );
        $stmt->execute(['code' => $code]);

        return $stmt->fetch() ?: null;
    }

    /**
     * Una página de incidencias, de la más reciente a la más antigua, con el código del
     * pedido si está vinculada, y el total que cumple el filtro (para la paginación).
     *
     * @return array{tickets: array, total: int}
     */
    public function findPage(?string $status, int $limit, int $offset): array
    {
        $where = $status !== null ? ' WHERE t.status = :status' : '';
        $params = $status !== null ? ['status' => $status] : [];

        $count = db()->prepare('SELECT COUNT(*) FROM support_tickets t' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $stmt = db()->prepare(
            'SELECT t.id, t.created_at, t.name, t.email, t.subject, t.message, t.status, o.id AS order_id, o.code AS order_code
             FROM support_tickets t
             LEFT JOIN orders o ON o.id = t.order_id'
            . $where
            . ' ORDER BY t.created_at DESC, t.id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $tickets = array_map(fn (array $row): array => [
            'id'         => (int) $row['id'],
            'created_at' => $row['created_at'],
            'name'       => $row['name'],
            'email'      => $row['email'],
            'subject'    => $row['subject'],
            'message'    => $row['message'],
            'status'     => $row['status'],
            'order'      => $row['order_id'] === null ? null : ['id' => (int) $row['order_id'], 'code' => $row['order_code']],
        ], $stmt->fetchAll());

        return ['tickets' => $tickets, 'total' => $total];
    }

    /** Guarda la incidencia (estado inicial: abierta) y devuelve su id. */
    public function create(?int $orderId, string $name, string $email, string $subject, string $message): int
    {
        $stmt = db()->prepare(
            'INSERT INTO support_tickets (order_id, name, email, subject, message, status, created_at)
             VALUES (:order_id, :name, :email, :subject, :message, \'abierta\', NOW())'
        );
        $stmt->execute([
            'order_id' => $orderId,
            'name'     => $name,
            'email'    => $email,
            'subject'  => $subject,
            'message'  => $message,
        ]);

        return (int) db()->lastInsertId();
    }
}
