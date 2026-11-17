<?php
declare(strict_types=1);

// Incidencias del back-office con la última propuesta del agente y su decisión.
require __DIR__ . '/../api/lib/page.php';

$manager = requireManagerPage();

$status = queryChoice('status', array_keys(TICKET_STATUS_LABELS));
$proposal = queryChoice('proposal', array_keys(PROPOSAL_STATUS_LABELS));
$page = currentPage();

$where = [];
$params = [];
if ($status !== null) {
    $where[] = 't.status = ?';
    $params[] = $status;
}
if ($proposal !== null) {
    $where[] = 'p.final_status = ?';
    $params[] = $proposal;
}
$sqlWhere = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

// Última propuesta de cada ticket (puede no haber ninguna)
$from = 'FROM bo_tickets t
         JOIN bo_customers c ON c.id = t.customer_id
         LEFT JOIN bo_agent_proposals p ON p.id = (
             SELECT MAX(p2.id) FROM bo_agent_proposals p2 WHERE p2.ticket_id = t.id
         )';

$stmt = db()->prepare("SELECT COUNT(*) $from $sqlWhere");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$stmt = db()->prepare(
    "SELECT t.id, t.shop_ticket_id, c.name AS customer_name, t.order_code, t.reason, t.status, t.priority,
            t.opened_at, t.closed_at, p.case_type, p.final_status, p.reviewed_by
     $from
     $sqlWhere
     ORDER BY t.opened_at DESC, t.id DESC
     LIMIT " . PAGE_SIZE . ' OFFSET ' . (($page - 1) * PAGE_SIZE)
);
$stmt->execute($params);
$tickets = $stmt->fetchAll();

renderHeader('Incidencias', 'incidencias', $manager);
?>
    <main>
        <h1>Incidencias</h1>
        <p class="muted note">Incidencias de soporte de la tienda y lo que propuso el agente de IA. <?= $total ?> en total<?= $status !== null || $proposal !== null ? ' con estos filtros' : '' ?>. El número es el de la tienda, el mismo que ve el cliente.</p>

        <form class="filters" method="get" action="incidencias.php">
            <label for="status">Estado</label>
            <select id="status" name="status">
                <option value="">Todos</option>
                <?php foreach (TICKET_STATUS_LABELS as $value => $text): ?>
                    <option value="<?= e($value) ?>"<?= $value === $status ? ' selected' : '' ?>><?= e($text) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="proposal">Propuesta</label>
            <select id="proposal" name="proposal">
                <option value="">Todas</option>
                <?php foreach (PROPOSAL_STATUS_LABELS as $value => $text): ?>
                    <option value="<?= e($value) ?>"<?= $value === $proposal ? ' selected' : '' ?>><?= e($text) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Filtrar</button>
        </form>

        <?php if ($tickets === []): ?>
            <p class="empty-state">No hay incidencias<?= $status !== null || $proposal !== null ? ' con estos filtros' : ' todavía' ?>.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr><th>Incidencia</th><th>Cliente</th><th>Pedido</th><th>Motivo</th><th>Prioridad</th><th>Estado</th><th>Propuesta del agente</th><th>Abierta</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($tickets as $t): ?>
                            <tr>
                                <td><a href="incidencia.php?id=<?= (int) $t['id'] ?>"><?= (int) $t['shop_ticket_id'] ?></a></td>
                                <td><?= e($t['customer_name']) ?></td>
                                <td><?= e($t['order_code'] ?? '—') ?></td>
                                <td><?= e(ticketReason($t['reason'], $t['order_code'])) ?></td>
                                <td><?= e(ucfirst($t['priority'])) ?></td>
                                <td><span class="badge ticket-<?= e($t['status']) ?>"><?= e(label(TICKET_STATUS_LABELS, $t['status'])) ?></span></td>
                                <td>
                                    <?php if ($t['final_status'] === null): ?>
                                        <span class="muted">Sin propuesta</span>
                                    <?php else: ?>
                                        <span class="badge proposal-<?= e($t['final_status']) ?>"><?= e(label(PROPOSAL_STATUS_LABELS, $t['final_status'])) ?></span>
                                        <span class="muted"><?= e(label(CASE_TYPE_LABELS, $t['case_type'])) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e(formatDate($t['opened_at'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php renderPager($page, $total); ?>
        <?php endif; ?>
    </main>
<?php
renderFooter();
