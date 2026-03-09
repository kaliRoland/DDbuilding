<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

$action = $_GET['action'] ?? 'list';
$user = api_require_user($conn);
$userId = (int)$user['id'];

if ($action === 'create') {
    $input = api_input();
    $items = $input['cart_items'] ?? [];
    if (!is_array($items) || count($items) === 0) {
        api_send(['status' => 'error', 'message' => 'Cart items are required'], 400);
    }

    $amount = 0.0;
    foreach ($items as $item) {
        $price = (float)($item['price'] ?? 0);
        $qty = max(1, (int)($item['quantity'] ?? 1));
        $amount += $price * $qty;
    }
    if ($amount <= 0) {
        api_send(['status' => 'error', 'message' => 'Invalid cart total'], 400);
    }

    $reference = 'APP_' . date('YmdHis') . '_' . bin2hex(random_bytes(3));
    $currency = 'NGN';
    $paymentStatus = 'pending';
    $status = 'pending';
    $email = (string)($input['customer_email'] ?? $user['email'] ?? '');
    $name = (string)($input['customer_name'] ?? $user['username'] ?? '');
    $phone = (string)($input['customer_phone'] ?? '');
    $address = (string)($input['customer_address'] ?? '');
    $productsJson = json_encode($items);

    $stmt = $conn->prepare(
        'INSERT INTO orders
         (user_id, reference, amount, currency, payment_status, customer_email, customer_name, customer_phone, customer_address, products_json, status, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->bind_param(
        'isdssssssss',
        $userId,
        $reference,
        $amount,
        $currency,
        $paymentStatus,
        $email,
        $name,
        $phone,
        $address,
        $productsJson,
        $status
    );
    if (!$stmt->execute()) {
        api_send(['status' => 'error', 'message' => $stmt->error], 500);
    }
    api_send(['status' => 'success', 'order_id' => (int)$stmt->insert_id, 'reference' => $reference], 201);
}

if ($action === 'get') {
    $orderId = (int)($_GET['id'] ?? 0);
    if ($orderId <= 0) {
        api_send(['status' => 'error', 'message' => 'Invalid order ID'], 400);
    }
    $stmt = $conn->prepare(
        'SELECT id, reference, amount, currency, payment_status, status, customer_name, customer_email, customer_phone, customer_address, products_json, created_at
         FROM orders
         WHERE id = ? AND user_id = ?
         LIMIT 1'
    );
    $stmt->bind_param('ii', $orderId, $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $order = $result ? $result->fetch_assoc() : null;
    if (!$order) {
        api_send(['status' => 'error', 'message' => 'Order not found'], 404);
    }
    $order['cart_items'] = json_decode((string)($order['products_json'] ?? '[]'), true) ?: [];
    api_send(['status' => 'success', 'order' => $order]);
}

$stmt = $conn->prepare(
    'SELECT id, reference, amount, currency, payment_status, status, customer_name, customer_email, created_at
     FROM orders
     WHERE user_id = ?
     ORDER BY created_at DESC'
);
$stmt->bind_param('i', $userId);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}
api_send(['status' => 'success', 'orders' => $orders]);

