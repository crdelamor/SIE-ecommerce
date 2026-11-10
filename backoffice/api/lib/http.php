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

/** Lee el cuerpo JSON de la petición; responde 400 si no es un objeto JSON válido. */
function readJsonBody(): array
{
    $data = json_decode(file_get_contents('php://input') ?: '', true);
    if (!is_array($data)) {
        jsonError('El cuerpo de la petición debe ser un JSON válido', 400);
    }

    return $data;
}
