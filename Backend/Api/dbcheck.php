<?php

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', '1');
header('Content-Type: text/plain; charset=utf-8');

require __DIR__ . '/lib/bootstrap.php';

try {
    $pdo = Database::connection();

    echo "OK: PDO создан\n";
    echo "server_version: ", $pdo->query('SHOW server_version')->fetchColumn(), "\n";
    echo "search_path:    ", $pdo->query('SHOW search_path')->fetchColumn(), "\n";
    echo "current_user:   ", $pdo->query('SELECT current_user')->fetchColumn(), "\n";
    echo "current_db:     ", $pdo->query('SELECT current_database()')->fetchColumn(), "\n";

    echo "\n--- Таблицы в текущем search_path ---\n";
    $rows = $pdo->query("
        SELECT table_schema, table_name
        FROM information_schema.tables
        WHERE table_schema NOT IN ('pg_catalog', 'information_schema')
        ORDER BY 1, 2
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (!$rows) {
        echo "(пусто)\n";
    } else {
        foreach ($rows as $row) {
            echo $row['table_schema'], '.', $row['table_name'], "\n";
        }
    }

    echo "\n--- Считаем строки в ключевых таблицах ---\n";
    $tables = [
        'project_types',
        'projects',
        'project_images',
        'product_categories',
        'collections',
        'collection_images',
        'products',
        'product_images',
        'service_packages',
        'service_package_features',
        'company_info',
        'leads',
    ];

    foreach ($tables as $t) {
        try {
            $count = $pdo->query("SELECT COUNT(*) FROM {$t}")->fetchColumn();
            echo str_pad($t, 28), " = ", $count, "\n";
        } catch (Throwable $e) {
            echo str_pad($t, 28), " = ОШИБКА: ", $e->getMessage(), "\n";
        }
    }

    echo "\n--- Проверка формы leads ---\n";
    try {
        $row = $pdo->query("
            SELECT pg_get_constraintdef(oid) AS def
            FROM pg_constraint
            WHERE conrelid = 'leads'::regclass
              AND contype = 'c'
              AND pg_get_constraintdef(oid) LIKE '%form_type%'
        ")->fetch(PDO::FETCH_ASSOC);

        echo $row ? "leads.form_type CHECK: {$row['def']}\n" : "leads.form_type CHECK не найден\n";
    } catch (Throwable $e) {
        echo "ОШИБКА чтения CHECK: ", $e->getMessage(), "\n";
    }

    echo "\n--- Последние 5 лидов ---\n";
    try {
        $stmt = $pdo->query("
            SELECT id, form_type, name, phone, source_page, status, created_at
            FROM leads
            ORDER BY id DESC
            LIMIT 5
        ");
        $any = false;
        foreach ($stmt as $r) {
            $any = true;
            echo "#{$r['id']} {$r['form_type']} | {$r['name']} | {$r['phone']} | {$r['source_page']} | {$r['status']} | {$r['created_at']}\n";
        }
        if (!$any) echo "(пусто)\n";
    } catch (Throwable $e) {
        echo "ОШИБКА чтения leads: ", $e->getMessage(), "\n";
    }

} catch (Throwable $e) {
    echo "FATAL: ", $e->getMessage(), "\n";
    echo $e->getTraceAsString(), "\n";
}
