<?php

declare(strict_types=1);

/**
 * Two deployment targets, one class:
 *
 *  - Docker (docker-compose.yml): PGHOST/PGPORT/PGDATABASE/PGUSER/PGPASSWORD
 *    are real environment variables set on the `web` service. Nothing to
 *    configure — this is what happens if no config.local.php is found.
 *
 *  - Helios: there is no equivalent to docker-compose's `environment:`
 *    block, so we look for a config.local.php file instead (same pattern
 *    as your working Helios example's Database class). Copy
 *    config.example.php to config.local.php next to it and fill in real
 *    credentials — config.local.php is meant to be gitignored, never
 *    committed. If found, its `db` array overrides the env-var defaults
 *    key by key.
 */
final class Database
{
    private static ?PDO $pdo = null;

    public static function connection(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $config = self::config();
        $dsn = sprintf(
            'pgsql:host=%s;port=%s;dbname=%s;connect_timeout=5',
            $config['host'],
            $config['port'],
            $config['name'],
        );

        self::$pdo = new PDO($dsn, $config['user'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        if ($config['schema'] !== '') {
            self::$pdo->exec('SET search_path TO ' . self::quoteIdentifier($config['schema']) . ', public');
        }

        return self::$pdo;
    }

    private static function quoteIdentifier(string $identifier): string
    {
        return '"' . str_replace('"', '""', $identifier) . '"';
    }

    private static function config(): array
    {
        // dirname(__DIR__) from lib/ is Api/ — same relative spot as your
        // working example's `dirname(__DIR__) . '/config.local.php'`.
        // The two deeper candidates are for keeping real credentials
        // outside the web root entirely on shared hosting (recommended
        // for Helios) — adjust the folder name/depth to wherever you
        // actually place it relative to Backend/Api/lib.
        $configCandidates = [
            dirname(__DIR__, 4) . '/tsogz_private/config.local.php',
            dirname(__DIR__, 3) . '/tsogz_private/config.local.php',
            dirname(__DIR__) . '/config.local.php',
        ];

        $configPath = null;
        foreach ($configCandidates as $candidate) {
            if (is_file($candidate)) {
                $configPath = $candidate;
                break;
            }
        }

        $localConfig = $configPath ? require $configPath : [];
        $dbConfig = is_array($localConfig['db'] ?? null) ? $localConfig['db'] : [];

        return [
            'host' => (string)($dbConfig['host'] ?? getenv('PGHOST') ?: 'db'),
            'port' => (string)($dbConfig['port'] ?? getenv('PGPORT') ?: '5432'),
            'name' => (string)($dbConfig['name'] ?? getenv('PGDATABASE') ?: 'tsogz'),
            'user' => (string)($dbConfig['user'] ?? getenv('PGUSER') ?: 'postgres'),
            'password' => (string)($dbConfig['password'] ?? getenv('PGPASSWORD') ?: ''),
            'schema' => (string)($dbConfig['schema'] ?? getenv('PGSCHEMA') ?: ''),
        ];
    }
}

/** Thin convenience wrapper so route files don't touch PDO directly. */
function rows(string $sql, array $parameters = []): array
{
    $statement = Database::connection()->prepare($sql);
    $statement->execute($parameters);
    return $statement->fetchAll();
}
