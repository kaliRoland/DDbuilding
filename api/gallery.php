<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

$action = $_GET['action'] ?? 'get_all';

if ($action !== 'get_all') {
    api_send(['status' => 'error', 'message' => 'Invalid action'], 400);
}

$result = $conn->query('SELECT * FROM gallery_items ORDER BY created_at DESC');
if (!$result) {
    api_send(['status' => 'error', 'message' => $conn->error], 500);
}

$items = [];
while ($row = $result->fetch_assoc()) {
    $imageUrls = [];

    // Newer schema: image_path_1 ... image_path_5
    for ($i = 1; $i <= 5; $i++) {
        $key = 'image_path_' . $i;
        if (!empty($row[$key])) {
            $imageUrls[] = api_asset_url((string)$row[$key]);
        }
    }

    // Legacy schema fallback: image_path
    if (empty($imageUrls) && !empty($row['image_path'])) {
        $imageUrls[] = api_asset_url((string)$row['image_path']);
    }

    $items[] = [
        'id' => isset($row['id']) ? (int)$row['id'] : 0,
        'title' => (string)($row['title'] ?? 'Installation'),
        'description' => (string)($row['description'] ?? ''),
        'youtube_url' => (string)($row['youtube_url'] ?? ''),
        'image_urls' => $imageUrls,
        'primary_image_url' => $imageUrls[0] ?? null,
        'created_at' => (string)($row['created_at'] ?? ''),
    ];
}

api_send(['status' => 'success', 'items' => $items]);

