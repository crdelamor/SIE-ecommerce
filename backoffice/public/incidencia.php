<?php
declare(strict_types=1);

// Detalle de una incidencia: datos del ticket y cada propuesta del agente con la revisión humana.
require __DIR__ . '/../api/lib/page.php';

$manager = requireManagerPage();

$id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$ticket = null;
if ($id !== false) {
    $stmt = db()->prepare(
        'SELECT t.id, t.shop_ticket_id, c.name AS customer_name, t.order_code, t.reason, t.status, t.priority,
                t.opened_at, t.closed_at
         FROM bo_tickets t
         JOIN bo_customers c ON c.id = t.customer_id
         WHERE t.id = ?'
    );
    $stmt->execute([$id]);
    $ticket = $stmt->fetch() ?: null;
}

if ($ticket === null) {
    http_response_code(404);
    renderHeader('Incidencia no encontrada', 'incidencias', $manager);
    ?>
    <main>
        <p><a href="incidencias.php">← Incidencias</a></p>
        <h1>Incidencia no encontrada</h1>
        <p class="empty-state">No hay ninguna incidencia con ese número en el back-office.</p>
    </main>
    <?php
    renderFooter();
    exit;
}

$stmt = db()->prepare(
    'SELECT id, case_type, consulted_data, proposed_action, justification, confidence, risks,
            final_status, reviewed_by, reviewed_at, created_at
     FROM bo_agent_proposals WHERE ticket_id = ? ORDER BY created_at, id'
);
$stmt->execute([$ticket['id']]);
$proposals = $stmt->fetchAll();

$orderExists = false;
if ($ticket['order_code'] !== null) {
    $stmt = db()->prepare('SELECT 1 FROM bo_orders WHERE code = ?');
    $stmt->execute([$ticket['order_code']]);
    $orderExists = (bool) $stmt->fetchColumn();
}

renderHeader('Incidencia ' . $ticket['shop_ticket_id'], 'incidencias', $manager);
?>
    <main>
        <p><a href="incidencias.php">← Incidencias</a></p>
        <h1>Incidencia <?= (int) $ticket['shop_ticket_id'] ?></h1>

        <section class="block">
            <dl class="facts">
                <div><dt>Cliente</dt><dd><?= e($ticket['customer_name']) ?></dd></div>
                <div><dt>Pedido</dt><dd>
                    <?php if ($orderExists): ?>
                        <a href="pedido.php?code=<?= e(urlencode($ticket['order_code'])) ?>"><?= e($ticket['order_code']) ?></a>
                    <?php else: ?>
                        <?= e($ticket['order_code'] ?? '—') ?>
                    <?php endif; ?>
                </dd></div>
                <div><dt>Motivo</dt><dd><?= e($ticket['reason']) ?></dd></div>
                <div><dt>Prioridad</dt><dd><?= e(ucfirst($ticket['priority'])) ?></dd></div>
                <div><dt>Estado</dt><dd><span class="badge ticket-<?= e($ticket['status']) ?>"><?= e(label(TICKET_STATUS_LABELS, $ticket['status'])) ?></span></dd></div>
                <div><dt>Abierta</dt><dd><?= e(formatDate($ticket['opened_at'])) ?></dd></div>
                <div><dt>Cerrada</dt><dd><?= e(formatDate($ticket['closed_at'])) ?></dd></div>
            </dl>
        </section>

        <h2>Propuestas del agente</h2>
        <?php if ($proposals === []): ?>
            <p class="empty-state">El agente todavía no ha hecho ninguna propuesta para esta incidencia.</p>
        <?php endif; ?>
        <?php foreach ($proposals as $p): ?>
            <section class="block proposal">
                <header class="proposal-head">
                    <h3>Propuesta <?= (int) $p['id'] ?> · <?= e($p['case_type']) ?></h3>
                    <span class="badge proposal-<?= e($p['final_status']) ?>"><?= e(label(PROPOSAL_STATUS_LABELS, $p['final_status'])) ?></span>
                </header>
                <dl class="facts">
                    <div><dt>Creada</dt><dd><?= e(formatDate($p['created_at'])) ?></dd></div>
                    <div><dt>Confianza</dt><dd><?= e(number_format((float) $p['confidence'] * 100, 0)) ?> %</dd></div>
                    <div><dt>Revisada por</dt><dd><?= e($p['reviewed_by'] ?? '—') ?></dd></div>
                    <div><dt>Revisada</dt><dd><?= e(formatDate($p['reviewed_at'])) ?></dd></div>
                </dl>
                <h4>Acción propuesta</h4>
                <p class="prose"><?= nl2br(e($p['proposed_action'])) ?></p>
                <h4>Justificación</h4>
                <p class="prose"><?= nl2br(e($p['justification'])) ?></p>
                <?php if ($p['risks'] !== null && $p['risks'] !== ''): ?>
                    <h4>Riesgos</h4>
                    <p class="prose"><?= nl2br(e($p['risks'])) ?></p>
                <?php endif; ?>
                <details>
                    <summary>Datos que consultó el agente</summary>
                    <pre><?= e(prettyJson($p['consulted_data'])) ?></pre>
                </details>
            </section>
        <?php endforeach; ?>
    </main>
<?php
renderFooter();
