<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// If the admin is not logged in, redirect to login page
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// If the admin is not a super user, redirect to dashboard
if ($_SESSION['admin_role'] !== 'super') {
    header('Location: index.php');
    exit;
}

log_activity($conn, $_SESSION['admin_id'], 'view_manage_users');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-800 p-6">
            <img src="../uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-8 mb-8">
            <nav>
                <a href="index.php" class="block py-2 px-4 rounded hover:bg-slate-700">Dashboard</a>
                <a href="products.php" class="block py-2 px-4 rounded hover:bg-slate-700">Products</a>
                <a href="add_product.php" class="block py-2 px-4 rounded hover:bg-slate-700">Add New Product</a>
                <a href="#" class="block py-2 px-4 rounded hover:bg-slate-700">Order Tracking</a>
                <a href="manage_users.php" class="block py-2 px-4 rounded bg-amber-500 text-slate-900">Manage Users</a>
                <a href="logout.php" class="block py-2 px-4 rounded hover:bg-slate-700 mt-4">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-10">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Manage Users</h1>
                <button id="add-user-btn" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">
                    Add New User
                </button>
            </div>

            <div class="bg-slate-800 rounded-lg overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Username</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Created At</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="users-tbody" class="divide-y divide-slate-700">
                        <!-- Users will be loaded here by JavaScript -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <!-- Add/Edit User Modal -->
    <div id="user-modal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-slate-800 p-8 rounded-lg shadow-lg w-full max-w-lg">
            <h2 id="modal-title" class="text-2xl font-bold mb-6">Add User</h2>
            <form id="user-form">
                <input type="hidden" id="user-id" name="id">
                <div class="mb-4">
                    <label for="username" class="block text-slate-400 mb-2">Username</label>
                    <input type="text" id="username" name="username" required class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                </div>
                <div class="mb-4">
                    <label for="email" class="block text-slate-400 mb-2">Email</label>
                    <input type="email" id="email" name="email" required class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                </div>
                <div class="mb-4">
                    <label for="role" class="block text-slate-400 mb-2">Role</label>
                    <select id="role" name="role" required class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                        <option value="admin">Admin</option>
                        <option value="super">Super Admin</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label for="password" class="block text-slate-400 mb-2">Password</label>
                    <input type="password" id="password" name="password" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    <p class="text-xs text-slate-500 mt-1">Leave blank to keep current password.</p>
                </div>
                <div class="flex justify-end gap-4">
                    <button type="button" id="cancel-btn" class="bg-slate-600 hover:bg-slate-500 text-white font-bold py-2 px-4 rounded transition">
                        Cancel
                    </button>
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">
                        Save User
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/users.js"></script>
</body>
</html>
