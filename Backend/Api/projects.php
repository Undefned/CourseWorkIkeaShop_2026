<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

requireMethod('GET');

$page  = queryInteger('page', 1, 100000);
$limit = queryInteger('limit', 12, 100);
$offset = ($page - 1) * $limit;

$where  = [];
$params = [];

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
$sort  = queryText('sort', 'default');
if (!isset($sorts[$sort])) {
    fail(422, 'Неизвестный порядок сортировки.');
}

$condition = $where ? implode(' AND ', $where) : '1=1';

$total = (int) rows("SELECT COUNT(*) AS total FROM projects p WHERE $condition", $params)[0]['total'];

$items = rows(
    "SELECT p.*, pt.id AS type_id, pt.name AS type_name, pt.slug AS type_slug,
            (SELECT pi.path FROM project_images pi WHERE pi.project_id = p.id
             ORDER BY (pi.role = 'cover') DESC, pi.sort_order, pi.id LIMIT 1) AS cover_path
     FROM projects p
     JOIN project_types pt ON pt.id = p.project_type_id
     WHERE $condition
     ORDER BY {$sorts[$sort]}
     LIMIT $limit OFFSET $offset",
    $params
);

foreach ($items as &$row) {
    $row['type'] = [
        'id'   => $row['type_id']   ?? null,
        'name' => $row['type_name'] ?? null,
        'slug' => $row['type_slug'] ?? null,
    ];
    $row['cover_url'] = $row['cover_path'] ?? null;
    unset(
        $row['type_id'], $row['type_name'], $row['type_slug'],
        $row['cover_path'], $row['project_type_id']
    );
}
unset($row);

respond(['data' => $items, 'meta' => [
    'page'  => $page,
    'limit' => $limit,
    'total' => $total,
    'pages' => (int) ceil($total / max($limit, 1)),
]]);