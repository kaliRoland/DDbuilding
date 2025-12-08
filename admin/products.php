<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// If the admin is not logged in, redirect to login page
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

log_activity($conn, $_SESSION['admin_id'], 'view_products');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Products</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .toggle-featured:checked + .block {
            background-color: #f59e0b; /* amber-500 */
        }
        .toggle-featured:checked + .block .dot {
            transform: translateX(100%);
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-800 p-6">
            <img src="../uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-8 mb-8">
            <nav>
                <a href="index.php" class="block py-2 px-4 rounded hover:bg-slate-700">Dashboard</a>
                <a href="products.php" class="block py-2 px-4 rounded bg-amber-500 text-slate-900">Products</a>
                <?php if ($_SESSION['admin_role'] === 'super'): ?>
                    <a href="manage_users.php" class="block py-2 px-4 rounded hover:bg-slate-700">Manage Users</a>
                <?php endif; ?>
                <a href="logout.php" class="block py-2 px-4 rounded hover:bg-slate-700 mt-4">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-10">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold text-white">Manage Products</h1>
                <div class="flex gap-4">
                    <button id="add-product-btn" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">
                        Add New Product
                    </button>
                    <button id="add-category-btn" class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition">
                        Add New Category
                    </button>
                    <button id="export-csv-btn" class="bg-green-500 hover:bg-green-600 text-white font-bold py-2 px-4 rounded transition">
                        Export to CSV
                    </button>
                    <a href="import_products.php" class="bg-purple-500 hover:bg-purple-600 text-white font-bold py-2 px-4 rounded transition">
                        Import from CSV
                    </a>
                </div>
            </div>

            <!-- Filters and Search -->
            <div class="mb-6 bg-slate-800 p-4 rounded-lg flex flex-wrap items-center gap-4">
                <div class="flex-grow">
                    <label for="search" class="sr-only">Search</label>
                    <input type="text" id="search" placeholder="Search products..." class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                </div>
                <div class="flex-grow">
                    <label for="category-filter" class="sr-only">Category</label>
                    <select id="category-filter" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                        <option value="">All Categories</option>
                        <!-- Categories will be populated by JS -->
                    </select>
                </div>
                <div class="flex-grow">
                    <label for="price-filter" class="text-slate-400 text-sm">Max Price: <span id="price-value"></span></label>
                    <input type="range" id="price-filter" min="0" max="2000" step="10" class="w-full h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer">
                </div>
            </div>

            <div class="bg-slate-800 rounded-lg overflow-hidden">
                <div class="p-4 flex justify-end">
                    <button id="bulk-delete-btn" class="hidden bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition text-sm">
                        Delete Selected
                    </button>
                </div>
                <table class="min-w-full">
                    <thead class="bg-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left">
                                <input type="checkbox" id="select-all" class="rounded bg-slate-900 border-slate-600 text-amber-500 focus:ring-amber-500/50">
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Featured</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-slate-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="products-tbody" class="divide-y divide-slate-700">
                        <!-- Products will be loaded here by JavaScript -->
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <div id="pagination" class="mt-6 flex justify-center items-center gap-2">
                <!-- Pagination buttons will be loaded here by JS -->
            </div>

        </main>
    </div>

    <!-- Add/Edit Product Modal -->
    <div id="product-modal" class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm z-50 hidden items-center justify-center">
        <div class="bg-slate-800 p-8 rounded-lg shadow-lg w-full max-w-2xl max-h-screen overflow-y-auto">
            <h2 id="modal-title" class="text-2xl font-bold mb-6">Add Product</h2>
            <form id="product-form">
                <input type="hidden" id="product-id" name="id">
                <div class="mb-4">
                    <label for="name" class="block text-slate-400 mb-2">Product Name</label>
                    <input type="text" id="name" name="name" required class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label for="category" class="block text-slate-400 mb-2">Category</label>
                        <input type="text" id="category" name="category" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    </div>
                    <div>
                        <label for="price" class="block text-slate-400 mb-2">Price (NGN)</label>
                        <input type="number" step="0.01" id="price" name="price" required class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    </div>
                </div>
                <div class="mb-4">
                    <label class="block text-slate-400 mb-2">Product Images</label>
                    <p class="text-slate-500 text-xs mb-2">Max 2MB each. Main picture is required.</p>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="image_main" class="block text-slate-500 mb-1 text-sm">Main Picture</label>
                            <input type="file" id="image_main" name="image_main" accept="image/*" class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
                        </div>
                        <div>
                            <label for="image_1" class="block text-slate-500 mb-1 text-sm">Other Picture 1</label>
                            <input type="file" id="image_1" name="image_1" accept="image/*" class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-slate-700 file:text-slate-300 hover:file:bg-slate-600">
                        </div>
                        <div>
                            <label for="image_2" class="block text-slate-500 mb-1 text-sm">Other Picture 2</label>
                            <input type="file" id="image_2" name="image_2" accept="image/*" class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-slate-700 file:text-slate-300 hover:file:bg-slate-600">
                        </div>
                        <div>
                            <label for="image_3" class="block text-slate-500 mb-1 text-sm">Other Picture 3</label>
                            <input type="file" id="image_3" name="image_3" accept="image/*" class="w-full text-sm text-slate-400 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-slate-700 file:text-slate-300 hover:file:bg-slate-600">
                        </div>
                    </div>
                </div>
                <div class="mb-6">
                    <label for="description" class="block text-slate-400 mb-2">Description</label>
                    <textarea id="description" name="description" rows="4" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent"></textarea>
                </div>
                <div class="flex justify-end gap-4">
                    <button type="button" id="cancel-btn" class="bg-slate-600 hover:bg-slate-500 text-white font-bold py-2 px-4 rounded transition">
                        Cancel
                    </button>
                    <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">
                        Save Product
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/main.js"></script>
</body>
</html>

