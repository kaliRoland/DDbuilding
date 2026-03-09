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

log_activity($conn, $_SESSION['admin_id'], 'view_order_management');

// Handle status update
$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $new_status = $_POST['status'];

    $valid_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
    if (in_array($new_status, $valid_statuses)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param('si', $new_status, $order_id);
        if ($stmt->execute()) {
            $message = 'Order status updated successfully!';
            $message_type = 'success';
            log_activity($conn, $_SESSION['admin_id'], 'update_order_status', "Order ID: $order_id, Status: $new_status");
        } else {
            $message = 'Failed to update order status.';
            $message_type = 'error';
        }
    } else {
        $message = 'Invalid status selected.';
        $message_type = 'error';
    }
}

// Handle search and filtering
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = intval($_GET['page'] ?? 1);
$per_page = 20;
$offset = ($page - 1) * $per_page;

// Build query
$query = "SELECT o.*, u.username FROM orders o LEFT JOIN users u ON o.user_id = u.id WHERE 1=1";
$count_query = "SELECT COUNT(*) as total FROM orders o WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (o.reference LIKE ? OR o.customer_email LIKE ? OR o.customer_name LIKE ? OR o.customer_phone LIKE ?)";
    $count_query .= " AND (reference LIKE ? OR customer_email LIKE ? OR customer_name LIKE ? OR customer_phone LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
}

if (!empty($status_filter)) {
    $query .= " AND o.status = ?";
    $count_query .= " AND status = ?";
    $params[] = $status_filter;
}

if (!empty($date_from)) {
    $query .= " AND DATE(o.created_at) >= ?";
    $count_query .= " AND DATE(created_at) >= ?";
    $params[] = $date_from;
}

if (!empty($date_to)) {
    $query .= " AND DATE(o.created_at) <= ?";
    $count_query .= " AND DATE(created_at) <= ?";
    $params[] = $date_to;
}

$query .= " ORDER BY o.created_at DESC LIMIT ? OFFSET ?";
$query_params = array_merge($params, [$per_page, $offset]);

// Get total count
$count_stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $types = str_repeat('s', count($params));
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$result = $count_stmt->get_result();
$total_orders = $result->fetch_assoc()['total'];
$total_pages = ceil($total_orders / $per_page);

// Get orders
$stmt = $conn->prepare($query);
if (!empty($query_params)) {
    $types = str_repeat('s', count($query_params));
    $stmt->bind_param($types, ...$query_params);
}
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    $orders[] = $row;
}

// Get status counts for filter tabs
$status_counts = [];
$status_query = "SELECT status, COUNT(*) as count FROM orders GROUP BY status";
$status_result = $conn->query($status_query);
while ($row = $status_result->fetch_assoc()) {
    $status_counts[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/brand.css">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
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
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Order Management</h1>
                <div class="text-sm text-slate-400">
                    Total Orders: <?= number_format($total_orders) ?>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="bg-<?= $message_type === 'success' ? 'green' : 'red' ?>-600 text-white p-4 rounded mb-6">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>

            <!-- Search and Filters -->
            <div class="bg-slate-800 p-6 rounded-lg mb-6">
                <form method="GET" class="space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Search</label>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                                   placeholder="Reference, email, name, phone..."
                                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white placeholder-slate-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">Status</label>
                            <select name="status" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white">
                                <option value="">All Statuses</option>
                                <option value="pending" <?= $status_filter === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="processing" <?= $status_filter === 'processing' ? 'selected' : '' ?>>Processing</option>
                                <option value="shipped" <?= $status_filter === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                                <option value="delivered" <?= $status_filter === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                                <option value="cancelled" <?= $status_filter === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">From Date</label>
                            <input type="date" name="date_from" value="<?= htmlspecialchars($date_from) ?>"
                                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-300 mb-1">To Date</label>
                            <input type="date" name="date_to" value="<?= htmlspecialchars($date_to) ?>"
                                   class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white">
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded">
                            Search & Filter
                        </button>
                        <a href="order_management.php" class="bg-slate-600 hover:bg-slate-700 text-white font-bold py-2 px-4 rounded">
                            Clear Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Status Filter Tabs -->
            <div class="flex flex-wrap gap-2 mb-6">
                <a href="?<?= http_build_query(array_merge($_GET, ['status' => ''])) ?>"
                   class="px-4 py-2 rounded <?= empty($status_filter) ? 'bg-amber-500 text-slate-900' : 'bg-slate-700 text-white hover:bg-slate-600' ?>">
                    All (<?= $total_orders ?>)
                </a>
                <?php foreach (['pending', 'processing', 'shipped', 'delivered', 'cancelled'] as $status): ?>
                    <a href="?<?= http_build_query(array_merge($_GET, ['status' => $status])) ?>"
                       class="px-4 py-2 rounded <?= $status_filter === $status ? 'bg-amber-500 text-slate-900' : 'bg-slate-700 text-white hover:bg-slate-600' ?>">
                        <?= ucfirst($status) ?> (<?= $status_counts[$status] ?? 0 ?>)
                    </a>
                <?php endforeach; ?>
            </div>

            <!-- Orders Table -->
            <div class="bg-slate-800 rounded-lg overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-slate-800 divide-y divide-slate-700">
                            <?php if (empty($orders)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-slate-400">
                                        No orders found matching your criteria.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($orders as $order): ?>
                                    <tr class="hover:bg-slate-700">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-white">#<?= htmlspecialchars($order['reference']) ?></div>
                                            <div class="text-sm text-slate-400">ID: <?= $order['id'] ?></div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="text-sm font-medium text-white"><?= htmlspecialchars($order['customer_name']) ?></div>
                                            <div class="text-sm text-slate-400"><?= htmlspecialchars($order['customer_email']) ?></div>
                                            <?php if ($order['username']): ?>
                                                <div class="text-xs text-slate-500">User: <?= htmlspecialchars($order['username']) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-white">₦<?= number_format($order['amount'], 2) ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
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
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-400">
                                            <?= date('M j, Y', strtotime($order['created_at'])) ?><br>
                                            <span class="text-xs"><?= date('g:i A', strtotime($order['created_at'])) ?></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <button onclick="openOrderModal(<?= $order['id'] ?>)"
                                                    class="text-amber-400 hover:text-amber-300 mr-3">
                                                View Details
                                            </button>
                                            <button onclick="openStatusModal(<?= $order['id'] ?>, '<?= $order['status'] ?>')"
                                                    class="text-blue-400 hover:text-blue-300">
                                                Update Status
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-6">
                    <div class="flex space-x-1">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>"
                               class="px-3 py-2 bg-slate-700 text-white rounded hover:bg-slate-600">Previous</a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"
                               class="px-3 py-2 rounded <?= $i === $page ? 'bg-amber-500 text-slate-900' : 'bg-slate-700 text-white hover:bg-slate-600' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>"
                               class="px-3 py-2 bg-slate-700 text-white rounded hover:bg-slate-600">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Order Details Modal -->
    <div id="orderModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-800 rounded-lg max-w-4xl w-full max-h-screen overflow-y-auto">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-xl font-bold text-white">Order Details</h3>
                        <button onclick="closeOrderModal()" class="text-slate-400 hover:text-white">
                            <i data-lucide="x" class="w-6 h-6"></i>
                        </button>
                    </div>
                    <div id="orderDetailsContent">
                        <!-- Order details will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status Update Modal -->
    <div id="statusModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-slate-800 rounded-lg max-w-md w-full">
                <div class="p-6">
                    <h3 class="text-xl font-bold text-white mb-4">Update Order Status</h3>
                    <form method="POST" id="statusForm">
                        <input type="hidden" name="order_id" id="statusOrderId">
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-slate-300 mb-2">New Status</label>
                            <select name="status" id="statusSelect" class="w-full px-3 py-2 bg-slate-700 border border-slate-600 rounded text-white">
                                <option value="pending">Pending</option>
                                <option value="processing">Processing</option>
                                <option value="shipped">Shipped</option>
                                <option value="delivered">Delivered</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" name="update_status" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded">
                                Update Status
                            </button>
                            <button type="button" onclick="closeStatusModal()" class="bg-slate-600 hover:bg-slate-700 text-white font-bold py-2 px-4 rounded">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openOrderModal(orderId) {
            fetch(`api/get_order_details.php?order_id=${orderId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('orderDetailsContent').innerHTML = html;
                    document.getElementById('orderModal').classList.remove('hidden');
                })
                .catch(error => {
                    console.error('Error loading order details:', error);
                    alert('Error loading order details');
                });
        }

        function closeOrderModal() {
            document.getElementById('orderModal').classList.add('hidden');
        }

        function openStatusModal(orderId, currentStatus) {
            document.getElementById('statusOrderId').value = orderId;
            document.getElementById('statusSelect').value = currentStatus;
            document.getElementById('statusModal').classList.remove('hidden');
        }

        function closeStatusModal() {
            document.getElementById('statusModal').classList.add('hidden');
        }

        // Close modals when clicking outside
        document.getElementById('orderModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeOrderModal();
            }
        });

        document.getElementById('statusModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeStatusModal();
            }
        });

        // Initialize Lucide icons
        lucide.createIcons();
    </script>
</body>
</html>


