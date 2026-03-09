<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

$action = $_GET['action'] ?? 'get_all';

if ($action === 'get_featured') {
    $limit = max(1, min(20, (int)($_GET['limit'] ?? 8)));
    $sql = "SELECT p.*,
            COALESCE(r.avg_rating, 0) AS avg_rating,
            COALESCE(r.review_count, 0) AS review_count
            FROM products p
            LEFT JOIN (
                SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
                FROM product_reviews
                WHERE status = 'approved'
                GROUP BY product_id
            ) r ON r.product_id = p.id
            WHERE p.is_featured = 1
            ORDER BY p.created_at DESC
            LIMIT ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $row['image_main_url'] = !empty($row['image_main']) ? api_asset_url($row['image_main']) : null;
        $items[] = $row;
    }
    api_send($items);
}

if ($action === 'get_one') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) {
        api_send(['status' => 'error', 'message' => 'Invalid product ID'], 400);
    }

    $stmt = $conn->prepare(
        "SELECT p.*,
         COALESCE(r.avg_rating, 0) AS avg_rating,
         COALESCE(r.review_count, 0) AS review_count
         FROM products p
         LEFT JOIN (
             SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
             FROM product_reviews
             WHERE status = 'approved'
             GROUP BY product_id
         ) r ON r.product_id = p.id
         WHERE p.id = ?
         LIMIT 1"
    );
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result ? $result->fetch_assoc() : null;
    if (!$product) {
        api_send(['status' => 'error', 'message' => 'Product not found'], 404);
    }
    $product['image_main_url'] = !empty($product['image_main']) ? api_asset_url($product['image_main']) : null;
    foreach (['image_1', 'image_2', 'image_3'] as $k) {
        $product[$k . '_url'] = !empty($product[$k]) ? api_asset_url($product[$k]) : null;
    }

    $reviews = [];
    $revStmt = $conn->prepare(
        "SELECT id, product_id, name, rating, review_text, created_at
         FROM product_reviews
         WHERE product_id = ? AND status = 'approved'
         ORDER BY created_at DESC
         LIMIT 20"
    );
    if ($revStmt) {
        $revStmt->bind_param('i', $id);
        $revStmt->execute();
        $revResult = $revStmt->get_result();
        while ($row = $revResult->fetch_assoc()) {
            $reviews[] = $row;
        }
    }

    $related = [];
    $relStmt = $conn->prepare(
        "SELECT id, name, category, price, image_main
         FROM products
         WHERE category = ? AND id != ?
         ORDER BY created_at DESC
         LIMIT 6"
    );
    if ($relStmt) {
        $relStmt->bind_param('si', $product['category'], $id);
        $relStmt->execute();
        $relResult = $relStmt->get_result();
        while ($row = $relResult->fetch_assoc()) {
            $row['image_main_url'] = !empty($row['image_main']) ? api_asset_url($row['image_main']) : null;
            $related[] = $row;
        }
    }

    api_send(['status' => 'success', 'product' => $product, 'reviews' => $reviews, 'related' => $related]);
}

$search = trim((string)($_GET['search'] ?? ''));
$category = trim((string)($_GET['category'] ?? ''));
$maxPrice = isset($_GET['max_price']) ? (float)$_GET['max_price'] : null;
$sort = (string)($_GET['sort'] ?? 'created_at_desc');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = max(1, min(50, (int)($_GET['limit'] ?? 12)));
$offset = ($page - 1) * $limit;

$where = [];
$params = [];
$types = '';

if ($search !== '') {
    $where[] = '(p.name LIKE ? OR p.description LIKE ? OR p.tags LIKE ?)';
    $term = '%' . $search . '%';
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $types .= 'sss';
}
if ($category !== '') {
    if (ctype_digit($category)) {
        $where[] = 'p.category_id = ?';
        $params[] = (int)$category;
        $types .= 'i';
    } else {
        $where[] = 'p.category = ?';
        $params[] = $category;
        $types .= 's';
    }
}
if ($maxPrice !== null && $maxPrice > 0) {
    $where[] = 'p.price <= ?';
    $params[] = $maxPrice;
    $types .= 'd';
}

$whereSql = empty($where) ? '' : (' WHERE ' . implode(' AND ', $where));
$orderSql = match ($sort) {
    'price_asc' => ' ORDER BY p.price ASC',
    'price_desc' => ' ORDER BY p.price DESC',
    'name_asc' => ' ORDER BY p.name ASC',
    'name_desc' => ' ORDER BY p.name DESC',
    default => ' ORDER BY p.created_at DESC'
};

$countSql = "SELECT COUNT(*) AS total FROM products p" . $whereSql;
$countStmt = $conn->prepare($countSql);
if (!$countStmt) {
    api_send(['status' => 'error', 'message' => $conn->error], 500);
}
if ($types !== '') {
    api_bind_params($countStmt, $types, $params);
}
$countStmt->execute();
$countResult = $countStmt->get_result();
$total = (int)($countResult->fetch_assoc()['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $limit));

$sql = "SELECT p.*,
        COALESCE(r.avg_rating, 0) AS avg_rating,
        COALESCE(r.review_count, 0) AS review_count
        FROM products p
        LEFT JOIN (
            SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
            FROM product_reviews
            WHERE status = 'approved'
            GROUP BY product_id
        ) r ON r.product_id = p.id"
    . $whereSql . $orderSql . " LIMIT ? OFFSET ?";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    api_send(['status' => 'error', 'message' => $conn->error], 500);
}
$paramsWithPaging = $params;
$typesWithPaging = $types . 'ii';
$paramsWithPaging[] = $limit;
$paramsWithPaging[] = $offset;
api_bind_params($stmt, $typesWithPaging, $paramsWithPaging);
$stmt->execute();
$result = $stmt->get_result();
$products = [];
while ($row = $result->fetch_assoc()) {
    $row['image_main_url'] = !empty($row['image_main']) ? api_asset_url($row['image_main']) : null;
    $products[] = $row;
}

$catResult = $conn->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id, name");
$categories = [];
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row;
    }
}

api_send([
    'status' => 'success',
    'products' => $products,
    'categories' => $categories,
    'pagination' => [
        'page' => $page,
        'limit' => $limit,
        'total' => $total,
        'total_pages' => $totalPages
    ]
]);

