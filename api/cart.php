<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

$action = $_GET['action'] ?? 'get';

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

function cart_response(): void
{
    $items = array_values($_SESSION['cart'] ?? []);
    api_send($items);
}

if ($action === 'get') {
    cart_response();
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    api_send(['status' => 'error', 'message' => 'Invalid product ID'], 400);
}

if ($action === 'add') {
    if (isset($_SESSION['cart'][$id])) {
        $_SESSION['cart'][$id]['quantity'] = (int)$_SESSION['cart'][$id]['quantity'] + 1;
        cart_response();
    }

    $stmt = $conn->prepare('SELECT id, name, price, image_main FROM products WHERE id = ? LIMIT 1');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result ? $result->fetch_assoc() : null;
    if (!$product) {
        api_send(['status' => 'error', 'message' => 'Product not found'], 404);
    }

    $_SESSION['cart'][$id] = [
        'id' => (int)$product['id'],
        'name' => (string)$product['name'],
        'price' => (float)$product['price'],
        'image_main' => (string)($product['image_main'] ?? ''),
        'quantity' => 1
    ];
    cart_response();
}

if ($action === 'remove') {
    unset($_SESSION['cart'][$id]);
    cart_response();
}

if ($action === 'update') {
    $change = (int)($_POST['change'] ?? 0);
    if (!isset($_SESSION['cart'][$id])) {
        cart_response();
    }
    $next = (int)$_SESSION['cart'][$id]['quantity'] + $change;
    if ($next <= 0) {
        unset($_SESSION['cart'][$id]);
    } else {
        $_SESSION['cart'][$id]['quantity'] = $next;
    }
    cart_response();
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    cart_response();
}

api_send(['status' => 'error', 'message' => 'Invalid action'], 400);

