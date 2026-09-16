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

/**
 * Off by default. Turn on with either:
 *   - an APP_DEBUG=1 environment variable (docker-compose.yml), or
 *   - a top-level 'debug' => true in config.local.php
 * to see the *real* exception (class, message, file:line) inside the
 * JSON error response instead of the generic 500 message — the fastest
 * way to see exactly which table/column a query is failing on when
 * something works locally but not on the real deployment.
 * Turn this back off once the real error is found; it can leak schema
 * details.
 */
function appDebugEnabled(): bool
{
    if (getenv('APP_DEBUG') === '1') {
        return true;
    }
    $configPath = __DIR__ . '/../config.local.php';
    if (is_file($configPath)) {
        $config = require $configPath;
        return is_array($config) && ($config['debug'] ?? false) === true;
    }
    return false;
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

    $error = ['message' => 'Внутренняя ошибка сервера. Попробуйте позже.', 'fields' => (object) []];
    if (appDebugEnabled()) {
        $error['debug'] = [
            'exception' => get_class($exception),
            'message' => $exception->getMessage(),
            'at' => $exception->getFile() . ':' . $exception->getLine(),
        ];
    }
    respond(['error' => $error], 500);
});
