<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

requireMethod('GET');

$slug = requiredQuerySlug();

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

respond(['data' => $item]);
