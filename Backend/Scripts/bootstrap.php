<?php
declare(strict_types=1);

function respond(array $body, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    exit;
}

function fail(int $status, string $message, array $fields = []): never
{
    respond(['error' => ['message' => $message, 'fields' => (object) $fields]], $status);
}

set_exception_handler(function (Throwable $exception): void {
    error_log((string) $exception);
    fail(500, 'Внутренняя ошибка сервера. Попробуйте позже.');
});

function db(): PDO
{
    static $connection;
    if (!$connection) {
        $host = getenv('PGHOST') ?: 'db';
        $port = getenv('PGPORT') ?: '5432';
        $database = getenv('PGDATABASE');
        $user = getenv('PGUSER');
        $password = getenv('PGPASSWORD');
        if (!$database || !$user || $password === false) {
            throw new RuntimeException('Database environment is not configured');
        }
        $connection = new PDO("pgsql:host=$host;port=$port;dbname=$database;connect_timeout=5", $user, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $connection;
}

function rows(string $sql, array $parameters = []): array
{
    $statement = db()->prepare($sql);
    $statement->execute($parameters);
    return $statement->fetchAll();
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

function requestBody(): array
{
    $type = strtolower(trim(explode(';', $_SERVER['CONTENT_TYPE'] ?? '')[0]));
    if ($type === 'application/json') {
        $raw = file_get_contents('php://input', false, null, 0, 16385);
        if ($raw === false || strlen($raw) > 16384) {
            fail(413, 'Слишком большой запрос.');
        }
        try {
            $body = json_decode($raw, false, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            fail(400, 'Некорректный JSON.');
        }
        if (!$body instanceof stdClass) {
            fail(400, 'Ожидается JSON-объект.');
        }
        return (array) $body;
    }
    if ($type === 'application/x-www-form-urlencoded') {
        return $_POST;
    }
    fail(415, 'Используйте application/json или application/x-www-form-urlencoded.');
}
