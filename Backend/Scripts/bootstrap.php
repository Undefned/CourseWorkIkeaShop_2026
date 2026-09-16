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
    $debug = getenv('APP_DEBUG') === '1';
    $payload = ['message' => 'Внутренняя ошибка сервера. Попробуйте позже.', 'fields' => (object) []];
    if ($debug) {
        $payload['debug'] = [
            'exception' => get_class($exception),
            'message'   => $exception->getMessage(),
            'at'        => $exception->getFile() . ':' . $exception->getLine(),
        ];
    }
    fail(500, $payload['message'], (array) ($payload['debug'] ?? []));
});

/**
 * Ищем config.local.php там же, где его ищет lib/database.php,
 * чтобы на Helios обе точки входа работали одинаково.
 */
function appConfig(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $candidates = [
        dirname(__DIR__, 3) . '/tsogz_private/config.local.php',
        dirname(__DIR__, 2) . '/tsogz_private/config.local.php',
        dirname(__DIR__) . '/Api/config.local.php',
        dirname(__DIR__) . '/config.local.php',
    ];

    foreach ($candidates as $path) {
        if (is_file($path)) {
            $loaded = require $path;
            if (is_array($loaded)) {
                return $config = $loaded;
            }
        }
    }
    return $config = [];
}

function db(): PDO
{
    static $connection = null;
    if ($connection instanceof PDO) {
        return $connection;
    }

    $local = appConfig();
    $dbConfig = is_array($local['db'] ?? null) ? $local['db'] : [];

    $host     = (string)($dbConfig['host']     ?? getenv('PGHOST')     ?: 'db');
    $port     = (string)($dbConfig['port']     ?? getenv('PGPORT')     ?: '5432');
    $database = (string)($dbConfig['name']     ?? getenv('PGDATABASE') ?: '');
    $user     = (string)($dbConfig['user']     ?? getenv('PGUSER')     ?: '');
    $password = (string)($dbConfig['password'] ?? getenv('PGPASSWORD') ?: '');
    $schema   = (string)($dbConfig['schema']   ?? getenv('PGSCHEMA')   ?: '');

    if ($database === '' || $user === '') {
        throw new RuntimeException('Database environment is not configured');
    }

    $connection = new PDO(
        "pgsql:host=$host;port=$port;dbname=$database;connect_timeout=5",
        $user,
        $password,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );

    if ($schema !== '') {
        $quoted = '"' . str_replace('"', '""', $schema) . '"';
        $connection->exec('SET search_path TO ' . $quoted . ', public');
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

function serveImage(string $table, int $id): never
{
    $allowed = ['project_images', 'product_images', 'collection_images'];
    if (!in_array($table, $allowed, true)) {
        fail(404, 'Изображение не найдено.');
    }

    $found = rows("SELECT data, mime_type, updated_at FROM $table WHERE id = :id", ['id' => $id]);
    if (!$found) {
        fail(404, 'Изображение не найдено.');
    }
    $image = $found[0];

    $data = $image['data'];
    if (is_resource($data)) {
        $data = stream_get_contents($data);
    }

    $etag = '"' . md5($table . ':' . $id . ':' . $image['updated_at']) . '"';
    $lastModified = gmdate('D, d M Y H:i:s', strtotime($image['updated_at'])) . ' GMT';

    header('Content-Type: ' . $image['mime_type']);
    header('Content-Length: ' . strlen($data));
    header('Cache-Control: public, max-age=86400');
    header('ETag: ' . $etag);
    header('Last-Modified: ' . $lastModified);

    if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
        http_response_code(304);
        exit;
    }

    echo $data;
    exit;
}