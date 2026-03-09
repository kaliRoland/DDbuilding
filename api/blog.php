<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

$perPage = max(1, min(20, (int)($_GET['per_page'] ?? 3)));
$page = max(1, (int)($_GET['page'] ?? 1));
$base = getenv('BLOG_API_URL') ?: 'https://ddbuildingtech.com/blog/wp-json/wp/v2';
$url = rtrim($base, '/') . '/posts?per_page=' . $perPage . '&page=' . $page . '&_embed=1';

$ctx = stream_context_create([
    'http' => [
        'timeout' => 10,
        'ignore_errors' => true,
        'header' => "User-Agent: DDMobileApp/1.0\r\n"
    ]
]);

$response = @file_get_contents($url, false, $ctx);
if ($response === false) {
    api_send(['status' => 'error', 'message' => 'Unable to fetch blog posts'], 502);
}

$data = json_decode($response, true);
if (!is_array($data)) {
    api_send(['status' => 'error', 'message' => 'Invalid response from blog API'], 502);
}

api_send($data);

