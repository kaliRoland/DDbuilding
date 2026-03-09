<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'config/paystack_config.php';

$reference = $_GET['reference'] ?? null;
if (!$reference) {
    include 'includes/header.php';
    echo '<div class="container mx-auto p-6 text-center text-red-400">Invalid payment reference.</div>';
    include 'includes/footer.php';
    exit;
}

$paystack_url = 'https://api.paystack.co/transaction/verify/' . rawurlencode($reference);
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $paystack_url);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . PAYSTACK_SECRET_KEY,
    'Content-Type: application/json',
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$result = curl_exec($ch);
if (curl_errno($ch)) {
    $err = curl_error($ch);
    curl_close($ch);
    include 'includes/header.php';
    echo '<div class="container mx-auto p-6 text-center text-red-400">Verification failed: ' . htmlspecialchars($err) . '</div>';
    include 'includes/footer.php';
    exit;
}
curl_close($ch);

$result_decoded = json_decode($result, true);
if (!$result_decoded || empty($result_decoded['status'])) {
    include 'includes/header.php';
    echo '<div class="container mx-auto p-6 text-center text-red-400">Unable to verify payment.</div>';
    include 'includes/footer.php';
    exit;
}

if ($result_decoded['data']['status'] === 'success') {
    // Prevent duplicate order creation
    $exists_stmt = $pdo->prepare("SELECT id FROM orders WHERE reference = ?");
    $exists_stmt->execute([$reference]);
    $existing = $exists_stmt->fetchColumn();

    if (!$existing) {
        $order_details = [
            'user_id' => $_SESSION['user_id'] ?? null,
            'reference' => $reference,
            'amount' => $result_decoded['data']['amount'] / 100,
            'currency' => $result_decoded['data']['currency'],
            'payment_status' => 'completed',
            'customer_email' => $result_decoded['data']['customer']['email'],
            'customer_name' => $result_decoded['data']['metadata']['full_name'] ?? null,
            'customer_phone' => $result_decoded['data']['metadata']['phone'] ?? null,
            'customer_address' => $result_decoded['data']['metadata']['address'] ?? null,
            'cart_items' => $result_decoded['data']['metadata']['cart_items'] ?? $_SESSION['cart']
        ];
        createOrder($pdo, $order_details);
    }

    clearCart();
    header('Location: order_success.php?reference=' . urlencode($reference));
    exit;
}

include 'includes/header.php';
echo '<div class="container mx-auto p-6 text-center text-red-400">Payment was not successful. Please try again.</div>';
include 'includes/footer.php';
?>
