<?php
declare(strict_types=1);

// Lista de pedidos que han llegado por n8n, con filtro por estado y búsqueda por código o cliente.
require __DIR__ . '/../api/lib/page.php';

$manager = requireManagerPage();

$status = queryChoice('status', array_keys(ORDER_STATUS_LABELS));
$search = trim(is_string($_GET['q'] ?? null) ? $_GET['q'] : '');
$page = currentPage();

$where = [];
$params = [];
if ($status !== null) {
    $where[] = 'o.status = ?';
    $params[] = $status;
}
if ($search !== '') {
    $where[] = '(o.code LIKE ? OR c.name LIKE ?)';
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}
$sqlWhere = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);

$stmt = db()->prepare("SELECT COUNT(*) FROM bo_orders o JOIN bo_customers c ON c.id = o.customer_id $sqlWhere");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$stmt = db()->prepare(
    "SELECT o.code, c.name AS customer_name, o.status, o.total, o.placed_at, o.synced_at,
            (SELECT COALESCE(SUM(l.quantity), 0) FROM bo_order_lines l WHERE l.order_id = o.id) AS units
     FROM bo_orders o
     JOIN bo_customers c ON c.id = o.customer_id
     $sqlWhere
     ORDER BY o.placed_at DESC, o.id DESC
     LIMIT " . PAGE_SIZE . ' OFFSET ' . (($page - 1) * PAGE_SIZE)
);
$stmt->execute($params);
$orders = $stmt->fetchAll();

renderHeader('Pedidos', 'pedidos', $manager);
?>
    <main>
        <h1>Pedidos</h1>
        <p class="muted note">Pedidos de la tienda que ha pasado n8n al back-office. <?= $total ?> en total<?= $status !== null || $search !== '' ? ' con estos filtros' : '' ?>.</p>

        <form class="filters" method="get" action="pedidos.php">
            <label for="status">Estado</label>
            <select id="status" name="status">
                <option value="">Todos</option>
                <?php foreach (ORDER_STATUS_LABELS as $value => $text): ?>
                    <option value="<?= e($value) ?>"<?= $value === $status ? ' selected' : '' ?>><?= e($text) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="q">Buscar</label>
            <input id="q" name="q" type="search" placeholder="Código o cliente" value="<?= e($search) ?>">
            <button type="submit">Filtrar</button>
        </form>

        <?php if ($orders === []): ?>
            <p class="empty-state">No hay pedidos<?= $status !== null || $search !== '' ? ' con estos filtros' : ' todavía' ?>.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr><th>Código</th><th>Cliente</th><th>Estado</th><th class="num">Unidades</th><th class="num">Total</th><th>Fecha del pedido</th><th>Sincronizado</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): ?>
                            <tr>
                                <td><a href="pedido.php?code=<?= e(urlencode($o['code'])) ?>"><?= e($o['code']) ?></a></td>
                                <td><?= e($o['customer_name']) ?></td>
                                <td><span class="badge status-<?= e($o['status']) ?>"><?= e(label(ORDER_STATUS_LABELS, $o['status'])) ?></span></td>
                                <td class="num"><?= (int) $o['units'] ?></td>
                                <td class="num"><?= e(formatMoney($o['total'])) ?></td>
                                <td><?= e(formatDate($o['placed_at'])) ?></td>
                                <td><?= e(formatDate($o['synced_at'])) ?></td>
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
