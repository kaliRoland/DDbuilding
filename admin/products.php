<?php
require_once __DIR__ . '/includes/session.php';
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
    <link rel="stylesheet" href="css/brand.css">
    <style>
        .toggle-featured:checked + .block {
            background-color: #f59e0b; /* amber-500 */
        }
        .toggle-featured:checked + .block .dot {
            transform: translateX(100%);
        }
    </style>
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
                    <input type="range" id="price-filter" min="0" max="2000" value="2000" step="10" class="w-full h-2 bg-slate-700 rounded-lg appearance-none cursor-pointer">
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
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Image</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Category</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-300 uppercase tracking-wider">Subcategory</th>
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
                        <label for="main_category_id" class="block text-slate-400 mb-2">Main Category</label>
                        <select id="main_category_id" name="main_category_id" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                            <option value="">Select Main Category</option>
                        </select>

                        <label for="subcategory_id" class="text-slate-400 mb-2 mt-3">Subcategory</label>
                        <select id="subcategory_id" name="category" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                            <option value="">Select Subcategory (optional)</option>
                        </select>

                        <input type="hidden" id="category_id" name="category_id" value="">
                    </div>
                    <div>
                        <label for="price" class="block text-slate-400 mb-2">Price (NGN)</label>
                        <input type="number" step="0.01" id="price" name="price" required class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    </div>
                </div>
                <div class="mb-4">
                    <label for="brand" class="block text-slate-400 mb-2">Brand</label>
                    <input type="text" id="brand" name="brand" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                </div>
                <div class="mb-4">
                    <label class="inline-flex items-center gap-2 text-slate-400">
                        <input type="checkbox" id="is_featured" name="is_featured" class="rounded text-amber-500">
                        <span class="text-sm">Mark as Featured</span>
                    </label>
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
                <div class="mb-6">
                    <label for="tags" class="block text-slate-400 mb-2">Tags (comma-separated)</label>
                    <input type="text" id="tags" name="tags" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                </div>
                <div class="mb-6">
                    <label class="block text-slate-400 mb-2">Product Specifications (Title and Detail)</label>
                    <div id="specifications-container">
                        <div class="spec-row grid grid-cols-2 gap-4 mb-2">
                            <input type="text" name="spec_title[]" placeholder="Specification Title" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                            <input type="text" name="spec_detail[]" placeholder="Specification Detail" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                        </div>
                    </div>
                    <button type="button" id="add-spec-btn" class="mt-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                        Add Another Specification
                    </button>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addSpecBtn = document.getElementById('add-spec-btn');
            const specsContainer = document.getElementById('specifications-container');
            if (addSpecBtn && specsContainer) {
                addSpecBtn.addEventListener('click', function() {
                    const specRow = document.createElement('div');
                    specRow.className = 'spec-row grid grid-cols-2 gap-4 mb-2';
                    specRow.innerHTML = `
                        <input type="text" name="spec_title[]" placeholder="Specification Title" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                        <input type="text" name="spec_detail[]" placeholder="Specification Detail" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    `;
                    specsContainer.appendChild(specRow);
                });
            }
            // Wire up share buttons on product page if present
            const url = encodeURIComponent(window.location.href);
            const title = encodeURIComponent(document.title);
            const wa = document.getElementById('share-whatsapp');
            const fb = document.getElementById('share-facebook');
            const tw = document.getElementById('share-twitter');
            const li = document.getElementById('share-linkedin');
            if (wa) wa.href = `https://api.whatsapp.com/send?text=${title}%20${url}`;
            if (fb) fb.href = `https://www.facebook.com/sharer/sharer.php?u=${url}`;
            if (tw) tw.href = `https://twitter.com/intent/tweet?url=${url}&text=${title}`;
            if (li) li.href = `https://www.linkedin.com/shareArticle?mini=true&url=${url}&title=${title}`;
        });
    </script>
</body>
</html>




