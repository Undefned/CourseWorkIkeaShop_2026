<?php

declare(strict_types=1);

final class ApiException extends RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        string $message,
        private readonly array $fields = [],
    ) {
        parent::__construct($message);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function fields(): array
    {
        return $this->fields;
    }
}

function respond(array $body, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    exit;
}

function fail(int $status, string $message, array $fields = []): never
{
    throw new ApiException($status, $message, $fields);
}

function requireMethod(string ...$allowedMethods): void
{
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (!in_array($method, $allowedMethods, true)) {
        header('Allow: ' . implode(', ', $allowedMethods));
        fail(405, 'Метод не поддерживается.');
    }
}

function requestBody(): array
{
    $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));

    if ($type === 'application/json') {
        $raw = file_get_contents('php://input', false, null, 0, 16385);
        if ($raw === false || strlen($raw) > 16384) {
            fail(413, 'Слишком большой запрос.');
        }
        $body = json_decode($raw ?: '{}', true);
        if (!is_array($body)) {
            fail(400, 'Ожидается JSON-объект.');
        }
        return $body;
    }

    if ($type === 'application/x-www-form-urlencoded') {
        return $_POST;
    }

    fail(415, 'Используйте application/json или application/x-www-form-urlencoded.');
}

function queryText(string $key, string $default = ''): string
{
    $value = $_GET[$key] ?? $default;
    if (!is_string($value) || strlen($value) > 200) {
        fail(422, 'Некорректный параметр: ' . $key);
    }
    return trim($value);
}

function queryInteger(string $key, int $default, int $max): int
{
    $value = filter_var(queryText($key, (string) $default), FILTER_VALIDATE_INT);
    if ($value === false || $value < 1 || $value > $max) {
        fail(422, 'Некорректный параметр: ' . $key);
    }
    return $value;
}

/** Like queryInteger, but the parameter is mandatory — no default to fall back to. */
function requiredQueryInteger(string $key): int
{
    $raw = $_GET[$key] ?? null;
    $value = is_scalar($raw) ? filter_var($raw, FILTER_VALIDATE_INT) : false;
    if ($value === false || $value < 1) {
        fail(422, 'Некорректный или отсутствующий параметр: ' . $key);
    }
    return $value;
}

function requiredQuerySlug(string $key = 'slug'): string
{
    $value = queryText($key);
    if ($value === '' || !preg_match('/^[a-z0-9-]+$/', $value)) {
        fail(422, 'Некорректный или отсутствующий параметр: ' . $key);
    }
    return $value;
}
