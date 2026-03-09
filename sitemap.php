<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';

header('Content-Type: application/xml; charset=UTF-8');

function sitemap_base_url(): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return $scheme . '://' . $host;
}

function sitemap_loc(string $path): string
{
    return sitemap_base_url() . '/' . ltrim($path, '/');
}

function xml_escape(string $value): string
{
    return htmlspecialchars($value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
}

$urls = [];
$today = gmdate('Y-m-d');

$static_pages = [
    ['loc' => sitemap_loc(''), 'changefreq' => 'weekly', 'priority' => '1.0', 'lastmod' => $today],
    ['loc' => sitemap_loc('products.php'), 'changefreq' => 'daily', 'priority' => '0.9', 'lastmod' => $today],
    ['loc' => sitemap_loc('blog.php'), 'changefreq' => 'weekly', 'priority' => '0.6', 'lastmod' => $today],
    ['loc' => sitemap_loc('gallery.php'), 'changefreq' => 'weekly', 'priority' => '0.5', 'lastmod' => $today],
    ['loc' => sitemap_loc('about.php'), 'changefreq' => 'monthly', 'priority' => '0.5', 'lastmod' => $today],
    ['loc' => sitemap_loc('contact.php'), 'changefreq' => 'monthly', 'priority' => '0.6', 'lastmod' => $today],
];
$urls = array_merge($urls, $static_pages);

$has_updated_at = false;
$updated_col_check = $conn->query("SHOW COLUMNS FROM products LIKE 'updated_at'");
if ($updated_col_check && $updated_col_check->num_rows > 0) {
    $has_updated_at = true;
}

$product_sql = $has_updated_at
    ? "SELECT id, COALESCE(updated_at, created_at, NOW()) AS lastmod FROM products ORDER BY id DESC"
    : "SELECT id, COALESCE(created_at, NOW()) AS lastmod FROM products ORDER BY id DESC";

$product_query = $conn->query($product_sql);
if ($product_query) {
    while ($row = $product_query->fetch_assoc()) {
        $lastmod = !empty($row['lastmod']) ? gmdate('Y-m-d', strtotime((string)$row['lastmod'])) : $today;
        $urls[] = [
            'loc' => sitemap_loc('product.php?id=' . (int)$row['id']),
            'changefreq' => 'weekly',
            'priority' => '0.8',
            'lastmod' => $lastmod
        ];
    }
}

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach ($urls as $url) {
    echo "  <url>\n";
    echo '    <loc>' . xml_escape($url['loc']) . "</loc>\n";
    echo '    <lastmod>' . xml_escape($url['lastmod']) . "</lastmod>\n";
    echo '    <changefreq>' . xml_escape($url['changefreq']) . "</changefreq>\n";
    echo '    <priority>' . xml_escape($url['priority']) . "</priority>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
