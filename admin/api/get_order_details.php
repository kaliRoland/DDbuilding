<?php
require_once __DIR__ . '/../includes/session.php';
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    http_response_code(403);
    echo 'Unauthorized';
    exit;
}

$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id <= 0) {
    http_response_code(400);
    echo 'Invalid order ID';
    exit;
}

// Get order details
$stmt = $conn->prepare("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    http_response_code(404);
    echo 'Order not found';
    exit;
}

// Decode products JSON
$cart_items = json_decode($order['products_json'], true);
$products = [];

if ($cart_items) {
    $product_ids = array_keys($cart_items);
    if (!empty($product_ids)) {
        $ids_string = implode(',', array_map('intval', $product_ids));
        $product_stmt = $conn->prepare("SELECT product_id, name, price FROM products WHERE product_id IN ($ids_string)");
        $product_stmt->execute();
        $result = $product_stmt->get_result();
        $products_data = [];
        while ($row = $result->fetch_assoc()) {
            $products_data[] = $row;
        }

        foreach ($products_data as $product) {
            $quantity = $cart_items[$product['product_id']] ?? 0;
            $products[] = [
                'name' => $product['name'],
                'price' => $product['price'],
                'quantity' => $quantity,
                'subtotal' => $product['price'] * $quantity
            ];
        }
    }
}
?>

<div class="space-y-6">
    <!-- Order Header -->
    <div class="bg-slate-700 p-4 rounded-lg">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <h4 class="text-sm font-medium text-slate-400">Order Reference</h4>
                <p class="text-lg font-semibold text-white">#<?= htmlspecialchars($order['reference']) ?></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-slate-400">Order Date</h4>
                <p class="text-lg font-semibold text-white"><?= date('M j, Y g:i A', strtotime($order['created_at'])) ?></p>
            </div>
            <div>
                <h4 class="text-sm font-medium text-slate-400">Status</h4>
                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full
                    <?php
                    switch ($order['status']) {
                        case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                        case 'processing': echo 'bg-blue-100 text-blue-800'; break;
                        case 'shipped': echo 'bg-purple-100 text-purple-800'; break;
                        case 'delivered': echo 'bg-green-100 text-green-800'; break;
                        case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                        default: echo 'bg-gray-100 text-gray-800';
                    }
                    ?>">
                    <?= ucfirst($order['status']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Customer Information -->
    <div class="bg-slate-700 p-4 rounded-lg">
        <h4 class="text-lg font-semibold text-white mb-3">Customer Information</h4>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-slate-400">Name</p>
                <p class="text-white"><?= htmlspecialchars($order['customer_name']) ?></p>
            </div>
            <div>
                <p class="text-sm text-slate-400">Email</p>
                <p class="text-white"><?= htmlspecialchars($order['customer_email']) ?></p>
            </div>
            <div>
                <p class="text-sm text-slate-400">Phone</p>
                <p class="text-white"><?= htmlspecialchars($order['customer_phone'] ?: 'Not provided') ?></p>
            </div>
            <div>
                <p class="text-sm text-slate-400">User Account</p>
                <p class="text-white"><?= htmlspecialchars($order['username'] ?: 'Guest') ?></p>
            </div>
        </div>
        <?php if ($order['customer_address']): ?>
            <div class="mt-4">
                <p class="text-sm text-slate-400">Address</p>
                <p class="text-white"><?= nl2br(htmlspecialchars($order['customer_address'])) ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Payment Information -->
    <div class="bg-slate-700 p-4 rounded-lg">
        <h4 class="text-lg font-semibold text-white mb-3">Payment Information</h4>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <p class="text-sm text-slate-400">Amount</p>
                <p class="text-xl font-semibold text-white">₦<?= number_format($order['amount'], 2) ?></p>
            </div>
            <div>
                <p class="text-sm text-slate-400">Currency</p>
                <p class="text-white"><?= htmlspecialchars($order['currency']) ?></p>
            </div>
            <div>
                <p class="text-sm text-slate-400">Payment Status</p>
                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                    <?= $order['payment_status'] === 'completed' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                    <?= ucfirst($order['payment_status']) ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Order Items -->
    <div class="bg-slate-700 p-4 rounded-lg">
        <h4 class="text-lg font-semibold text-white mb-3">Order Items</h4>
        <?php if (empty($products)): ?>
            <p class="text-slate-400">No product details available</p>
        <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full">
                    <thead class="bg-slate-600">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-300 uppercase">Product</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-300 uppercase">Price</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-300 uppercase">Quantity</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-slate-300 uppercase">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-600">
                        <?php foreach ($products as $item): ?>
                            <tr>
                                <td class="px-4 py-2 text-white"><?= htmlspecialchars($item['name']) ?></td>
                                <td class="px-4 py-2 text-white">₦<?= number_format($item['price'], 2) ?></td>
                                <td class="px-4 py-2 text-white"><?= $item['quantity'] ?></td>
                                <td class="px-4 py-2 text-white">₦<?= number_format($item['subtotal'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="bg-slate-600">
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-right text-white font-semibold">Total:</td>
                            <td class="px-4 py-2 text-white font-semibold">₦<?= number_format($order['amount'], 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
