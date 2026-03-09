<?php
require_once __DIR__ . '/includes/session.php';
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
            <div class="bg-slate-800 rounded-xl p-6 mb-6 border border-white/10">
                <p class="text-slate-400 text-sm uppercase tracking-wide">Administration</p>
                <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mt-2">
                    <div>
                        <h1 class="text-3xl font-bold text-white">Manage Users</h1>
                        <p class="text-slate-400 mt-1">Create, edit, and control admin access roles.</p>
                    </div>
                    <button id="add-user-btn" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">
                        Add New User
                    </button>
                </div>
            </div>

            <div class="bg-slate-800 rounded-xl overflow-hidden border border-white/10">
                <div class="px-6 py-4 border-b border-white/10">
                    <h2 class="text-lg font-semibold">User Directory</h2>
                    <p class="text-slate-400 text-sm">All registered admin and super admin accounts.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-slate-700/80">
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



