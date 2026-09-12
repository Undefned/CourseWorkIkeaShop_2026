<?php
declare(strict_types=1);

require_once __DIR__ . '/../Scripts/bootstrap.php';
require_once __DIR__ . '/../Scripts/applications.php';

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/', '/');
$method = $_SERVER['REQUEST_METHOD'];
$known = preg_match('~^/api/(health|categories|products(?:/[a-z0-9-]+)?|projects(?:/[a-z0-9-]+)?|services|applications)$~D', $path);
if (!$known) {
    fail(404, 'Маршрут не найден.');
}
$allowed = $path === '/api/applications' ? 'POST' : 'GET';
if ($method !== $allowed) {
    header('Allow: ' . $allowed);
    fail(405, 'Метод не поддерживается.');
}
if ($path === '/api/applications') {
    createApplication();
}
if ($path === '/api/health') {
    rows('SELECT 1');
    respond(['data' => ['status' => 'ok']]);
}
if ($path === '/api/categories') {
    respond(['data' => rows('SELECT id, slug, name FROM categories ORDER BY sort_order, id')]);
}
if ($path === '/api/services') {
    $services = rows('SELECT id, slug, name, description, price_from, price_unit, features FROM services WHERE is_active = TRUE ORDER BY sort_order, id');
    foreach ($services as &$service) {
        $service['features'] = json_decode($service['features'], true, 32, JSON_THROW_ON_ERROR);
    }
    unset($service);
    respond(['data' => $services]);
}
$isProduct = str_starts_with($path, '/api/products');
$table = $isProduct ? 'products' : 'projects';
$parts = explode('/', $path);
if (isset($parts[3])) {
    $item = rows("SELECT * FROM $table WHERE slug = :slug AND is_active = TRUE", ['slug' => $parts[3]]);
    if (!$item) {
        fail(404, $isProduct ? 'Товар не найден.' : 'Проект не найден.');
    }
    $imageTable = $isProduct ? 'product_images' : 'project_images';
    $foreignKey = $isProduct ? 'product_id' : 'project_id';
    $item[0]['images'] = rows("SELECT url, alt FROM $imageTable WHERE $foreignKey = :id ORDER BY sort_order, id", ['id' => $item[0]['id']]);
    respond(['data' => $item[0]]);
}
$page = queryInteger('page', 1, 100000);
$limit = queryInteger('limit', 12, 100);
$offset = ($page - 1) * $limit;
$where = ['is_active = TRUE'];
$params = [];
if ($isProduct) {
    $category = queryText('category');
    if ($category !== '') {
        $where[] = 'category_id = (SELECT id FROM categories WHERE slug = :category)';
        $params['category'] = $category;
    }
    $search = queryText('q');
    if ($search !== '') {
        $where[] = 'name ILIKE :search';
        $params['search'] = '%' . $search . '%';
    }
} else {
    $type = queryText('type');
    if ($type !== '') {
        if (!in_array($type, ['apartment', 'house', 'commercial'], true)) {
            fail(422, 'Неизвестный тип проекта.');
        }
        $where[] = 'object_type = :type';
        $params['type'] = $type;
    }
}
$sorts = $isProduct
    ? ['default' => 'sort_order, id', 'price_asc' => 'price, id', 'price_desc' => 'price DESC, id']
    : ['default' => 'sort_order, id', 'newest' => 'year DESC NULLS LAST, id'];
$sort = queryText('sort', 'default');
if (!isset($sorts[$sort])) {
    fail(422, 'Неизвестный порядок сортировки.');
}
$condition = implode(' AND ', $where);
$total = (int) rows("SELECT COUNT(*) AS total FROM $table WHERE $condition", $params)[0]['total'];
$items = rows("SELECT * FROM $table WHERE $condition ORDER BY {$sorts[$sort]} LIMIT $limit OFFSET $offset", $params);
respond(['data' => $items, 'meta' => ['page' => $page, 'limit' => $limit, 'total' => $total, 'pages' => (int) ceil($total / $limit)]]);
