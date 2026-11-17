<?php
declare(strict_types=1);

/**
 * Arranque de las páginas de public/ (login, cuadro de mando y pantallas de consulta).
 * Fuera de la raíz web; las páginas la cargan con require.
 */

require __DIR__ . '/db.php';
require __DIR__ . '/session.php';

// Antes de conectar: db.php fija la zona de la sesión de MySQL con date('P')
date_default_timezone_set('Europe/Madrid');

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');

/** Escapa un texto para HTML. */
function e(string $text): string
{
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/** Redirige al login si no hay gestor con sesión. */
function requireManagerPage(): array
{
    $manager = currentManager();
    if ($manager === null) {
        header('Location: login.php');
        exit;
    }

    return $manager;
}

const PAGE_SIZE = 25;

const ORDER_STATUS_LABELS = [
    'pendiente_pago' => 'Pendiente de pago',
    'pagado'         => 'Pagado',
    'en_preparacion' => 'En preparación',
    'enviado'        => 'Enviado',
    'entregado'      => 'Entregado',
    'cancelado'      => 'Cancelado',
    'incidencia'     => 'Incidencia',
];

const TICKET_STATUS_LABELS = [
    'abierta'  => 'Abierta',
    'en_curso' => 'En curso',
    'cerrada'  => 'Cerrada',
];

const PROPOSAL_STATUS_LABELS = [
    'pendiente_revision' => 'Pendiente de revisión',
    'aprobado'           => 'Aprobada',
    'rechazado'          => 'Rechazada',
    'ejecutado_simulado' => 'Ejecutada (simulado)',
];

const LOG_RESULT_LABELS = [
    'ok'        => 'Correcto',
    'error'     => 'Error',
    'duplicado' => 'Duplicado',
    'pendiente' => 'Pendiente',
];

/** Etiqueta de un valor de la base de datos; si no está en la lista, el valor tal cual. */
function label(array $labels, ?string $value): string
{
    return $value === null ? '—' : ($labels[$value] ?? $value);
}

function formatDate(?string $value): string
{
    return $value === null ? '—' : (new DateTimeImmutable($value))->format('d/m/Y H:i');
}

function formatMoney(float|string $value): string
{
    return number_format((float) $value, 2, ',', '.') . ' €';
}

/** Parámetro de la URL si es uno de los permitidos; si no, null (sin filtro). */
function queryChoice(string $name, array $allowed): ?string
{
    $value = $_GET[$name] ?? null;

    return is_string($value) && in_array($value, $allowed, true) ? $value : null;
}

/** Página pedida (desde 1). */
function currentPage(): int
{
    $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

    return $page === false ? 1 : $page;
}

/** Cabecera común: aviso de prototipo, menú y botón de salir. */
function renderHeader(string $title, string $active, array $manager): void
{
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · Back-office The Black Lotus TCG</title>
    <link rel="stylesheet" href="assets/cuadro-mando.css">
</head>
<body>
    <p class="prototype">Prototipo académico - sin actividad comercial real</p>
    <?php renderNav($active, $manager); ?>
    <?php
}

/** Menú del back-office. También lo usa el cuadro de mando, que tiene su propia cabecera. */
function renderNav(string $active, array $manager): void
{
    $links = [
        'cuadro'      => ['./', 'Cuadro de mando'],
        'pedidos'     => ['pedidos.php', 'Pedidos'],
        'incidencias' => ['incidencias.php', 'Incidencias'],
        'registro'    => ['registro.php', 'Registro de integración'],
    ];
    ?>
    <nav class="mainnav" aria-label="Back-office">
        <ul>
            <?php foreach ($links as $key => [$href, $text]): ?>
                <li><a href="<?= e($href) ?>"<?= $key === $active ? ' aria-current="page"' : '' ?>><?= e($text) ?></a></li>
            <?php endforeach; ?>
        </ul>
        <div class="mainnav-user">
            <span class="muted"><?= e($manager['email']) ?></span>
            <form method="post" action="logout.php">
                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                <button type="submit" class="secondary">Salir</button>
            </form>
        </div>
    </nav>
    <?php
}

function renderFooter(): void
{
    echo "</body>\n</html>\n";
}

/** Enlaces de página anterior y siguiente, conservando los filtros de la URL. */
function renderPager(int $page, int $total): void
{
    $pages = max(1, (int) ceil($total / PAGE_SIZE));
    if ($pages === 1) {
        return;
    }
    $link = fn (int $p): string => '?' . http_build_query(['page' => $p] + $_GET);
    ?>
    <nav class="pager" aria-label="Páginas">
        <?php if ($page > 1): ?><a href="<?= e($link($page - 1)) ?>">← Anterior</a><?php endif; ?>
        <span class="muted">Página <?= $page ?> de <?= $pages ?></span>
        <?php if ($page < $pages): ?><a href="<?= e($link($page + 1)) ?>">Siguiente →</a><?php endif; ?>
    </nav>
    <?php
}

/** JSON guardado en la base de datos, con sangría para leerlo. */
function prettyJson(?string $json): string
{
    if ($json === null) {
        return '';
    }
    $data = json_decode($json, true);

    return $data === null ? $json : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
