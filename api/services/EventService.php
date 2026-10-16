<?php
declare(strict_types=1);

/**
 * Consulta de eventos con filtro por tipo y exportación a CSV o JSON.
 */
final class EventService
{
    // Columnas fijas del CSV; después van las del payload aplanado (payload.reason, payload.from...)
    private const BASE_COLUMNS = ['id', 'type', 'created_at', 'entity_type', 'entity_id', 'session_id'];

    /** Tipo de evento con forma válida (order.created, support.requested...). */
    public static function isValidType(string $type): bool
    {
        return strlen($type) <= 50 && preg_match('/^[a-z][a-z_]*(\.[a-z][a-z_]*)+$/', $type) === 1;
    }

    /**
     * CSV de los eventos (RFC 4180, separado por comas) con el payload aplanado en columnas,
     * para abrirlo en una hoja de cálculo o cargarlo en una herramienta de analítica.
     */
    public function toCsv(array $events): string
    {
        $rows = array_map(fn (array $event): array => $this->flattenEvent($event), $events);

        // Las columnas del payload son la unión de las de todos los eventos exportados
        $payloadColumns = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $column) {
                if (!in_array($column, self::BASE_COLUMNS, true)) {
                    $payloadColumns[$column] = true;
                }
            }
        }
        $payloadColumns = array_keys($payloadColumns);
        sort($payloadColumns);
        $columns = array_merge(self::BASE_COLUMNS, $payloadColumns);

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF"); // BOM: así Excel reconoce el UTF-8 (tildes, ñ, €)
        fputcsv($out, $columns, ',', '"', '', "\r\n");
        foreach ($rows as $row) {
            $line = array_map(fn (string $column) => $this->safeCell($row[$column] ?? ''), $columns);
            fputcsv($out, $line, ',', '"', '', "\r\n");
        }
        rewind($out);
        $csv = stream_get_contents($out);
        fclose($out);

        return $csv;
    }

    /** JSON de los eventos tal cual (el payload como objeto), para un CRM u otra API. */
    public function toJson(array $events): string
    {
        return json_encode($events, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }

    /** Columnas fijas del evento + el payload aplanado con el prefijo "payload.". */
    private function flattenEvent(array $event): array
    {
        $row = [];
        foreach (self::BASE_COLUMNS as $column) {
            $row[$column] = $event[$column];
        }
        if (is_array($event['payload'])) {
            $this->flatten($event['payload'], 'payload', $row);
        }

        return $row;
    }

    /** {"a": {"b": 1}} -> payload.a.b = 1. Las listas se guardan como JSON en una sola celda. */
    private function flatten(array $data, string $prefix, array &$row): void
    {
        foreach ($data as $key => $value) {
            $column = "$prefix.$key";
            if (is_array($value) && !array_is_list($value)) {
                $this->flatten($value, $column, $row);
            } elseif (is_array($value)) {
                $row[$column] = json_encode($value, JSON_UNESCAPED_UNICODE);
            } else {
                $row[$column] = $value;
            }
        }
    }

    /**
     * Celda segura para hojas de cálculo. Algunos payloads vienen del navegador: un texto
     * que empieza por = + - @ podría ejecutarse como fórmula al abrir el CSV (inyección
     * de CSV, OWASP). Se neutraliza con un apóstrofo delante. Los números se dejan igual.
     */
    private function safeCell(mixed $value): string
    {
        if ($value === null) {
            return '';
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        $text = (string) $value;
        if ($text !== '' && in_array($text[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $text;
        }

        return $text;
    }
}
