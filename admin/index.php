<?php
require_once __DIR__ . '/includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security_headers.php';

// If the admin is not logged in, redirect to login page
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

log_activity($conn, $_SESSION['admin_id'], 'view_dashboard');

// Fetch comprehensive statistics
$total_orders_stmt = $conn->query("SELECT COUNT(*) as total FROM orders");
$total_orders = $total_orders_stmt->fetch_assoc()['total'];

$completed_orders_stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'completed'");
$completed_orders = $completed_orders_stmt->fetch_assoc()['total'];

$pending_orders_stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
$pending_orders = $pending_orders_stmt->fetch_assoc()['total'];

$cancelled_orders_stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'cancelled'");
$cancelled_orders = $cancelled_orders_stmt->fetch_assoc()['total'];

$total_products_stmt = $conn->query("SELECT COUNT(*) as total FROM products");
$total_products = $total_products_stmt->fetch_assoc()['total'];

$total_users_stmt = $conn->query("SELECT COUNT(*) as total FROM users");
$total_users = $total_users_stmt->fetch_assoc()['total'];

$total_admins_stmt = $conn->query("SELECT COUNT(*) as total FROM admins");
$total_admins = $total_admins_stmt->fetch_assoc()['total'];

// Recent orders (last 7 days)
$recent_orders_stmt = $conn->query("SELECT COUNT(*) as total FROM orders WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$recent_orders = $recent_orders_stmt->fetch_assoc()['total'];

// Total revenue
$revenue_stmt = $conn->query("SELECT SUM(amount) as total FROM orders WHERE status = 'completed'");
$revenue = $revenue_stmt->fetch_assoc()['total'] ?? 0;

// Fetch product counts by category
$product_counts_stmt = $conn->query("SELECT category, COUNT(*) as count FROM products GROUP BY category ORDER BY count DESC LIMIT 5");
$product_counts = [];
while($row = $product_counts_stmt->fetch_assoc()) {
    $product_counts[] = $row;
}

// Fetch recent orders
$recent_orders_list_stmt = $conn->query("SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id ORDER BY o.created_at DESC LIMIT 5");
$recent_orders_list = [];
while($row = $recent_orders_list_stmt->fetch_assoc()) {
    $recent_orders_list[] = $row;
}

// Fetch user activity log
$activity_log_stmt = $conn->query("SELECT ual.*, u.username FROM user_activity_log ual LEFT JOIN users u ON ual.user_id = u.id ORDER BY ual.timestamp DESC LIMIT 10");
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
    <link rel="stylesheet" href="css/brand.css">
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-5NVJVRF7');</script>
    <!-- End Google Tag Manager -->
</head>
<body class="bg-slate-900 text-slate-100">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5NVJVRF7"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->

    <div class="flex min-h-screen">
        <?php include 'includes/sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 p-10">
            <h1 class="text-3xl font-bold text-white mb-6">Dashboard</h1>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-slate-400 text-sm font-medium">Total Orders</h2>
                    <p class="text-3xl font-bold text-white mt-1"><?= $total_orders ?></p>
                    <p class="text-slate-500 text-xs mt-1">+<?= $recent_orders ?> this week</p>
                </div>
                <div class="bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-slate-400 text-sm font-medium">Completed Orders</h2>
                    <p class="text-3xl font-bold text-green-400 mt-1"><?= $completed_orders ?></p>
                    <p class="text-slate-500 text-xs mt-1">Revenue: ₦<?= number_format($revenue, 2) ?></p>
                </div>
                <div class="bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-slate-400 text-sm font-medium">Pending Orders</h2>
                    <p class="text-3xl font-bold text-amber-400 mt-1"><?= $pending_orders ?></p>
                    <p class="text-slate-500 text-xs mt-1">Awaiting processing</p>
                </div>
                <div class="bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-slate-400 text-sm font-medium">Total Products</h2>
                    <p class="text-3xl font-bold text-blue-400 mt-1"><?= $total_products ?></p>
                    <p class="text-slate-500 text-xs mt-1">In catalog</p>
                </div>
            </div>

            <!-- Secondary Stats -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div class="bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-slate-400 text-sm font-medium">Total Users</h2>
                    <p class="text-2xl font-bold text-purple-400 mt-1"><?= $total_users ?></p>
                    <p class="text-slate-500 text-xs mt-1">Registered customers</p>
                </div>
                <div class="bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-slate-400 text-sm font-medium">Admin Users</h2>
                    <p class="text-2xl font-bold text-red-400 mt-1"><?= $total_admins ?></p>
                    <p class="text-slate-500 text-xs mt-1">System administrators</p>
                </div>
                <div class="bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-slate-400 text-sm font-medium">Cancelled Orders</h2>
                    <p class="text-2xl font-bold text-red-400 mt-1"><?= $cancelled_orders ?></p>
                    <p class="text-slate-500 text-xs mt-1">Order cancellations</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Product Categories -->
                <div class="lg:col-span-1 bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-xl font-bold text-white mb-4">Top Product Categories</h2>
                    <div class="space-y-4">
                        <?php foreach($product_counts as $count): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-slate-300"><?= htmlspecialchars($count['category']) ?></span>
                                <span class="bg-slate-700 text-white text-sm font-bold px-2 py-1 rounded-full"><?= $count['count'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Recent Orders -->
                <div class="lg:col-span-1 bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-xl font-bold text-white mb-4">Recent Orders</h2>
                    <div class="space-y-3">
                        <?php foreach($recent_orders_list as $order): ?>
                            <div class="border-b border-slate-700 pb-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <p class="text-white font-medium">Order #<?= $order['id'] ?></p>
                                        <p class="text-slate-400 text-sm">₦<?= number_format($order['amount'], 2) ?></p>
                                    </div>
                                    <span class="text-xs px-2 py-1 rounded-full <?php
                                        echo $order['status'] === 'completed' ? 'bg-green-600' :
                                             ($order['status'] === 'pending' ? 'bg-amber-600' : 'bg-red-600');
                                    ?> text-white">
                                        <?= ucfirst($order['status']) ?>
                                    </span>
                                </div>
                                <p class="text-slate-500 text-xs mt-1">
                                    <?= date('M j, H:i', strtotime($order['created_at'])) ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- User Activity Log -->
                <div class="lg:col-span-1 bg-slate-800 p-6 rounded-lg">
                    <h2 class="text-xl font-bold text-white mb-4">Recent User Activity</h2>
                    <?php if ($_SESSION['admin_role'] === 'super'): ?>
                    <a href="api/download_activity.php" class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded mb-4">
                        Download All Activity
                    </a>
                    <?php endif; ?>
                    <div class="space-y-3">
                        <?php foreach($activity_logs as $log): ?>
                            <div class="flex items-center justify-between p-2 bg-slate-700/50 rounded">
                                <div>
                                    <span class="font-semibold text-slate-300"><?= htmlspecialchars($log['action']) ?></span>
                                    <span class="text-sm text-slate-500 ml-2">User: <?= htmlspecialchars($log['username'] ?? 'N/A') ?></span>
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




