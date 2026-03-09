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

// Verify transaction with Paystack
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

$order = null;
$verified_success = ($result_decoded['data']['status'] ?? '') === 'success';

if ($verified_success) {
    // Create order if it doesn't exist
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

    $order_stmt = $pdo->prepare("SELECT * FROM orders WHERE reference = ? LIMIT 1");
    $order_stmt->execute([$reference]);
    $order = $order_stmt->fetch();
}

include 'includes/header.php';
?>

<div class="container mx-auto p-6">
    <?php if ($verified_success && $order): ?>
        <div class="bg-slate-800 rounded-lg p-6 shadow-lg text-white">
            <h1 class="text-3xl font-bold text-emerald-400 mb-4">Payment Successful</h1>
            <p class="text-slate-300 mb-6">Your order has been confirmed and is being processed.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <h2 class="text-xl font-semibold mb-2">Order Details</h2>
                    <p><span class="text-slate-400">Reference:</span> <?= htmlspecialchars($order['reference']) ?></p>
                    <p><span class="text-slate-400">Status:</span> <?= htmlspecialchars($order['payment_status']) ?></p>
                    <p><span class="text-slate-400">Amount:</span> <?= htmlspecialchars($order['currency']) ?> <?= number_format((float)$order['amount'], 2) ?></p>
                    <p><span class="text-slate-400">Date:</span> <?= htmlspecialchars($order['created_at']) ?></p>
                </div>
                <div>
                    <h2 class="text-xl font-semibold mb-2">Customer</h2>
                    <p><span class="text-slate-400">Name:</span> <?= htmlspecialchars($order['customer_name'] ?? '') ?></p>
                    <p><span class="text-slate-400">Email:</span> <?= htmlspecialchars($order['customer_email'] ?? '') ?></p>
                    <p><span class="text-slate-400">Phone:</span> <?= htmlspecialchars($order['customer_phone'] ?? '') ?></p>
                    <p><span class="text-slate-400">Address:</span> <?= htmlspecialchars($order['customer_address'] ?? '') ?></p>
                </div>
            </div>

            <h2 class="text-xl font-semibold mb-3">Items</h2>
            <?php
                $items = json_decode($order['products_json'] ?? '[]', true);
            ?>
            <?php if (!empty($items)): ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full bg-slate-700 rounded-lg overflow-hidden text-white">
                        <thead>
                            <tr>
                                <th class="px-4 py-2 text-left">Product</th>
                                <th class="px-4 py-2 text-left">Price</th>
                                <th class="px-4 py-2 text-left">Quantity</th>
                                <th class="px-4 py-2 text-left">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="border-t border-slate-600 px-4 py-2"><?= htmlspecialchars($item['name'] ?? '') ?></td>
                                    <td class="border-t border-slate-600 px-4 py-2"><?= htmlspecialchars($order['currency']) ?> <?= number_format((float)($item['price'] ?? 0), 2) ?></td>
                                    <td class="border-t border-slate-600 px-4 py-2"><?= htmlspecialchars((string)($item['quantity'] ?? 1)) ?></td>
                                    <td class="border-t border-slate-600 px-4 py-2"><?= htmlspecialchars($order['currency']) ?> <?= number_format((float)(($item['price'] ?? 0) * ($item['quantity'] ?? 1)), 2) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <p class="text-slate-400">No items found for this order.</p>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="bg-slate-800 rounded-lg p-6 shadow-lg text-red-400 text-center">
            Payment not successful or order could not be found.
        </div>
    <?php endif; ?>
</div>

<?php if ($verified_success && $order): ?>
<?php
    $meta_items = json_decode($order['products_json'] ?? '[]', true);
    if (!is_array($meta_items)) {
        $meta_items = [];
    }
    $meta_currency = $order['currency'] ?? 'NGN';
?>
<script>
    (function() {
        if (typeof fbq !== 'function') return;
        const items = <?= json_encode($meta_items) ?>;
        const contents = Array.isArray(items) ? items.map(item => ({
            id: String(item.id ?? ''),
            quantity: Number(item.quantity || 1),
            item_price: Number(item.price || 0)
        })) : [];
        fbq('track', 'Purchase', {
            content_ids: contents.map(item => item.id).filter(Boolean),
            content_type: 'product',
            contents,
            value: Number(<?= json_encode((float)$order['amount']) ?>),
            currency: <?= json_encode($meta_currency) ?>
        });
    })();
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
