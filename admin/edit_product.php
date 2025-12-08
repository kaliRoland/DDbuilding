<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

// If the admin is not logged in, redirect to login page
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header('Location: products.php');
    exit;
}

$stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

if (!$product) {
    header('Location: products.php');
    exit;
}

$name = $product['name'];
$category = $product['category'];
$price = $product['price'];
$image_main = $product['image_main'];
$image_1 = $product['image_1'];
$image_2 = $product['image_2'];
$image_3 = $product['image_3'];
$description = $product['description'];
$errors = [];

// Fetch categories for dropdown
$categories = [];
$cat_res = $conn->query("SELECT name FROM categories ORDER BY name");
if ($cat_res) {
    while ($r = $cat_res->fetch_assoc()) {
        $categories[] = $r['name'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $new_category = trim($_POST['new_category'] ?? '');
    $price = $_POST['price'] ?? '';
    $image_main = $_POST['image_main'] ?? '';
    $image_1 = $_POST['image_1'] ?? '';
    $image_2 = $_POST['image_2'] ?? '';
    $image_3 = $_POST['image_3'] ?? '';
    $description = $_POST['description'] ?? '';

    if (empty($name)) $errors[] = 'Name is required';
    if (empty($price)) $errors[] = 'Price is required';

    // Handle creating new category if requested
    if ($category === '__new__') {
        if (empty($new_category)) {
            $errors[] = 'New category name is required.';
        } else {
            $check = $conn->prepare("SELECT id FROM categories WHERE name = ?");
            $check->bind_param('s', $new_category);
            $check->execute();
            $res = $check->get_result();
            if ($res && $res->num_rows > 0) {
                $category = $new_category;
            } else {
                $ins = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
                $ins->bind_param('s', $new_category);
                if ($ins->execute()) {
                    $category = $new_category;
                } else {
                    $errors[] = 'Could not create category: ' . $ins->error;
                }
                $ins->close();
            }
            $check->close();
        }
    }

                    if (empty($errors)) {

                        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, price = ?, image_main = ?, image_1 = ?, image_2 = ?, image_3 = ?, description = ? WHERE id = ?");

                        $stmt->bind_param("ssdsssssi", $name, $category, $price, $image_main, $image_1, $image_2, $image_3, $description, $id);

                        

                        if ($stmt->execute()) {
                            log_activity($conn, $_SESSION['admin_id'], 'edit_product: ' . $id);

                            header("Location: products.php");

                            exit;

                        } else {

                            $errors[] = "Database error: " . $stmt->error;

                        }

                    }}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100">

    <div class="flex min-h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-800 p-6">
            <img src="../uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-8 mb-8">
            <nav>
                <a href="index.php" class="block py-2 px-4 rounded hover:bg-slate-700">Dashboard</a>
                <a href="products.php" class="block py-2 px-4 rounded bg-amber-500 text-slate-900">Products</a>
                <a href="logout.php" class="block py-2 px-4 rounded hover:bg-slate-700 mt-4">Logout</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-10">
            <h1 class="text-3xl font-bold text-white mb-6">Edit Product</h1>

            <div class="bg-slate-800 p-8 rounded-lg max-w-2xl mx-auto">
                <?php if (!empty($errors)): ?>
                    <div class="bg-red-500 text-white p-3 rounded mb-4">
                        <?php foreach ($errors as $error): ?>
                            <p><?= htmlspecialchars($error) ?></p>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST">
                    <div class="mb-4">
                        <label for="name" class="block text-slate-400 mb-2">Product Name</label>
                        <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-4">
                        <div>
                            <label for="category" class="block text-slate-400 mb-2">Category</label>
                            <select id="category" name="category" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat) ?>" <?= ($category === $cat) ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
                                <?php endforeach; ?>
                                <option value="__new__" <?= ($category === '__new__') ? 'selected' : '' ?>>+ Add New Category...</option>
                            </select>
                            <div id="new-category-container" class="mt-2 <?= ($category === '__new__') ? '' : 'hidden' ?>">
                                <input type="text" name="new_category" id="new_category" placeholder="New category name" value="<?= htmlspecialchars($new_category ?? '') ?>" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                            </div>
                            <script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    const sel = document.getElementById('category');
                                    const container = document.getElementById('new-category-container');
                                    sel.addEventListener('change', () => {
                                        if (sel.value === '__new__') {
                                            container.classList.remove('hidden');
                                            document.getElementById('new_category').focus();
                                        } else {
                                            container.classList.add('hidden');
                                        }
                                    });
                                });
                            </script>
                        </div>
                        <div>
                            <label for="price" class="block text-slate-400 mb-2">Price</label>
                            <input type="number" step="0.01" id="price" name="price" value="<?= htmlspecialchars($price) ?>" required class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="image_main" class="block text-slate-400 mb-2">Main Image URL</label>
                        <input type="text" id="image_main" name="image_main" value="<?= htmlspecialchars($image_main) ?>" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    </div>
                    <div class="mb-4">
                        <label for="image_1" class="block text-slate-400 mb-2">Image 1 URL</label>
                        <input type="text" id="image_1" name="image_1" value="<?= htmlspecialchars($image_1) ?>" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    </div>
                    <div class="mb-4">
                        <label for="image_2" class="block text-slate-400 mb-2">Image 2 URL</label>
                        <input type="text" id="image_2" name="image_2" value="<?= htmlspecialchars($image_2) ?>" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    </div>
                    <div class="mb-4">
                        <label for="image_3" class="block text-slate-400 mb-2">Image 3 URL</label>
                        <input type="text" id="image_3" name="image_3" value="<?= htmlspecialchars($image_3) ?>" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    </div>
                    <div class="mb-6">
                        <label for="description" class="block text-slate-400 mb-2">Description</label>
                        <textarea id="description" name="description" rows="4" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent"><?= htmlspecialchars($description) ?></textarea>
                    </div>
                    <div class="flex justify-end gap-4">
                        <a href="products.php" class="bg-slate-600 hover:bg-slate-500 text-white font-bold py-2 px-4 rounded transition">
                            Cancel
                        </a>
                        <button type="submit" class="bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-2 px-4 rounded transition">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

</body>
</html>
