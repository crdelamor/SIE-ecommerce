<?php
declare(strict_types=1);

// Cuadro de mando del gestor. Los datos los pide cuadro-mando.js a GET /bo-api/metrics con la sesión.
require __DIR__ . '/../api/lib/page.php';

$manager = requireManagerPage();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Cuadro de mando · Back-office The Black Lotus TCG</title>
    <link rel="stylesheet" href="assets/cuadro-mando.css">
    <script src="assets/cuadro-mando.js" defer></script>
</head>
<body>
    <p class="prototype">Prototipo académico - sin actividad comercial real</p>
    <header class="topbar">
        <div>
            <h1>Cuadro de mando</h1>
            <p class="muted">Back-office de The Black Lotus TCG · <span id="generated">cargando datos…</span></p>
        </div>
        <div class="topbar-actions">
            <label for="days">Periodo</label>
            <select id="days">
                <option value="7">Últimos 7 días</option>
                <option value="30" selected>Últimos 30 días</option>
                <option value="90">Últimos 90 días</option>
            </select>
            <span class="muted"><?= e($manager['email']) ?></span>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <button type="submit" class="secondary">Salir</button>
            </form>
        </div>
    </header>

    <p class="muted note">El periodo cambia las gráficas por día y el retraso de sincronización; el resto de cifras usa todo el histórico. Cómo se calcula cada una: <code>docs/metricas.md</code>.</p>
    <p id="error" class="form-error" role="alert" hidden></p>

    <main id="dashboard" aria-busy="true">
        <section class="block" aria-labelledby="t-sales">
            <h2 id="t-sales">Ventas</h2>
            <div class="kpis" data-kpis="sales"></div>
            <div class="charts">
                <figure class="chart wide" data-chart="orders_per_day"></figure>
                <figure class="chart" data-chart="orders_by_status"></figure>
                <figure class="chart" data-chart="top_games"></figure>
                <figure class="chart" data-chart="top_products"></figure>
                <figure class="chart" data-chart="product_mix"></figure>
            </div>
        </section>

        <section class="block" aria-labelledby="t-fulfilment">
            <h2 id="t-fulfilment">Preparación y envío</h2>
            <div class="kpis" data-kpis="fulfilment"></div>
        </section>

        <section class="block" aria-labelledby="t-integration">
            <h2 id="t-integration">Integración (n8n)</h2>
            <div class="kpis" data-kpis="integration"></div>
            <div class="charts">
                <figure class="chart wide" data-chart="integration_per_day"></figure>
                <figure class="chart" data-chart="errors_by_type"></figure>
                <figure class="chart" data-chart="integration_by_workflow"></figure>
                <figure class="chart" data-chart="sync_delay"></figure>
            </div>
        </section>

        <section class="block" aria-labelledby="t-tickets">
            <h2 id="t-tickets">Soporte</h2>
            <div class="kpis" data-kpis="tickets"></div>
            <div class="charts">
                <figure class="chart" data-chart="tickets_by_priority"></figure>
                <figure class="chart" data-chart="top_reasons"></figure>
            </div>
        </section>

        <section class="block" aria-labelledby="t-proposals">
            <h2 id="t-proposals">Agente de IA y supervisión</h2>
            <div class="kpis" data-kpis="proposals"></div>
            <div class="charts">
                <figure class="chart" data-chart="proposals_by_status"></figure>
                <figure class="chart" data-chart="by_case_type"></figure>
            </div>
        </section>
    </main>
    <div id="tooltip" class="tooltip" role="status" hidden></div>
</body>
</html>
