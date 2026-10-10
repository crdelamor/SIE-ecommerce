<?php
declare(strict_types=1);

/**
 * Visor y exportación de eventos (C). Rutas protegidas con requireAdmin().
 *
 *   GET /admin/events?type=
 *   GET /admin/events/export?format=csv|json
 *
 * queryParam(), queryDate(), queryPage() y ADMIN_PER_PAGE están en lib/query.php.
 */

require_once __DIR__ . '/../repositories/EventRepository.php';
require_once __DIR__ . '/../services/EventService.php';

/** Filtros comunes del visor y la exportación: type, from y to (opcionales). Responde 400 si no son válidos. */
function eventFilters(): array
{
    $filters = [
        'type' => queryParam('type'),
        'from' => queryDate('from'),
        'to'   => queryDate('to'),
    ];

    if ($filters['type'] !== null && !EventService::isValidType($filters['type'])) {
        jsonError('Tipo de evento no válido', 400);
    }
    if ($filters['from'] !== null && $filters['to'] !== null && $filters['from'] > $filters['to']) {
        jsonError("La fecha 'from' no puede ser posterior a 'to'", 400);
    }

    return array_filter($filters, fn ($value) => $value !== null);
}

// ?type=order.created&from=2026-10-01&to=2026-10-31&page=2 (opcionales)
// -> 200 { "events": [ { id, type, entity_type, entity_id, payload, session_id, created_at } ],
//          "total", "page", "per_page", "types": [ tipos que existen, para el filtro ] }
$router->get('/admin/events', function (): void {
    AuthService::requireAdmin();

    $filters = eventFilters();
    $page = queryPage();
    $events = new EventRepository();

    jsonResponse($events->findPage($filters, ADMIN_PER_PAGE, ($page - 1) * ADMIN_PER_PAGE) + [
        'page'     => $page,
        'per_page' => ADMIN_PER_PAGE,
        'types'    => $events->distinctTypes(),
    ]);
});

// ?format=csv|json (+ los mismos filtros) -> descarga del fichero con todos los eventos que cumplen los filtros
$router->get('/admin/events/export', function (): void {
    AuthService::requireAdmin();

    $format = queryParam('format') ?? 'csv';
    if (!in_array($format, ['csv', 'json'], true)) {
        jsonError("El formato debe ser 'csv' o 'json'", 400);
    }

    $filters = eventFilters();
    $events = (new EventRepository())->findAll($filters);
    $service = new EventService();

    $filename = 'eventos'
        . (isset($filters['type']) ? '-' . str_replace('.', '_', $filters['type']) : '')
        . '-' . date('Y-m-d') . '.' . $format;

    http_response_code(200);
    header($format === 'csv' ? 'Content-Type: text/csv; charset=utf-8' : 'Content-Type: application/json; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    // Datos internos: que no se guarden en cachés intermedias
    header('Cache-Control: no-store');
    echo $format === 'csv' ? $service->toCsv($events) : $service->toJson($events);
    exit;
});
