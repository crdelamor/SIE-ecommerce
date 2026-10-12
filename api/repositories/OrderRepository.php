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

    // Resta el stock solo si hay suficiente. Devuelve false si no quedaba.
    public function decreaseStock(int $productId, int $quantity): bool
    {
        $stmt = db()->prepare(
            'UPDATE products SET stock = stock - :quantity WHERE id = :id AND stock >= :min'
        );
        $stmt->execute(['quantity' => $quantity, 'id' => $productId, 'min' => $quantity]);
        return $stmt->rowCount() === 1;
    }

    public function createCustomer(array $customer): int
    {
        $stmt = db()->prepare(
            'INSERT INTO customers (name, email, phone, address, city, postal_code)
             VALUES (:name, :email, :phone, :address, :city, :postal_code)'
        );
        $stmt->execute([
            'name'        => $customer['name'],
            'email'       => $customer['email'],
            'phone'       => $customer['phone'],
            'address'     => $customer['address'],
            'city'        => $customer['city'],
            'postal_code' => $customer['postal_code'],
        ]);
        return (int) db()->lastInsertId();
    }

    // Crea el pedido en pendiente_pago con sus líneas. El código PED-000123 sale del id.
    public function createOrder(int $customerId, array $quote, ?array $billing): array
    {
        $stmt = db()->prepare(
            'INSERT INTO orders (code, customer_id, status, subtotal, discount, shipping, tax, total,
                                 billing_name, billing_address, billing_city, billing_postal_code)
             VALUES (:code, :customer_id, :status, :subtotal, :discount, :shipping, :tax, :total,
                     :billing_name, :billing_address, :billing_city, :billing_postal_code)'
        );
        $stmt->execute([
            'code'                => 'TMP-' . bin2hex(random_bytes(6)), // se cambia justo abajo
            'customer_id'         => $customerId,
            'status'              => 'pendiente_pago',
            'subtotal'            => $quote['subtotal'],
            'discount'            => $quote['discount'],
            'shipping'            => $quote['shipping'],
            'tax'                 => $quote['tax'],
            'total'               => $quote['total'],
            'billing_name'        => $billing['name'] ?? null,
            'billing_address'     => $billing['address'] ?? null,
            'billing_city'        => $billing['city'] ?? null,
            'billing_postal_code' => $billing['postal_code'] ?? null,
        ]);
        $orderId = (int) db()->lastInsertId();

        $code = sprintf('PED-%06d', $orderId);
        db()->prepare('UPDATE orders SET code = :code WHERE id = :id')
            ->execute(['code' => $code, 'id' => $orderId]);

        foreach ($quote['items'] as $line) {
            $this->addItem($orderId, $line);
        }
        return ['id' => $orderId, 'code' => $code];
    }

    private function addItem(int $orderId, array $line): void
    {
        $stmt = db()->prepare(
            'INSERT INTO order_items (order_id, product_id, product_name, unit_price, tax_rate, quantity, line_total)
             VALUES (:order_id, :product_id, :product_name, :unit_price, :tax_rate, :quantity, :line_total)'
        );
        $stmt->execute([
            'order_id'     => $orderId,
            'product_id'   => $line['product_id'],
            'product_name' => $line['name'],
            'unit_price'   => $line['unit_price'],
            'tax_rate'     => $line['tax_rate'],
            'quantity'     => $line['quantity'],
            'line_total'   => $line['line_total'],
        ]);
    }

    // Resumen del pedido para la confirmación. Sin datos del cliente: el código se puede adivinar.
    public function findSummary(string $code): ?array
    {
        $stmt = db()->prepare(
            'SELECT id, code, status, subtotal, discount, shipping, tax, total, created_at FROM orders WHERE code = :code'
        );
        $stmt->execute(['code' => $code]);
        $order = $stmt->fetch();
        if (!$order) {
            return null;
        }

        $stmt = db()->prepare(
            'SELECT product_name, quantity, unit_price, line_total FROM order_items WHERE order_id = :id ORDER BY id'
        );
        $stmt->execute(['id' => $order['id']]);

        return [
            'code'       => $order['code'],
            'status'     => $order['status'],
            'created_at' => $order['created_at'],
            'items'      => array_map(fn ($line) => [
                'name'       => $line['product_name'],
                'quantity'   => (int) $line['quantity'],
                'unit_price' => (float) $line['unit_price'],
                'line_total' => (float) $line['line_total'],
            ], $stmt->fetchAll()),
            'subtotal'   => (float) $order['subtotal'],
            'discount'   => (float) $order['discount'],
            'shipping'   => (float) $order['shipping'],
            'tax'        => (float) $order['tax'],
            'total'      => (float) $order['total'],
        ];
    }

    // Pedido por su código, bloqueado hasta el final de la transacción para que no se pague dos veces
    public function findForPayment(string $code): ?array
    {
        $stmt = db()->prepare('SELECT id, code, status, total FROM orders WHERE code = :code FOR UPDATE');
        $stmt->execute(['code' => $code]);
        return $stmt->fetch() ?: null;
    }

    public function addPayment(int $orderId, string $method, string $status, string $amount, string $reference): void
    {
        $stmt = db()->prepare(
            'INSERT INTO payments (order_id, method, status, amount, reference)
             VALUES (:order_id, :method, :status, :amount, :reference)'
        );
        $stmt->execute([
            'order_id'  => $orderId,
            'method'    => $method,
            'status'    => $status,
            'amount'    => $amount,
            'reference' => $reference,
        ]);
    }
}
