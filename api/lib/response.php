<?php
declare(strict_types=1);

/**
 * Respuestas JSON de la API. Los errores tienen siempre la forma { "error": "mensaje" }.
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
