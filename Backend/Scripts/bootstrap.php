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

/**
 * Serves a single BYTEA image row as a raw HTTP response (not JSON).
 * $table is restricted to a fixed allow-list so this can never be used
 * to read arbitrary tables — see the route match in Api/index.php, which
 * only ever passes one of these three values.
 */
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

    $clientEtag = $_SERVER['HTTP_IF_NONE_MATCH'] ?? '';
    if ($clientEtag === $etag) {
        http_response_code(304);
        exit;
    }

    echo $data;
    exit;
}
