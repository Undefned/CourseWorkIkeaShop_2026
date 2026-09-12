<?php
declare(strict_types=1);

require_once __DIR__ . '/../Scripts/bootstrap.php';
require_once __DIR__ . '/../Scripts/leads.php';

$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/', '/');
$method = $_SERVER['REQUEST_METHOD'];

// ------------------------------------------------------------------
// GET /api/image/{table}/{id} — raw BYTEA image bytes, not JSON.
// Matched first and separately because it doesn't return the usual
// {data: ...} envelope.
// ------------------------------------------------------------------
if (preg_match('~^/api/image/(project_images|product_images|collection_images)/([0-9]+)$~D', $path, $m)) {
    if ($method !== 'GET') {
        header('Allow: GET');
        fail(405, 'Метод не поддерживается.');
    }
    serveImage($m[1], (int) $m[2]);
}

$known = preg_match('~^/api/(health|projects(?:/[a-z0-9-]+)?|products(?:/[a-z0-9-]+)?|leads)$~D', $path);
if (!$known) {
    fail(404, 'Маршрут не найден.');
}

// ------------------------------------------------------------------
// POST /api/leads — contacts.html + request.html forms
// ------------------------------------------------------------------
if ($path === '/api/leads') {
    if ($method !== 'POST') {
        header('Allow: POST');
        fail(405, 'Метод не поддерживается.');
    }
    createLead();
}

// Everything else below is read-only.
if ($method !== 'GET') {
    header('Allow: GET');
    fail(405, 'Метод не поддерживается.');
}

if ($path === '/api/health') {
    rows('SELECT 1');
    respond(['data' => ['status' => 'ok']]);
}

$isProduct = str_starts_with($path, '/api/products');
$table = $isProduct ? 'products' : 'projects';
$imageTable = $isProduct ? 'product_images' : 'project_images';
$parts = explode('/', $path);

// ==================================================================
// DETAIL: /api/products/{slug} or /api/projects/{slug}
// ==================================================================
if (isset($parts[3])) {
    $slug = $parts[3];

    if ($isProduct) {
        $item = rows('SELECT * FROM products WHERE slug = :slug AND is_active = TRUE', ['slug' => $slug]);
        if (!$item) {
            fail(404, 'Товар не найден.');
        }
        $item = $item[0];

        $category = rows(
            'SELECT id, name, slug FROM product_categories WHERE id = :id',
            ['id' => $item['product_category_id']]
        )[0] ?? null;
        $item['category'] = $category;
        unset($item['product_category_id']);

        $item['images'] = rows(
            'SELECT id, alt, is_primary FROM product_images WHERE product_id = :id ORDER BY is_primary DESC, sort_order, id',
            ['id' => $item['id']]
        );
        foreach ($item['images'] as &$img) {
            $img['url'] = '/api/image/product_images/' . $img['id'];
        }
        unset($img);

        $item['related'] = rows(
            'SELECT id, name, slug, price FROM products
             WHERE product_category_id = :cat AND id != :id AND is_active = TRUE
             ORDER BY sort_order, id LIMIT 3',
            ['cat' => $category['id'] ?? 0, 'id' => $item['id']]
        );
        foreach ($item['related'] as &$rel) {
            $cover = rows(
                'SELECT id FROM product_images WHERE product_id = :id ORDER BY is_primary DESC, sort_order, id LIMIT 1',
                ['id' => $rel['id']]
            );
            $rel['cover_url'] = $cover ? '/api/image/product_images/' . $cover[0]['id'] : null;
        }
        unset($rel);
    } else {
        // NOTE: `projects` has no is_active column in schema.sql (unlike
        // `products`), so there is nothing to filter on here — see the
        // schema-gap note in the response to the user.
        $item = rows('SELECT * FROM projects WHERE slug = :slug', ['slug' => $slug]);
        if (!$item) {
            fail(404, 'Проект не найден.');
        }
        $item = $item[0];

        $type = rows(
            'SELECT id, name, slug FROM project_types WHERE id = :id',
            ['id' => $item['project_type_id']]
        )[0] ?? null;
        $item['type'] = $type;
        unset($item['project_type_id']);

        $item['images'] = rows(
            'SELECT id, alt, role FROM project_images WHERE project_id = :id ORDER BY sort_order, id',
            ['id' => $item['id']]
        );
        foreach ($item['images'] as &$img) {
            $img['url'] = '/api/image/project_images/' . $img['id'];
        }
        unset($img);

        $item['related'] = rows(
            'SELECT id, title, slug, area_m2 FROM projects
             WHERE project_type_id = :type AND id != :id
             ORDER BY sort_order, id LIMIT 3',
            ['type' => $type['id'] ?? 0, 'id' => $item['id']]
        );
        foreach ($item['related'] as &$rel) {
            $cover = rows(
                "SELECT id FROM project_images WHERE project_id = :id ORDER BY (role = 'cover') DESC, sort_order, id LIMIT 1",
                ['id' => $rel['id']]
            );
            $rel['cover_url'] = $cover ? '/api/image/project_images/' . $cover[0]['id'] : null;
        }
        unset($rel);
    }

    respond(['data' => $item]);
}

// ==================================================================
// LIST: /api/products or /api/projects
// ==================================================================
$page = queryInteger('page', 1, 100000);
$limit = queryInteger('limit', 12, 100);
$offset = ($page - 1) * $limit;
$where = [];
$params = [];

if ($isProduct) {
    $where[] = 'pr.is_active = TRUE';

    $category = queryText('category');
    if ($category !== '') {
        $where[] = 'pr.product_category_id = (SELECT id FROM product_categories WHERE slug = :category)';
        $params['category'] = $category;
    }

    $collection = queryText('collection');
    if ($collection !== '') {
        $where[] = 'pr.collection_id = (SELECT id FROM collections WHERE slug = :collection)';
        $params['collection'] = $collection;
    }

    $search = queryText('q');
    if ($search !== '') {
        $where[] = 'pr.name ILIKE :search';
        $params['search'] = '%' . $search . '%';
    }

    $sorts = ['default' => 'pr.sort_order, pr.id', 'price_asc' => 'pr.price, pr.id', 'price_desc' => 'pr.price DESC, pr.id'];
} else {
    $type = queryText('type');
    if ($type !== '') {
        $where[] = 'p.project_type_id = (SELECT id FROM project_types WHERE slug = :type)';
        $params['type'] = $type;
    }

    $featured = queryText('featured');
    if ($featured === '1' || $featured === 'true') {
        $where[] = 'p.is_featured = TRUE';
    }

    $sorts = ['default' => 'p.sort_order, p.id', 'newest' => 'p.year DESC NULLS LAST, p.id'];
}

$sort = queryText('sort', 'default');
if (!isset($sorts[$sort])) {
    fail(422, 'Неизвестный порядок сортировки.');
}

$condition = $where ? implode(' AND ', $where) : '1=1';
$total = (int) rows("SELECT COUNT(*) AS total FROM $table AS " . ($isProduct ? 'pr' : 'p') . " WHERE $condition", $params)[0]['total'];

if ($isProduct) {
    $items = rows(
        "SELECT pr.*, pc.id AS cat_id, pc.name AS cat_name, pc.slug AS cat_slug,
                (SELECT pi.id FROM product_images pi WHERE pi.product_id = pr.id
                 ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS cover_image_id
         FROM products pr
         JOIN product_categories pc ON pc.id = pr.product_category_id
         WHERE $condition
         ORDER BY {$sorts[$sort]}
         LIMIT $limit OFFSET $offset",
        $params
    );
    foreach ($items as &$row) {
        $row['category'] = ['id' => $row['cat_id'], 'name' => $row['cat_name'], 'slug' => $row['cat_slug']];
        $row['cover_url'] = $row['cover_image_id'] ? '/api/image/product_images/' . $row['cover_image_id'] : null;
        unset($row['cat_id'], $row['cat_name'], $row['cat_slug'], $row['cover_image_id'], $row['product_category_id']);
    }
    unset($row);
} else {
    $items = rows(
        "SELECT p.*, pt.id AS type_id, pt.name AS type_name, pt.slug AS type_slug,
                (SELECT pi.id FROM project_images pi WHERE pi.project_id = p.id
                 ORDER BY (pi.role = 'cover') DESC, pi.sort_order, pi.id LIMIT 1) AS cover_image_id
         FROM projects p
         JOIN project_types pt ON pt.id = p.project_type_id
         WHERE $condition
         ORDER BY {$sorts[$sort]}
         LIMIT $limit OFFSET $offset",
        $params
    );
    foreach ($items as &$row) {
        $row['type'] = ['id' => $row['type_id'], 'name' => $row['type_name'], 'slug' => $row['type_slug']];
        $row['cover_url'] = $row['cover_image_id'] ? '/api/image/' . $imageTable . '/' . $row['cover_image_id'] : null;
        unset($row['type_id'], $row['type_name'], $row['type_slug'], $row['cover_image_id'], $row['project_type_id']);
    }
    unset($row);
}

respond(['data' => $items, 'meta' => [
    'page' => $page,
    'limit' => $limit,
    'total' => $total,
    'pages' => (int) ceil($total / max($limit, 1)),
]]);
