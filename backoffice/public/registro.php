<?php
declare(strict_types=1);

// Registro de integración: una fila por ejecución de n8n, también los reintentos.
require __DIR__ . '/../api/lib/page.php';

$manager = requireManagerPage();

$workflows = db()->query('SELECT DISTINCT workflow FROM bo_integration_log ORDER BY workflow')->fetchAll(PDO::FETCH_COLUMN);

$result = queryChoice('result', array_keys(LOG_RESULT_LABELS));
$workflow = queryChoice('workflow', $workflows);
$event = filter_var($_GET['event'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$event = $event === false ? null : $event;
$page = currentPage();

$where = [];
$params = [];
if ($result !== null) {
    $where[] = 'result = ?';
    $params[] = $result;
}
if ($workflow !== null) {
    $where[] = 'workflow = ?';
    $params[] = $workflow;
}
if ($event !== null) {
    $where[] = 'event_id = ?';
    $params[] = $event;
}
$sqlWhere = $where === [] ? '' : 'WHERE ' . implode(' AND ', $where);
$filtered = $where !== [];

$stmt = db()->prepare("SELECT COUNT(*) FROM bo_integration_log $sqlWhere");
$stmt->execute($params);
$total = (int) $stmt->fetchColumn();

$stmt = db()->prepare(
    "SELECT id, event_id, event_type, workflow, result, error_type, message, payload, n8n_execution_id, created_at
     FROM bo_integration_log
     $sqlWhere
     ORDER BY created_at DESC, id DESC
     LIMIT " . PAGE_SIZE . ' OFFSET ' . (($page - 1) * PAGE_SIZE)
);
$stmt->execute($params);
$logs = $stmt->fetchAll();

renderHeader('Registro de integración', 'registro', $manager);
?>
    <main>
        <h1>Registro de integración</h1>
        <p class="muted note">Cada ejecución de los flujos de n8n deja una fila, también los reintentos y los errores. <?= $total ?> en total<?= $filtered ? ' con estos filtros' : '' ?>. Los tipos de error están en <code>docs/integracion.md</code>.</p>

        <form class="filters" method="get" action="registro.php">
            <label for="result">Resultado</label>
            <select id="result" name="result">
                <option value="">Todos</option>
                <?php foreach (LOG_RESULT_LABELS as $value => $text): ?>
                    <option value="<?= e($value) ?>"<?= $value === $result ? ' selected' : '' ?>><?= e($text) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="workflow">Flujo</label>
            <select id="workflow" name="workflow">
                <option value="">Todos</option>
                <?php foreach ($workflows as $value): ?>
                    <option value="<?= e($value) ?>"<?= $value === $workflow ? ' selected' : '' ?>><?= e($value) ?></option>
                <?php endforeach; ?>
            </select>
            <label for="event">Evento</label>
            <input id="event" name="event" type="number" min="1" inputmode="numeric" placeholder="id" value="<?= $event ?? '' ?>">
            <button type="submit">Filtrar</button>
            <?php if ($filtered): ?><a href="registro.php">Quitar filtros</a><?php endif; ?>
        </form>

        <?php if ($logs === []): ?>
            <p class="empty-state">No hay ejecuciones<?= $filtered ? ' con estos filtros' : ' registradas todavía' ?>.</p>
        <?php else: ?>
            <div class="table-wrap">
                <table class="data">
                    <thead>
                        <tr><th>Fecha</th><th>Evento</th><th>Flujo</th><th>Resultado</th><th>Error</th><th>Ejecución n8n</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $g): ?>
                            <tr>
                                <td><?= e(formatDate($g['created_at'])) ?></td>
                                <td>
                                    <?= e($g['event_type']) ?>
                                    <?php if ($g['event_id'] !== null): ?><a href="registro.php?event=<?= (int) $g['event_id'] ?>">#<?= (int) $g['event_id'] ?></a><?php else: ?><span class="muted">sin id</span><?php endif; ?>
                                </td>
                                <td><?= e($g['workflow']) ?></td>
                                <td><span class="badge result-<?= e($g['result']) ?>"><?= e(label(LOG_RESULT_LABELS, $g['result'])) ?></span></td>
                                <td>
                                    <?= e($g['error_type'] ?? '—') ?>
                                    <?php if ($g['message'] !== null || $g['payload'] !== null): ?>
                                        <details>
                                            <summary>Detalle</summary>
                                            <?php if ($g['message'] !== null): ?><p><?= e($g['message']) ?></p><?php endif; ?>
                                            <?php if ($g['payload'] !== null): ?><pre><?= e(prettyJson($g['payload'])) ?></pre><?php endif; ?>
                                        </details>
                                    <?php endif; ?>
                                </td>
                                <td><?= e($g['n8n_execution_id'] ?? '—') ?></td>
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
