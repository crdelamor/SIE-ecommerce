<?php
declare(strict_types=1);

/**
 * Visitas de la tienda por día. Las manda el flujo "Analítica" (GET /api/integration/visits de la
 * tienda) cada hora con ayer y hoy: cada día que llega sustituye al que había, así que repetir
 * una vuelta no duplica nada. Se leen en el cuadro de mando (GET /bo-api/metrics, bloque web).
 */

const WEB_METRIC_FIELDS = ['visits', 'visit_seconds', 'visits_with_cart', 'visits_with_cart_and_order'];

// POST /bo-api/web-metrics  { "days": [ { "day": "2026-11-16", "visits": 2, "visit_seconds": 4,
//                                         "visits_with_cart": 1, "visits_with_cart_and_order": 0 }, ... ] }
$router->post('/web-metrics', function (): void {
    $body = readJsonBody();
    $days = $body['days'] ?? null;
    if (!is_array($days) || !array_is_list($days) || count($days) === 0 || count($days) > 31) {
        jsonValidationError(['days']);
    }

    $errors = [];
    $seen = [];
    foreach ($days as $i => $row) {
        if (!is_array($row)) {
            $errors[] = "days[$i]";
            continue;
        }
        $day = $row['day'] ?? null;
        $date = is_string($day) ? DateTimeImmutable::createFromFormat('!Y-m-d', $day) : false;
        if ($date === false || $date->format('Y-m-d') !== $day || isset($seen[$day])) {
            $errors[] = "days[$i].day";
        } else {
            $seen[$day] = true;
        }
        foreach (WEB_METRIC_FIELDS as $field) {
            if (!is_int($row[$field] ?? null) || $row[$field] < 0) {
                $errors[] = "days[$i].$field";
            }
        }
        // Las visitas con carrito son parte de las visitas, y las que acaban en pedido, de las de carrito
        $counts = array_map(fn (string $f) => $row[$f] ?? null, WEB_METRIC_FIELDS);
        if (count(array_filter($counts, 'is_int')) === 4
            && ($counts[2] > $counts[0] || $counts[3] > $counts[2])) {
            $errors[] = "days[$i].visits_with_cart";
        }
    }
    if ($errors !== []) {
        jsonValidationError(array_unique($errors));
    }

    $pdo = db();
    $stmt = $pdo->prepare(
        'INSERT INTO bo_web_metrics (day, visits, visit_seconds, visits_with_cart, visits_with_cart_and_order)
         VALUES (?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE visits = VALUES(visits), visit_seconds = VALUES(visit_seconds),
             visits_with_cart = VALUES(visits_with_cart), visits_with_cart_and_order = VALUES(visits_with_cart_and_order),
             updated_at = CURRENT_TIMESTAMP'
    );
    $pdo->beginTransaction();
    try {
        foreach ($days as $row) {
            $stmt->execute([$row['day'], $row['visits'], $row['visit_seconds'], $row['visits_with_cart'], $row['visits_with_cart_and_order']]);
        }
        $pdo->commit();
    } catch (PDOException $e) {
        $pdo->rollBack();
        throw $e;
    }

    jsonResponse(['days' => count($days), 'result' => 'guardado']);
});
