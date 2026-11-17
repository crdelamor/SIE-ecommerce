<?php
declare(strict_types=1);

/**
 * Cuadro de mando: métricas calculadas en el momento con consultas a las tablas bo_*.
 * Ninguna cifra está escrita a mano. Qué mide cada una y por qué: docs/metricas.md.
 */

// Estados que cuentan como venta: el pago simulado ya se ha hecho y el pedido no se ha cancelado
const SALE_STATUSES = ['pagado', 'en_preparacion', 'enviado', 'entregado', 'incidencia'];

/** Ejecuta una consulta preparada y devuelve todas las filas. */
function metricRows(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

/** Primera columna de la primera fila (o null si no hay filas o el valor es NULL). */
function metricValue(string $sql, array $params = []): mixed
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();

    return $value === false ? null : $value;
}

/** [['k' => 'a', 'n' => 3], ...] -> ['a' => 3, ...] con todas las claves de $keys, aunque sean 0. */
function countsByKey(array $rows, array $keys = []): array
{
    $counts = array_fill_keys($keys, 0);
    foreach ($rows as $row) {
        $counts[$row['k']] = (int) $row['n'];
    }

    return $counts;
}

/** Un mapa vacío tiene que salir como {} en el JSON, no como []. */
function asMap(array $counts): array|object
{
    return $counts === [] ? new stdClass() : $counts;
}

function roundOrNull(mixed $value, int $decimals = 2): ?float
{
    return $value === null ? null : round((float) $value, $decimals);
}

/** Serie diaria de $from a hoy, con 0 en los días sin datos. $rows: [['day' => 'AAAA-MM-DD', ...]]. */
function dailySeries(array $rows, DateTimeImmutable $from, array $fields): array
{
    $byDay = array_column($rows, null, 'day');
    $series = [];
    foreach (new DatePeriod($from, new DateInterval('P1D'), new DateTimeImmutable('tomorrow')) as $date) {
        $day = $date->format('Y-m-d');
        $point = ['day' => $day];
        foreach ($fields as $field) {
            $point[$field] = (int) ($byDay[$day][$field] ?? 0);
        }
        $series[] = $point;
    }

    return $series;
}

function salesMetrics(DateTimeImmutable $from): array
{
    $in = implode(', ', array_fill(0, count(SALE_STATUSES), '?'));

    $totals = metricRows(
        "SELECT COUNT(*) AS orders, COALESCE(SUM(total), 0) AS amount FROM bo_orders WHERE status IN ($in)",
        SALE_STATUSES
    )[0];
    $orders = (int) $totals['orders'];

    $perDay = metricRows(
        'SELECT DATE(placed_at) AS day, COUNT(*) AS orders FROM bo_orders WHERE placed_at >= ? GROUP BY DATE(placed_at)',
        [$from->format('Y-m-d')]
    );

    $byStatus = countsByKey(
        metricRows('SELECT status AS k, COUNT(*) AS n FROM bo_orders GROUP BY status'),
        ORDER_STATUSES
    );
    $allOrders = array_sum($byStatus);

    $topGames = metricRows(
        "SELECT l.game, SUM(l.quantity) AS units, SUM(l.line_total) AS amount
         FROM bo_order_lines l JOIN bo_orders o ON o.id = l.order_id
         WHERE o.status IN ($in)
         GROUP BY l.game ORDER BY units DESC, amount DESC",
        SALE_STATUSES
    );
    $topProducts = metricRows(
        "SELECT l.product_name, l.game, SUM(l.quantity) AS units, SUM(l.line_total) AS amount
         FROM bo_order_lines l JOIN bo_orders o ON o.id = l.order_id
         WHERE o.status IN ($in)
         GROUP BY l.product_name, l.game ORDER BY units DESC, amount DESC LIMIT 5",
        SALE_STATUSES
    );

    // Sobres (sin estado de carta) frente a cartas sueltas, y foil
    $mix = metricRows(
        "SELECT SUM(CASE WHEN l.card_condition IS NULL THEN l.quantity ELSE 0 END) AS sealed,
                SUM(CASE WHEN l.card_condition IS NOT NULL THEN l.quantity ELSE 0 END) AS singles,
                SUM(CASE WHEN l.is_foil = 1 THEN l.quantity ELSE 0 END) AS foil
         FROM bo_order_lines l JOIN bo_orders o ON o.id = l.order_id
         WHERE o.status IN ($in)",
        SALE_STATUSES
    )[0];

    // Clientes con al menos una venta y cuántos repiten
    $customers = metricRows(
        "SELECT COUNT(*) AS buyers, COALESCE(SUM(CASE WHEN n > 1 THEN 1 ELSE 0 END), 0) AS repeat_buyers
         FROM (SELECT customer_id, COUNT(*) AS n FROM bo_orders WHERE status IN ($in) GROUP BY customer_id) c",
        SALE_STATUSES
    )[0];

    return [
        'orders_per_day'   => dailySeries($perDay, $from, ['orders']),
        'sales'            => [
            'orders'       => $orders,
            'total_amount' => round((float) $totals['amount'], 2),
            'avg_amount'   => $orders > 0 ? round((float) $totals['amount'] / $orders, 2) : null,
        ],
        'orders_by_status' => $byStatus,
        'cancel_rate'      => $allOrders > 0 ? round($byStatus['cancelado'] / $allOrders, 4) : null,
        'top_games'        => array_map(fn (array $r): array => [
            'game'   => $r['game'],
            'units'  => (int) $r['units'],
            'amount' => round((float) $r['amount'], 2),
        ], $topGames),
        'top_products'     => array_map(fn (array $r): array => [
            'product_name' => $r['product_name'],
            'game'         => $r['game'],
            'units'        => (int) $r['units'],
            'amount'       => round((float) $r['amount'], 2),
        ], $topProducts),
        'product_mix'      => [
            'sealed_units'  => (int) $mix['sealed'],
            'singles_units' => (int) $mix['singles'],
            'foil_units'    => (int) $mix['foil'],
        ],
        'customers'        => [
            'buyers'        => (int) $customers['buyers'],
            'repeat_buyers' => (int) $customers['repeat_buyers'],
        ],
    ];
}

function fulfilmentMetrics(): array
{
    // Horas entre la primera vez que el pedido entra en un estado y la primera vez que entra en el siguiente
    $sql = 'SELECT AVG(TIMESTAMPDIFF(SECOND, a.at, b.at)) / 3600 AS hours, COUNT(*) AS orders
            FROM (SELECT order_id, MIN(changed_at) AS at FROM bo_order_status_log WHERE to_status = ? GROUP BY order_id) a
            JOIN (SELECT order_id, MIN(changed_at) AS at FROM bo_order_status_log WHERE to_status = ? GROUP BY order_id) b
              ON b.order_id = a.order_id AND b.at >= a.at';

    $paidToShipped = metricRows($sql, ['pagado', 'enviado'])[0];
    $shippedToDelivered = metricRows($sql, ['enviado', 'entregado'])[0];

    return [
        'paid_to_shipped_hours'       => roundOrNull($paidToShipped['hours']),
        'paid_to_shipped_orders'      => (int) $paidToShipped['orders'],
        'shipped_to_delivered_hours'  => roundOrNull($shippedToDelivered['hours']),
        'shipped_to_delivered_orders' => (int) $shippedToDelivered['orders'],
    ];
}

function integrationMetrics(DateTimeImmutable $from): array
{
    $byResult = countsByKey(
        metricRows('SELECT result AS k, COUNT(*) AS n FROM bo_integration_log GROUP BY result'),
        LOG_RESULTS
    );
    $errorsByType = countsByKey(metricRows(
        "SELECT error_type AS k, COUNT(*) AS n FROM bo_integration_log
         WHERE result = 'error' AND error_type IS NOT NULL GROUP BY error_type ORDER BY n DESC"
    ));
    $byWorkflow = countsByKey(metricRows(
        'SELECT workflow AS k, COUNT(*) AS n FROM bo_integration_log GROUP BY workflow ORDER BY n DESC'
    ));
    $perDay = metricRows(
        "SELECT DATE(created_at) AS day,
                SUM(CASE WHEN result IN ('ok', 'duplicado') THEN 1 ELSE 0 END) AS ok,
                SUM(CASE WHEN result IN ('error', 'pendiente') THEN 1 ELSE 0 END) AS failed
         FROM bo_integration_log WHERE created_at >= ? GROUP BY DATE(created_at)",
        [$from->format('Y-m-d')]
    );

    // Retraso de sincronización: del pedido en la tienda a su alta en el back-office.
    // Solo los pedidos de los últimos `days` días: los anteriores a la integración se pasaron mucho después.
    $delays = metricRows(
        "SELECT COUNT(*) AS orders,
                SUM(CASE WHEN d < 60 THEN 1 ELSE 0 END) AS under_1_min,
                SUM(CASE WHEN d >= 60 AND d < 600 THEN 1 ELSE 0 END) AS from_1_to_10_min,
                SUM(CASE WHEN d >= 600 THEN 1 ELSE 0 END) AS over_10_min
         FROM (SELECT GREATEST(TIMESTAMPDIFF(SECOND, placed_at, synced_at), 0) AS d FROM bo_orders WHERE placed_at >= ?) t",
        [$from->format('Y-m-d')]
    )[0];
    $median = medianSyncDelay((int) $delays['orders'], $from);

    return [
        'integration'             => $byResult + ['errors_by_type' => asMap($errorsByType)],
        'integration_by_workflow' => asMap($byWorkflow),
        'integration_per_day'     => dailySeries($perDay, $from, ['ok', 'failed']),
        'sync_delay'              => [
            'orders'           => (int) $delays['orders'],
            'median_seconds'   => $median,
            'under_1_min'      => (int) $delays['under_1_min'],
            'from_1_to_10_min' => (int) $delays['from_1_to_10_min'],
            'over_10_min'      => (int) $delays['over_10_min'],
        ],
    ];
}

/** Mediana en segundos (MySQL 8 no tiene MEDIAN). Con pocos pedidos basta con leer los valores ordenados. */
function medianSyncDelay(int $orders, DateTimeImmutable $from): ?int
{
    if ($orders === 0) {
        return null;
    }
    $values = array_map('intval', array_column(metricRows(
        'SELECT GREATEST(TIMESTAMPDIFF(SECOND, placed_at, synced_at), 0) AS d FROM bo_orders WHERE placed_at >= ? ORDER BY d',
        [$from->format('Y-m-d')]
    ), 'd'));
    $middle = intdiv($orders, 2);

    return $orders % 2 === 1 ? $values[$middle] : intdiv($values[$middle - 1] + $values[$middle], 2);
}

function ticketMetrics(): array
{
    $byStatus = countsByKey(
        metricRows('SELECT status AS k, COUNT(*) AS n FROM bo_tickets GROUP BY status'),
        TICKET_STATUSES
    );
    $byPriority = countsByKey(
        metricRows('SELECT priority AS k, COUNT(*) AS n FROM bo_tickets GROUP BY priority'),
        TICKET_PRIORITIES
    );
    // reason es el asunto de la tienda, con el pedido detrás ("Daño en el envío · PED-000027"): se agrupa sin él
    $byReason = countsByKey(metricRows(
        "SELECT SUBSTRING_INDEX(reason, ' · ', 1) AS k, COUNT(*) AS n FROM bo_tickets GROUP BY k ORDER BY n DESC LIMIT 5"
    ));
    $resolution = metricValue(
        'SELECT AVG(TIMESTAMPDIFF(SECOND, opened_at, closed_at)) / 3600 FROM bo_tickets WHERE closed_at IS NOT NULL'
    );
    $withOrder = (int) metricValue('SELECT COUNT(*) FROM bo_tickets WHERE order_code IS NOT NULL');
    $linked = (int) metricValue(
        'SELECT COUNT(DISTINCT c.id) FROM bo_customers c JOIN bo_tickets t ON t.customer_id = c.id
         WHERE c.telegram_chat_id IS NOT NULL'
    );
    $withTickets = (int) metricValue('SELECT COUNT(DISTINCT customer_id) FROM bo_tickets');

    return [
        'tickets' => [
            'total'                => array_sum($byStatus),
            'open'                 => $byStatus['abierta'] + $byStatus['en_curso'],
            'by_status'            => $byStatus,
            'by_priority'          => $byPriority,
            'top_reasons'          => asMap($byReason),
            'with_order'           => $withOrder,
            'avg_resolution_hours' => roundOrNull($resolution),
            'customers'            => $withTickets,
            'customers_telegram'   => $linked,
        ],
    ];
}

function proposalMetrics(): array
{
    $statuses = ['pendiente_revision', 'aprobado', 'rechazado', 'ejecutado_simulado'];
    $byStatus = countsByKey(
        metricRows('SELECT final_status AS k, COUNT(*) AS n FROM bo_agent_proposals GROUP BY final_status'),
        $statuses
    );
    $approved = $byStatus['aprobado'] + $byStatus['ejecutado_simulado'];
    $decided = $approved + $byStatus['rechazado'];

    // Confianza que dio el agente según lo que decidió la persona
    $confidence = metricRows(
        "SELECT AVG(CASE WHEN final_status IN ('aprobado', 'ejecutado_simulado') THEN confidence END) AS approved,
                AVG(CASE WHEN final_status = 'rechazado' THEN confidence END) AS rejected,
                AVG(confidence) AS all_proposals
         FROM bo_agent_proposals"
    )[0];
    $byCaseType = countsByKey(metricRows(
        'SELECT case_type AS k, COUNT(*) AS n FROM bo_agent_proposals GROUP BY case_type ORDER BY n DESC'
    ));
    $review = metricValue(
        'SELECT AVG(TIMESTAMPDIFF(SECOND, created_at, reviewed_at)) / 60 FROM bo_agent_proposals WHERE reviewed_at IS NOT NULL'
    );

    return [
        'proposals' => [
            'total'              => array_sum($byStatus),
            'by_status'          => $byStatus,
            'approval_rate'      => $decided > 0 ? round($approved / $decided, 4) : null,
            'avg_confidence'     => [
                'all'      => roundOrNull($confidence['all_proposals']),
                'approved' => roundOrNull($confidence['approved']),
                'rejected' => roundOrNull($confidence['rejected']),
            ],
            'by_case_type'       => asMap($byCaseType),
            'avg_review_minutes' => roundOrNull($review, 1),
        ],
    ];
}

/** Visitas de la tienda en el periodo. Las guarda POST /bo-api/web-metrics (flujo "Analítica"). */
function webMetrics(DateTimeImmutable $from): array
{
    $params = [$from->format('Y-m-d')];
    $totals = metricRows(
        'SELECT COALESCE(SUM(visits), 0) AS visits, COALESCE(SUM(visit_seconds), 0) AS seconds,
                COALESCE(SUM(visits_with_cart), 0) AS cart, COALESCE(SUM(visits_with_cart_and_order), 0) AS cart_order,
                MAX(updated_at) AS updated_at
         FROM bo_web_metrics WHERE day >= ?',
        $params
    )[0];
    $perDay = metricRows(
        'SELECT day, visits, visits_with_cart, visits_with_cart_and_order FROM bo_web_metrics WHERE day >= ?',
        $params
    );
    $visits = (int) $totals['visits'];
    $cart = (int) $totals['cart'];
    $cartOrder = (int) $totals['cart_order'];

    return [
        'web'            => [
            'visits'                     => $visits,
            'avg_visit_seconds'          => $visits > 0 ? (int) round($totals['seconds'] / $visits) : null,
            'visits_with_cart'           => $cart,
            'visits_with_cart_and_order' => $cartOrder,
            'cart_rate'                  => $visits > 0 ? round($cart / $visits, 4) : null,
            'cart_to_order_rate'         => $cart > 0 ? round($cartOrder / $cart, 4) : null,
            'updated_at'                 => fromDbDate($totals['updated_at']),
        ],
        'visits_per_day' => dailySeries($perDay, $from, ['visits', 'visits_with_cart', 'visits_with_cart_and_order']),
    ];
}

// GET /bo-api/metrics?days=30 - datos del cuadro de mando. days: días de las series diarias (1 a 365)
$router->get('/metrics', function (): void {
    $days = filter_var($_GET['days'] ?? 30, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 365]]);
    if ($days === false) {
        jsonValidationError(['days']);
    }
    $from = new DateTimeImmutable('today -' . ($days - 1) . ' days');

    jsonResponse(
        ['generated_at' => date(DATE_ATOM), 'days' => $days]
        + salesMetrics($from)
        + ['fulfilment' => fulfilmentMetrics()]
        + integrationMetrics($from)
        + ticketMetrics()
        + proposalMetrics()
        + webMetrics($from)
    );
});
