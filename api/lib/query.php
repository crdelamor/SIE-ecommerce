<?php
declare(strict_types=1);

/**
 * Lectura de parámetros de la URL (?status=, ?from=, ?page=...) común a los módulos.
 * Se carga desde index.php, así no depende del orden en que se cargan los ficheros de http/.
 */

/** Parámetro de la URL como texto; null si no viene o está vacío. Un array (?x[]=) es un 400. */
function queryParam(string $name): ?string
{
    $value = $_GET[$name] ?? null;
    if ($value !== null && !is_string($value)) {
        jsonError("El parámetro '$name' no es válido", 400);
    }
    $value = $value === null ? null : trim($value);

    return $value === '' ? null : $value;
}

/** Fecha 'AAAA-MM-DD' real (rechaza '2026-02-30' o '30/09/2026'); responde 400 si no lo es. */
function queryDate(string $name): ?string
{
    $value = queryParam($name);
    if ($value === null) {
        return null;
    }
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if ($date === false || $date->format('Y-m-d') !== $value) {
        jsonError("La fecha '$name' debe tener el formato AAAA-MM-DD", 400);
    }

    return $value;
}

/** Número de página de ?page= (1 si no viene); responde 400 si no es un entero de 1 a 100000. */
function queryPage(): int
{
    $page = queryParam('page') ?? '1';
    if (!ctype_digit($page) || (int) $page < 1 || (int) $page > 100000) {
        jsonError('Página no válida', 400);
    }

    return (int) $page;
}

// Elementos por página en las listas de la administración (pedidos, incidencias y eventos)
const ADMIN_PER_PAGE = 20;
