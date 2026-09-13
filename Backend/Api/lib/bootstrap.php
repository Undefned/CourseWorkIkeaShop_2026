<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '0');

require_once __DIR__ . '/http.php';
require_once __DIR__ . '/database.php';

// Permissive by default so the same API can be called from wherever the
// front end ends up living (Docker same-origin, or Helios on a different
// path/subdomain than the static pages). Tighten to a specific origin
// once you know the real production front-end URL.
header('Access-Control-Allow-Origin: *');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type');
    http_response_code(204);
    exit;
}

set_exception_handler(static function (Throwable $exception): void {
    if ($exception instanceof ApiException) {
        respond([
            'error' => [
                'message' => $exception->getMessage(),
                'fields' => (object) $exception->fields(),
            ],
        ], $exception->statusCode());
    }

    error_log((string) $exception);
    respond([
        'error' => ['message' => 'Внутренняя ошибка сервера. Попробуйте позже.', 'fields' => (object) []],
    ], 500);
});
