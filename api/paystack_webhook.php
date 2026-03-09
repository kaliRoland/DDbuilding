<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../config/paystack_config.php';

// Paystack sends a signature header for verification
$input = file_get_contents('php://input');
$signature = $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] ?? '';

if (empty($signature) || empty($input)) {
    http_response_code(400);
    echo 'Invalid payload';
    exit;
}

$computed = hash_hmac('sha512', $input, PAYSTACK_SECRET_KEY);
if (!hash_equals($computed, $signature)) {
    http_response_code(400);
    echo 'Invalid signature';
    exit;
}

$event = json_decode($input, true);
if (!$event || empty($event['event'])) {
    http_response_code(400);
    echo 'Invalid event';
    exit;
}

if ($event['event'] === 'charge.success') {
    $data = $event['data'];
    $reference = $data['reference'] ?? null;

    if ($reference) {
        // Check if order exists
        $exists_stmt = $pdo->prepare("SELECT id FROM orders WHERE reference = ?");
        $exists_stmt->execute([$reference]);
        $existing = $exists_stmt->fetchColumn();

        if (!$existing) {
            $order_details = [
                'user_id' => null,
                'reference' => $reference,
                'amount' => ($data['amount'] ?? 0) / 100,
                'currency' => $data['currency'] ?? 'NGN',
                'payment_status' => 'completed',
                'customer_email' => $data['customer']['email'] ?? null,
                'customer_name' => $data['metadata']['full_name'] ?? null,
                'customer_phone' => $data['metadata']['phone'] ?? null,
                'customer_address' => $data['metadata']['address'] ?? null,
                'cart_items' => $data['metadata']['cart_items'] ?? []
            ];
            createOrder($pdo, $order_details);
        } else {
            $update_stmt = $pdo->prepare("UPDATE orders SET payment_status = 'completed' WHERE reference = ?");
            $update_stmt->execute([$reference]);
        }
    }
}

http_response_code(200);
echo 'OK';
