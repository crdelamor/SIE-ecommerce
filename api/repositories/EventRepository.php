<?php
declare(strict_types=1);

/**
 * Lectura de la tabla events. Los eventos se escriben siempre con logEvent().
 */
final class EventRepository
{
    /**
     * Una página de eventos, del más reciente al más antiguo, y el total que cumple los
     * filtros (para la paginación).
     *
     * @param array $filters type, from y to ('AAAA-MM-DD'), todos opcionales y ya validados
     * @return array{events: array, total: int}
     */
    public function findPage(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->where($filters);

        $count = db()->prepare('SELECT COUNT(*) FROM events' . $where);
        $count->execute($params);
        $total = (int) $count->fetchColumn();

        $stmt = db()->prepare(
            'SELECT id, type, entity_type, entity_id, payload, session_id, created_at FROM events'
            . $where . ' ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset'
        );
        foreach ($params as $name => $value) {
            $stmt->bindValue($name, $value);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['events' => array_map([$this, 'mapRow'], $stmt->fetchAll()), 'total' => $total];
    }

    /** Todos los eventos que cumplen los filtros, del más antiguo al más reciente (para exportar). */
    public function findAll(array $filters): array
    {
        [$where, $params] = $this->where($filters);

        $stmt = db()->prepare(
            'SELECT id, type, entity_type, entity_id, payload, session_id, created_at FROM events'
            . $where . ' ORDER BY created_at, id'
        );
        $stmt->execute($params);

        return array_map([$this, 'mapRow'], $stmt->fetchAll());
    }

    /** Tipos de evento que existen, para el filtro del visor. */
    public function distinctTypes(): array
    {
        return db()->query('SELECT DISTINCT type FROM events ORDER BY type')->fetchAll(PDO::FETCH_COLUMN);
    }

    /** WHERE con trozos fijos y valores como parámetros. Las fechas, como rango para usar el índice. */
    private function where(array $filters): array
    {
        $where = [];
        $params = [];

        if (isset($filters['type'])) {
            $where[] = 'type = :type';
            $params['type'] = $filters['type'];
        }
        if (isset($filters['from'])) {
            $where[] = 'created_at >= :from';
            $params['from'] = $filters['from'] . ' 00:00:00';
        }
        if (isset($filters['to'])) {
            $where[] = 'created_at < :to_next_day';
            $params['to_next_day'] = (new DateTimeImmutable($filters['to']))->modify('+1 day')->format('Y-m-d 00:00:00');
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function mapRow(array $row): array
    {
        return [
            'id'          => (int) $row['id'],
            'type'        => $row['type'],
            'entity_type' => $row['entity_type'],
            'entity_id'   => $row['entity_id'] === null ? null : (int) $row['entity_id'],
            'payload'     => $row['payload'] === null ? null : json_decode($row['payload'], true),
            'session_id'  => $row['session_id'],
            'created_at'  => $row['created_at'],
        ];
    }
}
