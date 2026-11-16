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

    public function findOrderIdByCode(string $code): ?int
    {
        $stmt = db()->prepare('SELECT id FROM orders WHERE code = :code');
        $stmt->execute(['code' => $code]);
        $id = $stmt->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /**
     * Visitas por día entre dos fechas (incluidas), con 0 en los días sin visitas.
     * Solo totales: ni identificadores de sesión ni payloads.
     */
    public function visitsPerDay(string $from, string $to): array
    {
        // Un día antes y otro después, para no partir las visitas que cruzan la medianoche
        $stmt = db()->prepare(
            'SELECT session_id, type, created_at FROM events
             WHERE session_id IS NOT NULL
               AND created_at >= DATE_SUB(?, INTERVAL 1 DAY) AND created_at < DATE_ADD(?, INTERVAL 2 DAY)
             ORDER BY session_id, created_at, id'
        );
        $stmt->execute([$from, $to]);

        $days = [];
        for ($day = new DateTimeImmutable($from); $day->format('Y-m-d') <= $to; $day = $day->modify('+1 day')) {
            $days[$day->format('Y-m-d')] = [
                'day'                        => $day->format('Y-m-d'),
                'visits'                     => 0,
                'visit_seconds'              => 0,
                'visits_with_cart'           => 0,
                'visits_with_cart_and_order' => 0,
            ];
        }

        foreach (self::groupVisits($stmt->fetchAll()) as $visit) {
            $day = substr($visit['start'], 0, 10);
            if (!isset($days[$day])) {
                continue;
            }
            $days[$day]['visits']++;
            $days[$day]['visit_seconds'] += strtotime($visit['end']) - strtotime($visit['start']);
            $days[$day]['visits_with_cart'] += (int) $visit['cart'];
            $days[$day]['visits_with_cart_and_order'] += (int) ($visit['cart'] && $visit['order']);
        }

        return array_values($days);
    }

    /**
     * Agrupa los eventos (ordenados por session_id y fecha) en visitas: una visita son los eventos de un
     * mismo session_id sin más de 30 minutos entre uno y el siguiente, como en Google Analytics.
     * Devuelve [['start', 'end', 'cart' => bool, 'order' => bool], ...].
     */
    public static function groupVisits(array $rows): array
    {
        $visits = [];
        $current = null;
        $session = null;
        foreach ($rows as $row) {
            $at = $row['created_at'];
            if ($current === null || $row['session_id'] !== $session || strtotime($at) - strtotime($current['end']) > 1800) {
                if ($current !== null) {
                    $visits[] = $current;
                }
                $current = ['start' => $at, 'end' => $at, 'cart' => false, 'order' => false];
                $session = $row['session_id'];
            }
            $current['end'] = $at;
            $current['cart'] = $current['cart'] || $row['type'] === 'cart.item_added';
            $current['order'] = $current['order'] || $row['type'] === 'order.created';
        }
        if ($current !== null) {
            $visits[] = $current;
        }

        return $visits;
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
