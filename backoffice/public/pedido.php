<?php
declare(strict_types=1);

// Detalle de un pedido: datos, líneas, historial de estados y lo que se registró de él en la integración.
require __DIR__ . '/../api/lib/page.php';

$manager = requireManagerPage();

$code = is_string($_GET['code'] ?? null) ? $_GET['code'] : '';
$stmt = db()->prepare(
    'SELECT o.id, o.code, c.name AS customer_name, o.status, o.total, o.placed_at, o.synced_at
     FROM bo_orders o
     JOIN bo_customers c ON c.id = o.customer_id
     WHERE o.code = ?'
);
$stmt->execute([$code]);
$order = $stmt->fetch() ?: null;

if ($order === null) {
    http_response_code(404);
    renderHeader('Pedido no encontrado', 'pedidos', $manager);
    ?>
    <main>
        <p><a href="pedidos.php">← Pedidos</a></p>
        <h1>Pedido no encontrado</h1>
        <p class="empty-state">No hay ningún pedido con ese código en el back-office.</p>
    </main>
    <?php
    renderFooter();
    exit;
}

$stmt = db()->prepare(
    'SELECT product_name, game, set_name, card_condition, language, is_foil, quantity, unit_price, line_total
     FROM bo_order_lines WHERE order_id = ? ORDER BY id'
);
$stmt->execute([$order['id']]);
$lines = $stmt->fetchAll();

$stmt = db()->prepare(
    'SELECT from_status, to_status, changed_at, event_id
     FROM bo_order_status_log WHERE order_id = ? ORDER BY changed_at, id'
);
$stmt->execute([$order['id']]);
$history = $stmt->fetchAll();

// n8n guarda como payload el data del evento: code en los de pedidos, order_code en support.requested
$stmt = db()->prepare(
    "SELECT id, event_id, event_type, workflow, result, error_type, created_at
     FROM bo_integration_log
     WHERE JSON_UNQUOTE(JSON_EXTRACT(payload, '$.code')) = ?
        OR JSON_UNQUOTE(JSON_EXTRACT(payload, '$.order_code')) = ?
     ORDER BY created_at, id"
);
$stmt->execute([$order['code'], $order['code']]);
$logs = $stmt->fetchAll();

renderHeader('Pedido ' . $order['code'], 'pedidos', $manager);
?>
    <main>
        <p><a href="pedidos.php">← Pedidos</a></p>
        <h1>Pedido <?= e($order['code']) ?></h1>

        <section class="block">
            <dl class="facts">
                <div><dt>Cliente</dt><dd><?= e($order['customer_name']) ?></dd></div>
                <div><dt>Estado</dt><dd><span class="badge status-<?= e($order['status']) ?>"><?= e(label(ORDER_STATUS_LABELS, $order['status'])) ?></span></dd></div>
                <div><dt>Total (simulado)</dt><dd><?= e(formatMoney($order['total'])) ?></dd></div>
                <div><dt>Fecha del pedido</dt><dd><?= e(formatDate($order['placed_at'])) ?></dd></div>
                <div><dt>Sincronizado</dt><dd><?= e(formatDate($order['synced_at'])) ?></dd></div>
            </dl>
        </section>

        <section class="block">
            <h2>Líneas</h2>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr><th>Producto</th><th>Juego</th><th>Edición</th><th>Estado</th><th>Idioma</th><th>Foil</th><th class="num">Cantidad</th><th class="num">Precio</th><th class="num">Importe</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($lines as $l): ?>
                            <tr>
                                <td><?= e($l['product_name']) ?></td>
                                <td><?= e($l['game']) ?></td>
                                <td><?= e($l['set_name'] ?? '—') ?></td>
                                <td><?= e($l['card_condition'] ?? '—') ?></td>
                                <td><?= e($l['language'] ?? '—') ?></td>
                                <td><?= $l['is_foil'] ? 'Sí' : 'No' ?></td>
                                <td class="num"><?= (int) $l['quantity'] ?></td>
                                <td class="num"><?= e(formatMoney($l['unit_price'])) ?></td>
                                <td class="num"><?= e(formatMoney($l['line_total'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="block">
            <h2>Historial de estados</h2>
            <?php if ($history === []): ?>
                <p class="empty-state">Sin cambios registrados.</p>
            <?php else: ?>
                <ol class="timeline">
                    <?php foreach ($history as $h): ?>
                        <li>
                            <time><?= e(formatDate($h['changed_at'])) ?></time>
                            <?php if ($h['from_status'] === null): ?>
                                Alta como <strong><?= e(label(ORDER_STATUS_LABELS, $h['to_status'])) ?></strong>
                            <?php else: ?>
                                <?= e(label(ORDER_STATUS_LABELS, $h['from_status'])) ?> → <strong><?= e(label(ORDER_STATUS_LABELS, $h['to_status'])) ?></strong>
                            <?php endif; ?>
                            <?php if ($h['event_id'] !== null): ?><span class="muted">· evento <?= (int) $h['event_id'] ?></span><?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>

        <section class="block">
            <h2>Registro de integración</h2>
            <?php if ($logs === []): ?>
                <p class="empty-state">No hay ejecuciones registradas con este código.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="data">
                        <thead><tr><th>Fecha</th><th>Evento</th><th>Flujo</th><th>Resultado</th><th>Tipo de error</th></tr></thead>
                        <tbody>
                            <?php foreach ($logs as $g): ?>
                                <tr>
                                    <td><?= e(formatDate($g['created_at'])) ?></td>
                                    <td>
                                        <?= e($g['event_type']) ?>
                                        <?php if ($g['event_id'] !== null): ?><a href="registro.php?event=<?= (int) $g['event_id'] ?>">#<?= (int) $g['event_id'] ?></a><?php endif; ?>
                                    </td>
                                    <td><?= e($g['workflow']) ?></td>
                                    <td><span class="badge result-<?= e($g['result']) ?>"><?= e(label(LOG_RESULT_LABELS, $g['result'])) ?></span></td>
                                    <td><?= e($g['error_type'] ?? '—') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </section>
    </main>
<?php
renderFooter();
