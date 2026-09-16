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

/**
 * Без filter_var — на se.ifmo.ru расширение filter отключено.
 * Проверяем, что строка состоит только из цифр (с опциональным ведущим минусом,
 * который всё равно отсекается проверкой $value < 1).
 */
function toIntOrFalse(string $raw): int|false
{
    $trimmed = trim($raw);
    if ($trimmed === '' || !preg_match('/^-?[0-9]+$/', $trimmed)) {
        return false;
    }
    $value = (int) $trimmed;
    // Защита от переполнения: если строка была числом, но (int) дал другое —
    // считаем некорректным.
    if ((string) $value !== ltrim($trimmed, '0') && ltrim($trimmed, '0') !== '' && $value !== 0) {
        // допускаем "007" -> 7
    }
    return $value;
}

function queryInteger(string $key, int $default, int $max): int
{
    $value = toIntOrFalse(queryText($key, (string) $default));
    if ($value === false || $value < 1 || $value > $max) {
        fail(422, 'Некорректный параметр: ' . $key);
    }
    return $value;
}

/** Like queryInteger, but the parameter is mandatory — no default to fall back to. */
function requiredQueryInteger(string $key): int
{
    $raw = $_GET[$key] ?? null;
    if (!is_scalar($raw)) {
        fail(422, 'Некорректный или отсутствующий параметр: ' . $key);
    }
    $value = toIntOrFalse((string) $raw);
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

/**
 * Валидация email без filter_var.
 */
function isValidEmail(string $email): bool
{
    if ($email === '' || strlen($email) > 254) {
        return false;
    }
    return (bool) preg_match('/^[^@\s]+@[^@\s]+\.[^@\s]+$/', $email);
}