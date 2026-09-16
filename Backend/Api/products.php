<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

requireMethod('GET');

$page  = queryInteger('page', 1, 100000);
$limit = queryInteger('limit', 12, 100);
$offset = ($page - 1) * $limit;

$where  = ['pr.is_active = TRUE'];
$params = [];

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

$sorts = [
    'default'    => 'pr.sort_order, pr.id',
    'price_asc'  => 'pr.price, pr.id',
    'price_desc' => 'pr.price DESC, pr.id',
];
$sort = queryText('sort', 'default');
if (!isset($sorts[$sort])) {
    fail(422, 'Неизвестный порядок сортировки.');
}

$condition = implode(' AND ', $where);

$total = (int) rows("SELECT COUNT(*) AS total FROM products pr WHERE $condition", $params)[0]['total'];

$items = rows(
    "SELECT pr.*, pc.id AS cat_id, pc.name AS cat_name, pc.slug AS cat_slug,
            (SELECT pi.path FROM product_images pi WHERE pi.product_id = pr.id
             ORDER BY pi.is_primary DESC, pi.sort_order, pi.id LIMIT 1) AS cover_path
     FROM products pr
     JOIN product_categories pc ON pc.id = pr.product_category_id
     WHERE $condition
     ORDER BY {$sorts[$sort]}
     LIMIT $limit OFFSET $offset",
    $params
);

foreach ($items as &$row) {
    $row['category'] = [
        'id'   => $row['cat_id']   ?? null,
        'name' => $row['cat_name'] ?? null,
        'slug' => $row['cat_slug'] ?? null,
    ];
    $row['cover_url'] = $row['cover_path'] ?? null;
    unset(
        $row['cat_id'], $row['cat_name'], $row['cat_slug'],
        $row['cover_path'], $row['product_category_id']
    );
}
unset($row);

respond(['data' => $items, 'meta' => [
    'page'  => $page,
    'limit' => $limit,
    'total' => $total,
    'pages' => (int) ceil($total / max($limit, 1)),
]]);