<!-- Sidebar -->
<aside class="w-64 bg-slate-800 p-6">
    <img src="../uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-8 mb-8">
    <nav>
        <a href="index.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'bg-amber-500 text-slate-900' : '' ?>">Dashboard</a>
        <a href="products.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'products.php') ? 'bg-amber-500 text-slate-900' : '' ?>">Products</a>
        <a href="reviews.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'reviews.php') ? 'bg-amber-500 text-slate-900' : '' ?>">Reviews</a>
        <a href="manage_categories.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'manage_categories.php') ? 'bg-amber-500 text-slate-900' : '' ?>">Manage Categories</a>
        <a href="manage_slides.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'manage_slides.php') ? 'bg-amber-500 text-slate-900' : '' ?>">Hero Slides</a>
        <a href="add_product.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'add_product.php') ? 'bg-amber-500 text-slate-900' : '' ?>" id="add-product-btn">Add New Product</a>
        <a href="gallery.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'gallery.php') ? 'bg-amber-500 text-slate-900' : '' ?>">Manage Gallery</a>
        <a href="site_settings.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'site_settings.php') ? 'bg-amber-500 text-slate-900' : '' ?>">Site Settings</a>
        <a href="order_management.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'order_management.php') ? 'bg-amber-500 text-slate-900' : '' ?>">Order Management</a>
        <a href="support_requests.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'support_requests.php') ? 'bg-amber-500 text-slate-900' : '' ?>">Support Requests</a>
        <a href="https://ddbuildingtech.com/blog/wp-admin" target="_blank" class="block py-2 px-4 rounded hover:bg-slate-700">Blog Management</a>
        <?php if ($_SESSION['admin_role'] === 'super'): ?>
            <a href="manage_users.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'manage_users.php') ? 'bg-amber-500 text-slate-900' : '' ?>">Manage Users</a>
            <a href="backup_restore.php" class="block py-2 px-4 rounded hover:bg-slate-700 <?= (basename($_SERVER['PHP_SELF']) == 'backup_restore.php') ? 'bg-amber-500 text-slate-900' : '' ?>">Backup & Restore</a>
        <?php endif; ?>
        <a href="logout.php" class="block py-2 px-4 rounded hover:bg-slate-700 mt-4">Logout</a>
    </nav>
</aside>
