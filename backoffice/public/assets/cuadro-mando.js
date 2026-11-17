// Cuadro de mando: pide GET /bo-api/metrics con la sesión del gestor y dibuja cifras y barras en SVG.
// Sin librerías. Los textos que vienen de los datos (juegos, productos, motivos...) se insertan con textContent.
'use strict';

const SVG_NS = 'http://www.w3.org/2000/svg';
const numberFormat = new Intl.NumberFormat('es-ES');
const euroFormat = new Intl.NumberFormat('es-ES', { style: 'currency', currency: 'EUR' });
const percentFormat = new Intl.NumberFormat('es-ES', { style: 'percent', maximumFractionDigits: 1 });
const decimalFormat = new Intl.NumberFormat('es-ES', { maximumFractionDigits: 1 });

const STATUS_LABELS = {
    pendiente_pago: 'Pendiente de pago', pagado: 'Pagado', en_preparacion: 'En preparación', enviado: 'Enviado',
    entregado: 'Entregado', cancelado: 'Cancelado', incidencia: 'Incidencia',
};
const PROPOSAL_LABELS = {
    pendiente_revision: 'Pendiente de revisión', aprobado: 'Aprobado', rechazado: 'Rechazado',
    ejecutado_simulado: 'Ejecutado (simulado)',
};
const PRIORITY_LABELS = { alta: 'Alta', media: 'Media', baja: 'Baja' };

// ---------- utilidades ----------

function el(tag, attrs = {}, text) {
    const node = document.createElement(tag);
    for (const [key, value] of Object.entries(attrs)) node.setAttribute(key, value);
    if (text !== undefined) node.textContent = text;
    return node;
}

function svgEl(tag, attrs = {}, text) {
    const node = document.createElementNS(SVG_NS, tag);
    for (const [key, value] of Object.entries(attrs)) node.setAttribute(key, String(value));
    if (text !== undefined) node.textContent = text;
    return node;
}

const isEmpty = (value) => value === null || value === undefined;
const fmtNumber = (value) => (isEmpty(value) ? null : numberFormat.format(value));
const fmtEuro = (value) => (isEmpty(value) ? null : euroFormat.format(value));
const fmtPercent = (value) => (isEmpty(value) ? null : percentFormat.format(value));
const fmtHours = (value) => (isEmpty(value) ? null : `${decimalFormat.format(value)} h`);
const fmtMinutes = (value) => (isEmpty(value) ? null : `${decimalFormat.format(value)} min`);
const fmtConfidence = (value) => (isEmpty(value) ? null : decimalFormat.format(value * 100) + ' %');

function fmtSeconds(value) {
    if (isEmpty(value)) return null;
    if (value < 60) return `${value} s`;
    if (value < 3600) return `${decimalFormat.format(value / 60)} min`;
    return `${decimalFormat.format(value / 3600)} h`;
}

/** "2026-11-15" -> "15/11" */
function shortDay(day) {
    const [, month, date] = day.split('-');
    return `${date}/${month}`;
}

/** Objeto { clave: n } -> [{ label, value }], con etiquetas legibles si se dan. */
function entries(map, labels = {}) {
    return Object.entries(map || {}).map(([key, value]) => ({ label: labels[key] || key.replaceAll('_', ' '), value }));
}

// ---------- tooltip ----------

const tooltip = document.getElementById('tooltip');

function showTooltip(event, value, label) {
    tooltip.replaceChildren(el('strong', {}, value), document.createTextNode(label));
    tooltip.hidden = false;
    const box = event.target.getBoundingClientRect();
    const x = event.clientX ?? box.left + box.width / 2;
    const y = event.clientY ?? box.top;
    tooltip.style.left = `${Math.min(x + 12, window.innerWidth - tooltip.offsetWidth - 8)}px`;
    tooltip.style.top = `${Math.max(y - tooltip.offsetHeight - 12, 8)}px`;
}

function hideTooltip() {
    tooltip.hidden = true;
}

/** Zona de hover y foco de una barra: más grande que la barra y con el valor en el tooltip. */
function hitArea(svg, rect, value, label) {
    const hit = svgEl('rect', { ...rect, class: 'hit', tabindex: 0, 'aria-label': `${label}: ${value}` });
    hit.addEventListener('pointermove', (event) => showTooltip(event, value, label));
    hit.addEventListener('focus', (event) => showTooltip(event, value, label));
    hit.addEventListener('pointerleave', hideTooltip);
    hit.addEventListener('blur', hideTooltip);
    svg.append(hit);
}

/** Barra con el extremo de datos redondeado (4 px) y la base recta. */
function barPath(x, y, width, height, horizontal) {
    const r = Math.min(4, horizontal ? height / 2 : width / 2, horizontal ? width : height);
    if (width <= 0 || height <= 0) return '';
    if (horizontal) {
        return `M${x},${y}h${width - r}a${r},${r} 0 0 1 ${r},${r}v${height - 2 * r}a${r},${r} 0 0 1 -${r},${r}h-${width - r}z`;
    }
    return `M${x},${y + height}v-${height - r}a${r},${r} 0 0 1 ${r},-${r}h${width - 2 * r}a${r},${r} 0 0 1 ${r},${r}v${height - r}z`;
}

// ---------- tablas (los mismos datos sin depender del gráfico) ----------

function dataTable(headers, rows) {
    const details = el('details');
    details.append(el('summary', {}, 'Ver los datos'));
    const table = el('table');
    const head = el('tr');
    headers.forEach((h, i) => head.append(el('th', i > 0 ? { class: 'num' } : {}, h)));
    table.append(head);
    rows.forEach((row) => {
        const tr = el('tr');
        row.forEach((cell, i) => tr.append(el('td', i > 0 ? { class: 'num' } : {}, cell)));
        table.append(tr);
    });
    details.append(table);
    return details;
}

// ---------- gráficos ----------

/** Barras horizontales con el valor al final. items: [{ label, value }] */
function horizontalBars(figure, title, items, format = fmtNumber) {
    figure.replaceChildren(el('figcaption', {}, title));
    if (items.length === 0 || items.every((i) => !i.value)) {
        figure.append(el('p', { class: 'empty' }, 'Sin datos todavía.'));
        return;
    }

    // Se dibuja al ancho real de la columna para que el texto no se encoja
    const width = Math.max(260, Math.round(figure.clientWidth || 360));
    const labelWidth = Math.min(150, Math.round(width * 0.38));
    const valueWidth = 56;
    const rowHeight = 28;
    const barHeight = 16;
    const max = Math.max(...items.map((i) => i.value));
    const plotWidth = width - labelWidth - valueWidth;
    const svg = svgEl('svg', { viewBox: `0 0 ${width} ${items.length * rowHeight}`, role: 'img', 'aria-label': title });

    items.forEach((item, i) => {
        const y = i * rowHeight;
        const barWidth = max > 0 ? (item.value / max) * plotWidth : 0;
        const maxChars = Math.floor(labelWidth / 6.5);
        const label = item.label.length > maxChars ? item.label.slice(0, maxChars - 1) + '…' : item.label;
        svg.append(svgEl('text', { x: labelWidth - 8, y: y + rowHeight / 2 + 4, 'text-anchor': 'end', class: 'label' }, label));
        hitArea(svg, { x: 0, y, width, height: rowHeight }, format(item.value), item.label);
        svg.append(svgEl('path', { d: barPath(labelWidth, y + (rowHeight - barHeight) / 2, barWidth, barHeight, true), class: 'bar' }));
        svg.append(svgEl('text', { x: labelWidth + barWidth + 6, y: y + rowHeight / 2 + 4, class: 'value' }, format(item.value)));
    });
    svg.append(svgEl('line', { x1: labelWidth, x2: labelWidth, y1: 0, y2: items.length * rowHeight, class: 'axis' }));

    figure.append(svg, dataTable(['', 'Valor'], items.map((i) => [i.label, format(i.value)])));
}

/**
 * Columnas por día. series: [{ key, name, className }]; con dos series van apiladas
 * (2 px de separación entre segmentos) y con leyenda.
 */
function dailyColumns(figure, title, points, series) {
    figure.replaceChildren(el('figcaption', {}, title));
    const totals = points.map((p) => series.reduce((sum, s) => sum + p[s.key], 0));
    if (totals.every((t) => t === 0)) {
        figure.append(el('p', { class: 'empty' }, 'Sin datos en este periodo.'));
        return;
    }

    if (series.length > 1) {
        const legend = el('div', { class: 'legend' });
        series.forEach((s) => {
            const item = el('span');
            item.append(el('i', { style: `background: var(--${s.className})` }), document.createTextNode(s.name));
            legend.append(item);
        });
        figure.append(legend);
    }

    const width = Math.max(320, Math.round(figure.clientWidth || 960));
    const height = 200;
    const left = 36;
    const bottom = 22;
    const top = 8;
    const plotHeight = height - top - bottom;
    const max = Math.max(...totals);
    const step = niceStep(max);
    const yMax = Math.ceil(max / step) * step;
    const slot = (width - left) / points.length;
    const barWidth = Math.max(2, Math.min(28, slot - 4));
    const svg = svgEl('svg', { viewBox: `0 0 ${width} ${height}`, role: 'img', 'aria-label': title });

    for (let v = 0; v <= yMax; v += step) {
        const y = top + plotHeight - (v / yMax) * plotHeight;
        svg.append(svgEl('line', { x1: left, x2: width, y1: y, y2: y, class: v === 0 ? 'axis' : 'grid' }));
        svg.append(svgEl('text', { x: left - 6, y: y + 4, 'text-anchor': 'end', class: 'tick' }, fmtNumber(v)));
    }

    // Etiquetas del eje X: como mucho unas 10, para que no se pisen
    const labelEvery = Math.ceil(points.length / 10);
    points.forEach((point, i) => {
        const x = left + i * slot + (slot - barWidth) / 2;
        let base = top + plotHeight;
        const parts = series.map((s) => `${s.name}: ${fmtNumber(point[s.key])}`).join(' · ');
        hitArea(svg, { x: left + i * slot, y: top, width: slot, height: plotHeight }, series.length > 1 ? parts : fmtNumber(totals[i]), shortDay(point.day));
        series.forEach((s, si) => {
            const h = (point[s.key] / yMax) * plotHeight;
            if (h <= 0) return;
            const gap = si > 0 ? 2 : 0;
            const isTop = series.slice(si + 1).every((next) => point[next.key] === 0);
            const y = base - h;
            const d = isTop ? barPath(x, y, barWidth, h - gap, false) : `M${x},${y}h${barWidth}v${h - gap}h-${barWidth}z`;
            svg.append(svgEl('path', { d, class: `bar ${s.className === 'series' ? '' : s.className}` }));
            base = y;
        });
        if (i % labelEvery === 0) {
            svg.append(svgEl('text', { x: left + i * slot + slot / 2, y: height - 6, 'text-anchor': 'middle', class: 'tick' }, shortDay(point.day)));
        }
    });

    const headers = ['Día', ...series.map((s) => s.name)];
    figure.append(svg, dataTable(headers, points.map((p) => [shortDay(p.day), ...series.map((s) => fmtNumber(p[s.key]))])));
}

/** Paso de la escala del eje Y: 1, 2 o 5 por potencia de 10, con unas 4 líneas. */
function niceStep(max) {
    if (max <= 4) return 1;
    const raw = max / 4;
    const power = 10 ** Math.floor(Math.log10(raw));
    return [1, 2, 5, 10].map((m) => m * power).find((s) => s >= raw);
}

// ---------- cifras ----------

/** kpis: [{ label, value (ya formateado o null), hint }] */
function renderKpis(name, kpis) {
    const container = document.querySelector(`[data-kpis="${name}"]`);
    container.replaceChildren(...kpis.map((kpi) => {
        const box = el('div', { class: 'kpi' });
        box.append(el('div', { class: 'kpi-label' }, kpi.label));
        box.append(el('div', { class: kpi.value === null ? 'kpi-value empty' : 'kpi-value' }, kpi.value ?? 'Sin datos'));
        if (kpi.hint) box.append(el('div', { class: 'kpi-hint' }, kpi.hint));
        return box;
    }));
}

const chart = (name) => document.querySelector(`[data-chart="${name}"]`);
const ratio = (part, total) => (total > 0 ? part / total : null);

// ---------- cuadro de mando ----------

function render(m) {
    document.getElementById('generated').textContent =
        `datos de ${new Date(m.generated_at).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' })}`;

    // Ventas
    renderKpis('sales', [
        { label: 'Pedidos vendidos', value: fmtNumber(m.sales.orders), hint: 'Pagados y no cancelados' },
        { label: 'Importe total simulado', value: fmtEuro(m.sales.total_amount) },
        { label: 'Pedido medio', value: fmtEuro(m.sales.avg_amount) },
        { label: 'Tasa de cancelación', value: fmtPercent(m.cancel_rate), hint: 'Sobre todos los pedidos' },
        {
            label: 'Clientes que repiten',
            value: m.customers.buyers > 0 ? `${fmtNumber(m.customers.repeat_buyers)} de ${fmtNumber(m.customers.buyers)}` : null,
        },
    ]);
    dailyColumns(chart('orders_per_day'), `Pedidos por día (últimos ${m.days} días)`, m.orders_per_day,
        [{ key: 'orders', name: 'Pedidos', className: 'series' }]);
    horizontalBars(chart('orders_by_status'), 'Pedidos por estado', entries(m.orders_by_status, STATUS_LABELS));
    horizontalBars(chart('top_games'), 'Juegos más vendidos (unidades)', m.top_games.map((g) => ({ label: g.game, value: g.units })));
    horizontalBars(chart('top_products'), 'Productos más vendidos (unidades)', m.top_products.map((p) => ({ label: p.product_name, value: p.units })));
    horizontalBars(chart('product_mix'), 'Unidades vendidas por tipo', [
        { label: 'Sobres', value: m.product_mix.sealed_units },
        { label: 'Cartas sueltas', value: m.product_mix.singles_units },
        { label: 'De ellas, foil', value: m.product_mix.foil_units },
    ]);

    // Preparación y envío
    const f = m.fulfilment;
    renderKpis('fulfilment', [
        { label: 'De pagado a enviado', value: fmtHours(f.paid_to_shipped_hours), hint: `Media de ${fmtNumber(f.paid_to_shipped_orders)} pedidos` },
        { label: 'De enviado a entregado', value: fmtHours(f.shipped_to_delivered_hours), hint: `Media de ${fmtNumber(f.shipped_to_delivered_orders)} pedidos` },
    ]);

    // Integración
    const integ = m.integration;
    const executions = integ.ok + integ.error + integ.duplicado + integ.pendiente;
    renderKpis('integration', [
        { label: 'Ejecuciones registradas', value: fmtNumber(executions) },
        { label: 'Correctas', value: fmtPercent(ratio(integ.ok + integ.duplicado, executions)), hint: 'ok y duplicado' },
        { label: 'Con error', value: fmtNumber(integ.error) },
        { label: 'Pendientes', value: fmtNumber(integ.pendiente) },
        { label: 'Retraso de sincronización', value: fmtSeconds(m.sync_delay.median_seconds), hint: `Mediana de ${fmtNumber(m.sync_delay.orders)} pedidos del periodo` },
    ]);
    dailyColumns(chart('integration_per_day'), `Ejecuciones por día (últimos ${m.days} días)`, m.integration_per_day, [
        { key: 'ok', name: 'Correctas', className: 'good' },
        { key: 'failed', name: 'Con error o pendientes', className: 'critical' },
    ]);
    horizontalBars(chart('errors_by_type'), 'Errores por tipo', entries(integ.errors_by_type));
    horizontalBars(chart('integration_by_workflow'), 'Ejecuciones por flujo', entries(m.integration_by_workflow));
    horizontalBars(chart('sync_delay'), 'Tiempo hasta llegar al back-office', [
        { label: 'Menos de 1 min', value: m.sync_delay.under_1_min },
        { label: 'De 1 a 10 min', value: m.sync_delay.from_1_to_10_min },
        { label: 'Más de 10 min', value: m.sync_delay.over_10_min },
    ]);

    // Soporte
    const t = m.tickets;
    renderKpis('tickets', [
        { label: 'Incidencias abiertas', value: fmtNumber(t.open), hint: `De ${fmtNumber(t.total)} en total` },
        { label: 'Tiempo medio de resolución', value: fmtHours(t.avg_resolution_hours), hint: 'De las cerradas' },
        { label: 'Con pedido', value: fmtNumber(t.with_order) },
        { label: 'Clientes con Telegram', value: t.customers > 0 ? `${fmtNumber(t.customers_telegram)} de ${fmtNumber(t.customers)}` : null },
    ]);
    horizontalBars(chart('tickets_by_priority'), 'Incidencias por prioridad', entries(t.by_priority, PRIORITY_LABELS));
    horizontalBars(chart('top_reasons'), 'Motivos más frecuentes', entries(t.top_reasons));

    // Agente
    const p = m.proposals;
    renderKpis('proposals', [
        { label: 'Propuestas del agente', value: fmtNumber(p.total), hint: `${fmtNumber(p.by_status.pendiente_revision)} pendientes de revisión` },
        { label: 'Tasa de aprobación', value: fmtPercent(p.approval_rate), hint: 'Sobre las ya decididas' },
        { label: 'Confianza de las aprobadas', value: fmtConfidence(p.avg_confidence.approved) },
        { label: 'Confianza de las rechazadas', value: fmtConfidence(p.avg_confidence.rejected) },
        { label: 'Tiempo de revisión humana', value: fmtMinutes(p.avg_review_minutes), hint: 'Media hasta aprobar o rechazar' },
    ]);
    horizontalBars(chart('proposals_by_status'), 'Propuestas por estado', entries(p.by_status, PROPOSAL_LABELS));
    horizontalBars(chart('by_case_type'), 'Propuestas por tipo de caso', entries(p.by_case_type));

    // Visitas de la tienda (flujo "Analítica", cada hora)
    const w = m.web;
    const updated = w.updated_at
        ? `Actualizado ${new Date(w.updated_at).toLocaleString('es-ES', { dateStyle: 'short', timeStyle: 'short' })}`
        : 'Todavía no ha llegado ningún dato';
    renderKpis('web', [
        { label: 'Visitas', value: fmtNumber(w.visits), hint: updated },
        { label: 'Tiempo medio de visita', value: fmtSeconds(w.avg_visit_seconds), hint: 'Del primer al último evento' },
        { label: 'Visitas con carrito', value: fmtPercent(w.cart_rate), hint: `${fmtNumber(w.visits_with_cart)} visitas` },
        { label: 'De carrito a pedido', value: fmtPercent(w.cart_to_order_rate), hint: `${fmtNumber(w.visits_with_cart_and_order)} visitas` },
    ]);
    dailyColumns(chart('visits_per_day'), `Visitas por día (últimos ${m.days} días)`, m.visits_per_day,
        [{ key: 'visits', name: 'Visitas', className: 'series' }]);
    horizontalBars(chart('visits_funnel'), 'Embudo de las visitas', [
        { label: 'Visitas', value: w.visits },
        { label: 'Con carrito', value: w.visits_with_cart },
        { label: 'Con carrito y pedido', value: w.visits_with_cart_and_order },
    ]);
}

async function load() {
    const dashboard = document.getElementById('dashboard');
    const error = document.getElementById('error');
    const days = document.getElementById('days').value;
    dashboard.setAttribute('aria-busy', 'true');

    try {
        const response = await fetch(`/bo-api/metrics?days=${encodeURIComponent(days)}`, { credentials: 'same-origin' });
        if (response.status === 401) {
            window.location.href = 'login.php';
            return;
        }
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        lastMetrics = await response.json();
        render(lastMetrics);
        error.hidden = true;
    } catch (e) {
        error.textContent = 'No se han podido cargar las métricas. Vuelve a intentarlo en unos segundos.';
        error.hidden = false;
        console.error(e);
    } finally {
        dashboard.setAttribute('aria-busy', 'false');
    }
}

let lastMetrics = null;
let resizeTimer = null;
window.addEventListener('resize', () => {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(() => lastMetrics && render(lastMetrics), 200);
});

document.getElementById('days').addEventListener('change', load);
load();
