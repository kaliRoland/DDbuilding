<?php
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Check if cart is empty
if (empty($_SESSION['cart'])) {
    header('Location: products.php'); // Redirect to products page if cart is empty
    exit();
}

$cart_items = [];
$total_amount = 0;

// Build cart items from session (cart holds full product rows + quantity)
if (!empty($_SESSION['cart'])) {
    foreach ($_SESSION['cart'] as $item) {
        $quantity = (int)($item['quantity'] ?? 1);
        $price = (float)($item['price'] ?? 0);
        $subtotal = $price * $quantity;
        $total_amount += $subtotal;
        $cart_items[] = [
            'id' => $item['id'],
            'name' => $item['name'],
            'price' => $price,
            'quantity' => $quantity,
            'subtotal' => $subtotal
        ];
    }
}

include 'includes/header.php';
?>

<div class="container mx-auto p-4">
    <h1 class="text-3xl font-bold mb-6 text-white">Checkout</h1>

    <div class="bg-slate-800 p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-2xl font-semibold mb-4 text-white">Your Order</h2>
        <?php if (!empty($cart_items)): ?>
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
                        <?php foreach ($cart_items as $item): ?>
                            <tr>
                                <td class="border-t border-slate-600 px-4 py-2"><?= htmlspecialchars($item['name']) ?></td>
                                <td class="border-t border-slate-600 px-4 py-2">NGN<?= number_format($item['price'], 2) ?></td>
                                <td class="border-t border-slate-600 px-4 py-2"><?= $item['quantity'] ?></td>
                                <td class="border-t border-slate-600 px-4 py-2">NGN<?= number_format($item['subtotal'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="3" class="border-t border-slate-600 px-4 py-2 text-right font-semibold">Total:</td>
                            <td class="border-t border-slate-600 px-4 py-2 font-semibold">NGN<?= number_format($total_amount, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        <?php else: ?>
            <p class="text-white">Your cart is empty.</p>
        <?php endif; ?>
    </div>

    <div class="bg-slate-800 p-6 rounded-lg shadow-lg">
        <h2 class="text-2xl font-semibold mb-4 text-white">Customer Information</h2>
        <form id="paymentForm">
            <div class="mb-4">
                <label for="name" class="block text-white text-sm font-bold mb-2">Full Name:</label>
                <input type="text" id="name" name="name" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-white leading-tight focus:outline-none focus:shadow-outline bg-slate-700 border-slate-600">
            </div>
            <div class="mb-4">
                <label for="email" class="block text-white text-sm font-bold mb-2">Email:</label>
                <input type="email" id="email" name="email" required
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-white leading-tight focus:outline-none focus:shadow-outline bg-slate-700 border-slate-600">
            </div>
            <div class="mb-6">
                <label for="phone" class="block text-white text-sm font-bold mb-2">Phone:</label>
                <input type="tel" id="phone" name="phone"
                       class="shadow appearance-none border rounded w-full py-2 px-3 text-white leading-tight focus:outline-none focus:shadow-outline bg-slate-700 border-slate-600">
            </div>
            <div class="mb-6">
                <label for="address" class="block text-white text-sm font-bold mb-2">Address:</label>
                <textarea id="address" name="address"
                          class="shadow appearance-none border rounded w-full py-2 px-3 text-white leading-tight focus:outline-none focus:shadow-outline bg-slate-700 border-slate-600"></textarea>
            </div>
            <button type="submit" id="pay-button"
                    class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                Pay Now (NGN<?= number_format($total_amount, 2) ?>)
            </button>
            <p id="payment-message" class="text-sm text-slate-400 mt-3"></p>
        </form>
    </div>
</div>

<script src="https://js.paystack.co/v1/inline.js"></script>
<script>
    const metaCartItems = <?= json_encode($cart_items) ?>;
    const metaCartValue = <?= json_encode((float)$total_amount) ?>;
    if (typeof fbq === 'function' && Array.isArray(metaCartItems) && metaCartItems.length) {
        const contents = metaCartItems.map(item => ({
            id: String(item.id),
            quantity: Number(item.quantity || 1),
            item_price: Number(item.price || 0)
        }));
        fbq('track', 'InitiateCheckout', {
            content_ids: metaCartItems.map(item => String(item.id)),
            content_type: 'product',
            contents,
            value: Number(metaCartValue || 0),
            currency: 'NGN'
        });
    }

    const totalAmount = <?= json_encode((float)$total_amount) ?>;
    const paymentForm = document.getElementById('paymentForm');
    const paymentMessage = document.getElementById('payment-message');

    paymentForm.addEventListener('submit', async function(event) {
        event.preventDefault();

        const name = document.getElementById('name').value || '';
        const email = document.getElementById('email').value || '';
        const phone = document.getElementById('phone').value || '';
        const address = document.getElementById('address').value || '';

        if (paymentMessage) {
            paymentMessage.textContent = 'Initializing payment...';
            paymentMessage.className = 'text-sm text-slate-400 mt-3';
        }

        try {
            const response = await fetch('api/paystack.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    amount: Math.round(totalAmount * 100),
                    email,
                    name,
                    phone,
                    address
                })
            });
            const data = await response.json();
            if (data.status === 'success' && data.access_code && data.public_key) {
                const handler = PaystackPop.setup({
                    key: data.public_key,
                    email,
                    amount: Math.round(totalAmount * 100),
                    ref: data.reference,
                    access_code: data.access_code,
                    metadata: {
                        custom_fields: [
                            { display_name: 'Full Name', variable_name: 'full_name', value: name },
                            { display_name: 'Phone', variable_name: 'phone', value: phone },
                            { display_name: 'Address', variable_name: 'address', value: address }
                        ]
                    },
                    callback: function(response) {
                        window.location.href = `payment_status.php?reference=${encodeURIComponent(response.reference)}`;
                    },
                    onClose: function() {
                        if (paymentMessage) {
                            paymentMessage.textContent = 'Payment popup closed.';
                            paymentMessage.className = 'text-sm text-amber-400 mt-3';
                        }
                    }
                });
                handler.openIframe();
            } else {
                if (paymentMessage) {
                    paymentMessage.textContent = data.message || 'Failed to initialize payment.';
                    paymentMessage.className = 'text-sm text-red-400 mt-3';
                }
            }
        } catch (err) {
            if (paymentMessage) {
                paymentMessage.textContent = 'Payment initialization failed. Please try again.';
                paymentMessage.className = 'text-sm text-red-400 mt-3';
            }
        }
    });
</script>

<?php include 'includes/footer.php'; ?>

