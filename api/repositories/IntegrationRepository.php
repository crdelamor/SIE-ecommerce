<?php
declare(strict_types=1);

/**
 * Consultas de la integración con n8n (A): eventos integrados con el sobre común y el id
 * de un pedido por su código.
 */
final class IntegrationRepository
{
    /** Eventos integrados con esos ids, en orden. Los que no existen no se devuelven. */
    public function findEventsByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $marks = implode(',', array_fill(0, count($ids), '?'));
        $types = implode(',', array_fill(0, count(INTEGRATED_EVENTS), '?'));

        $stmt = db()->prepare(
            "SELECT id, type, entity_type, entity_id, payload, created_at FROM events
             WHERE id IN ($marks) AND type IN ($types) ORDER BY id"
        );
        $stmt->execute([...array_values($ids), ...INTEGRATED_EVENTS]);

        return array_map([$this, 'envelope'], $stmt->fetchAll());
    }

    /** Eventos integrados con id mayor que $afterId, del más antiguo al más reciente. */
    public function findEventsAfter(int $afterId, int $limit): array
    {
        $types = implode(',', array_fill(0, count(INTEGRATED_EVENTS), '?'));

        $stmt = db()->prepare(
            "SELECT id, type, entity_type, entity_id, payload, created_at FROM events
             WHERE id > ? AND type IN ($types) ORDER BY id LIMIT ?"
        );
        $stmt->bindValue(1, $afterId, PDO::PARAM_INT);
        foreach (INTEGRATED_EVENTS as $i => $type) {
            $stmt->bindValue($i + 2, $type);
        }
        $stmt->bindValue(count(INTEGRATED_EVENTS) + 2, $limit, PDO::PARAM_INT);
        $stmt->execute();

        return array_map([$this, 'envelope'], $stmt->fetchAll());
    }

    /**
     * Pasos del embudo entre dos fechas (incluidas): eventos y sesiones distintas de cada paso.
     * Solo totales: ni ids, ni payloads, ni datos personales.
     */
    public function funnel(string $from, string $to): array
    {
        $steps = ['product.viewed', 'cart.item_added', 'checkout.started', 'order.created'];
        $stmt = db()->prepare(
            'SELECT type, COUNT(*) AS events, COUNT(DISTINCT session_id) AS sessions FROM events
             WHERE type IN (?, ?, ?, ?) AND created_at >= ? AND created_at < DATE_ADD(?, INTERVAL 1 DAY)
             GROUP BY type'
        );
        $stmt->execute([...$steps, $from, $to]);
        $rows = array_column($stmt->fetchAll(), null, 'type');

        return array_map(fn (string $type): array => [
            'type'     => $type,
            'events'   => (int) ($rows[$type]['events'] ?? 0),
            'sessions' => (int) ($rows[$type]['sessions'] ?? 0),
        ], $steps);
    }

    public function findOrderIdByCode(string $code): ?int
    {
        $stmt = db()->prepare('SELECT id FROM orders WHERE code = :code');
        $stmt->execute(['code' => $code]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /** Sobre común de los eventos (docs/integracion.md). `data` es el payload de logEvent(). */
    private function envelope(array $row): array
    {
        return [
            'event_id'    => (int) $row['id'],
            'type'        => $row['type'],
            'entity_type' => $row['entity_type'],
            'entity_id'   => $row['entity_id'] === null ? null : (int) $row['entity_id'],
            'occurred_at' => (new DateTimeImmutable($row['created_at']))->format(DATE_ATOM),
            'source'      => 'tienda',
            'data'        => $row['payload'] === null ? (object) [] : json_decode($row['payload'], true),
        ];
    }
}
