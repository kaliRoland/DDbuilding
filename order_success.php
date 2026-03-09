<?php
session_start();
include 'includes/header.php';

$reference = $_GET['reference'] ?? 'N/A';
?>

<div class="container mx-auto p-4 text-center">
    <h1 class="text-4xl font-bold mb-6 text-green-500">Order Placed Successfully!</h1>
    <p class="text-xl text-white mb-4">Thank you for your purchase.</p>
    <p class="text-lg text-white mb-8">Your payment has been successfully processed, and your order is being prepared.</p>
    <p class="text-lg text-white mb-8">Order Reference: <span class="font-semibold text-amber-400"><?= htmlspecialchars($reference) ?></span></p>
    <a href="products.php" class="bg-amber-500 hover:bg-amber-600 text-white font-bold py-3 px-6 rounded-lg text-lg focus:outline-none focus:shadow-outline transition duration-300">Continue Shopping</a>
</div>

<?php include 'includes/footer.php'; ?>
