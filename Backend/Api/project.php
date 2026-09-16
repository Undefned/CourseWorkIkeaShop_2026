<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

requireMethod('GET');

$slug = requiredQuerySlug();

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
    'SELECT id, path, alt, role FROM project_images WHERE project_id = :id ORDER BY sort_order, id',
    ['id' => $item['id']]
);
foreach ($item['images'] as &$img) {
    $img['url'] = $img['path'];
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
        "SELECT path FROM project_images WHERE project_id = :id
         ORDER BY (role = 'cover') DESC, sort_order, id LIMIT 1",
        ['id' => $rel['id']]
    );
    $rel['cover_url'] = $cover ? $cover[0]['path'] : null;
}
unset($rel);

respond(['data' => $item]);