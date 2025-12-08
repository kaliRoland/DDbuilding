<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// If the admin is not logged in, redirect to login page
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

log_activity($conn, $_SESSION['admin_id'], 'view_dashboard');

// Fetch order statistics
$total_orders_stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $total_orders_stmt->fetch_assoc()['total'];

$completed_orders_stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'completed'");
$completed_orders = $completed_orders_stmt->fetch_assoc()['total'];

$pending_orders_stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pending_orders = $pending_orders_stmt->fetch_assoc()['total'];

// Fetch product counts by category
$product_counts_stmt = $conn->query("SELECT category, COUNT(*) as count FROM products GROUP BY category");
$product_counts = [];
while($row = $product_counts_stmt->fetch_assoc()) {
    $product_counts[] = $row;
}

// Fetch user activity log
$activity_log_stmt = $conn->query("SELECT * FROM user_activity_log ORDER BY timestamp DESC LIMIT 10");
$activity_logs = [];
while($row = $activity_log_stmt->fetch_assoc()) {
    $activity_logs[] = $row;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-800 p-6">
            <img src="../uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-8 mb-8">
            <nav>
                <a href="index.php" class="block py-2 px-4 rounded bg-amber-500 text-slate-900">Dashboard</a>
                <a href="products.php" class="block py-2 px-4 rounded hover:bg-slate-700">Products</a>
                <a href="add_product.php" class="block py-2 px-4 rounded hover:bg-slate-700" id="add-product-btn">Add New Product</a>
                <a href="gallery.php" class="block py-2 px-4 rounded hover:bg-slate-700">Manage Gallery</a>
                <a href="#" class="block py-2 px-4 rounded hover:bg-slate-700">Order Tracking</a>
                <?php if ($_SESSION['admin_role'] === 'super'): ?>
                    <a href="manage_users.php" class="block py-2 px-4 rounded hover:bg-slate-700">Manage Users</a>
                <?php endif; ?>
                <a href="logout.php" class="block py-2 px-4 rounded hover:bg-slate-700 mt-4">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-10">
            <h1 class="text-3xl font-bold text-white mb-6">Dashboard</h1>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-slate-400 text-sm font-medium">Total Orders</h2>
                    <p class="text-3xl font-bold text-white mt-1"><?= $total_orders ?></p>
                </div>
                <div class="bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-slate-400 text-sm font-medium">Completed Orders</h2>
                    <p class="text-3xl font-bold text-green-400 mt-1"><?= $completed_orders ?></p>
                </div>
                <div class="bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-slate-400 text-sm font-medium">Pending Orders</h2>
                    <p class="text-3xl font-bold text-amber-400 mt-1"><?= $pending_orders ?></p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Product Categories -->
                <div class="lg:col-span-1 bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-xl font-bold text-white mb-4">Products by Category</h2>
                    <div class="space-y-4">
                        <?php foreach($product_counts as $count): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-300"><?= htmlspecialchars($count['category']) ?></span>
                                <span class="bg-slate-700 text-white text-sm font-bold px-2 py-1 rounded-full"><?= $count['count'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- User Activity Log -->
                <div class="lg:col-span-2 bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-xl font-bold text-white mb-4">Recent User Activity</h2>
                    <a href="api/download_activity.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded mb-4">
                        Download All Activity
                    </a>
                    <div class="space-y-3">
                        <?php foreach($activity_logs as $log): ?>
                            <div class="flex items-center justify-between p-2 bg-slate-700/50 rounded">
                                <div>
                                    <span class="font-semibold text-slate-300"><?= htmlspecialchars($log['action']) ?></span>
                                    <span class="text-sm text-slate-500 ml-2">User ID: <?= htmlspecialchars($log['user_id']) ?></span>
                                </div>
                                <span class="text-xs text-slate-500"><?= date('M d, Y H:i', strtotime($log['timestamp'])) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

        </main>
    </div>
</body>
</html>
