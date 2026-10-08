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
