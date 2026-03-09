<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

$action = $_GET['action'] ?? 'list';

if ($action === 'submit') {
    $input = api_input();
    $productId = (int)($_POST['product_id'] ?? $input['product_id'] ?? 0);
    $name = trim((string)($_POST['name'] ?? $input['name'] ?? ''));
    $email = trim((string)($_POST['email'] ?? $input['email'] ?? ''));
    $rating = (int)($_POST['rating'] ?? $input['rating'] ?? 0);
    $reviewText = trim((string)($_POST['review_text'] ?? $input['review_text'] ?? ''));

    if ($productId <= 0 || $name === '' || $rating < 1 || $rating > 5 || $reviewText === '') {
        api_send(['status' => 'error', 'message' => 'Invalid review payload'], 400);
    }

    $check = $conn->prepare('SELECT id FROM products WHERE id = ? LIMIT 1');
    $check->bind_param('i', $productId);
    $check->execute();
    if (!$check->get_result()->fetch_assoc()) {
        api_send(['status' => 'error', 'message' => 'Product not found'], 404);
    }

    $stmt = $conn->prepare(
        "INSERT INTO product_reviews (product_id, name, email, rating, review_text, status)
         VALUES (?, ?, ?, ?, ?, 'pending')"
    );
    $stmt->bind_param('issis', $productId, $name, $email, $rating, $reviewText);
    if (!$stmt->execute()) {
        api_send(['status' => 'error', 'message' => $stmt->error], 500);
    }
    api_send(['status' => 'success', 'message' => 'Review submitted and pending approval.']);
}

$productId = (int)($_GET['product_id'] ?? 0);
if ($productId <= 0) {
    api_send(['status' => 'error', 'message' => 'Product ID is required'], 400);
}

$stmt = $conn->prepare(
    "SELECT id, product_id, name, rating, review_text, created_at
     FROM product_reviews
     WHERE product_id = ? AND status = 'approved'
     ORDER BY created_at DESC"
);
$stmt->bind_param('i', $productId);
$stmt->execute();
$result = $stmt->get_result();
$reviews = [];
while ($row = $result->fetch_assoc()) {
    $reviews[] = $row;
}

api_send(['status' => 'success', 'reviews' => $reviews]);

