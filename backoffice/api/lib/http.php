<?php
declare(strict_types=1);

/**
 * Respuestas JSON y lectura de la petición.
 * Los errores tienen siempre la forma { "error": "mensaje" } (ver docs/backoffice-api.md).
 */

function jsonResponse(mixed $data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function jsonError(string $message, int $status): void
{
    jsonResponse(['error' => $message], $status);
}

/** 422 con la lista de campos que faltan o no son válidos. */
function jsonValidationError(array $fields): void
{
    jsonResponse([
        'error'  => 'Faltan campos obligatorios o no son válidos',
        'fields' => array_values($fields),
    ], 422);
}

// Comprobaciones de los campos del cuerpo JSON

function isPositiveInt(mixed $value): bool
{
    return is_int($value) && $value > 0;
}

function isAmount(mixed $value): bool
{
    return (is_int($value) || is_float($value)) && $value >= 0;
}

function isText(mixed $value, int $maxLength): bool
{
    return is_string($value) && trim($value) !== '' && mb_strlen($value) <= $maxLength;
}

/** '2026-11-05T10:02:11+01:00' -> '2026-11-05 10:02:11' en hora de Madrid, o null si no es una fecha. */
function toDbDate(mixed $value): ?string
{
    if (!is_string($value) || $value === '') {
        return null;
    }
    try {
        $date = new DateTimeImmutable($value);
    } catch (Exception) {
        return null;
    }

    return $date->setTimezone(new DateTimeZone(date_default_timezone_get()))->format('Y-m-d H:i:s');
}

/** DATETIME de MySQL -> ISO 8601 con zona, como en el contrato. */
function fromDbDate(?string $value): ?string
{
    return $value === null ? null : (new DateTimeImmutable($value))->format(DATE_ATOM);
}

/** Lee el cuerpo JSON de la petición; responde 400 si no es un objeto JSON válido. */
function readJsonBody(): array
{
    $data = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($data)) {
        jsonError('El cuerpo de la petición debe ser un JSON válido', 400);
    }

    return $data;
}
