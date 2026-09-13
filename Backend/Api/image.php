<?php

declare(strict_types=1);

require __DIR__ . '/lib/bootstrap.php';

requireMethod('GET');

$allowed = ['project_images', 'product_images', 'collection_images'];
$table = $_GET['table'] ?? '';
$id = requiredQueryInteger('id');

if (!in_array($table, $allowed, true)) {
    fail(404, 'Изображение не найдено.');
}

$found = rows("SELECT data, mime_type, updated_at FROM $table WHERE id = :id", ['id' => $id]);
if (!$found) {
    fail(404, 'Изображение не найдено.');
}
$image = $found[0];

$data = $image['data'];
if (is_resource($data)) {
    $data = stream_get_contents($data);
}

$etag = '"' . md5($table . ':' . $id . ':' . $image['updated_at']) . '"';
$lastModified = gmdate('D, d M Y H:i:s', strtotime($image['updated_at'])) . ' GMT';

header('Content-Type: ' . $image['mime_type']);
header('Content-Length: ' . strlen($data));
header('Cache-Control: public, max-age=86400');
header('ETag: ' . $etag);
header('Last-Modified: ' . $lastModified);

if (($_SERVER['HTTP_IF_NONE_MATCH'] ?? '') === $etag) {
    http_response_code(304);
    exit;
}

echo $data;
