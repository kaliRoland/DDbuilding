<?php
require_once __DIR__ . '/includes/session.php';
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
$brand = $product['brand'] ?? '';
$image_main = $product['image_main'];
$image_1 = $product['image_1'];
$image_2 = $product['image_2'];
$image_3 = $product['image_3'];
$description = $product['description'];
$tags = $product['tags'] ?? '';
$specifications = json_decode($product['specifications'] ?? '[]', true);
$errors = [];

// Fetch categories for dropdown
$categories = [];
$cat_res = $conn->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id, name");
if ($cat_res) {
    while ($r = $cat_res->fetch_assoc()) {
        $categories[] = $r; // id, name, parent_id
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'] ?? '';
    $category = $_POST['category'] ?? '';
    $new_category = trim($_POST['new_category'] ?? '');
    $main_category_id = $_POST['main_category_id'] ?? '';
    $new_subcategory = trim($_POST['new_subcategory'] ?? '');
    $subcategory_id = null;
    $price = $_POST['price'] ?? '';
    $brand = $_POST['brand'] ?? '';
    $image_main = $_POST['image_main'] ?? '';
    $image_1 = $_POST['image_1'] ?? '';
    $image_2 = $_POST['image_2'] ?? '';
    $image_3 = $_POST['image_3'] ?? '';
    $description = $_POST['description'] ?? '';
    $tags = $_POST['tags'] ?? '';

    // Process specifications
    $spec_titles = $_POST['spec_title'] ?? [];
    $spec_details = $_POST['spec_detail'] ?? [];
    $specifications = [];
    for ($i = 0; $i < count($spec_titles); $i++) {
        if (!empty($spec_titles[$i]) && !empty($spec_details[$i])) {
            $specifications[] = [
                'title' => $spec_titles[$i],
                'detail' => $spec_details[$i]
            ];
        }
    }

    if (empty($name)) $errors[] = 'Name is required';
    if (empty($price)) $errors[] = 'Price is required';

    // Handle creating new main category if requested (legacy single-category flow)
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

                        // If creating a new subcategory via the edit form, create it
                        if (!empty($new_subcategory) && !empty($main_category_id) && is_numeric($main_category_id)) {
                            $check = $conn->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ?");
                            if ($check) {
                                $check->bind_param('si', $new_subcategory, $main_category_id);
                                $check->execute();
                                $res = $check->get_result();
                                if ($res && $res->num_rows > 0) {
                                    $row = $res->fetch_assoc();
                                    $subcategory_id = $row['id'];
                                } else {
                                    $ins = $conn->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
                                    if ($ins) {
                                        $ins->bind_param('si', $new_subcategory, $main_category_id);
                                        if ($ins->execute()) {
                                            $subcategory_id = $ins->insert_id;
                                        }
                                        $ins->close();
                                    }
                                }
                                $check->close();
                            }
                            if (!empty($subcategory_id)) $category = $new_subcategory;
                        }

                        // If an existing subcategory name was selected, resolve its id
                        if (empty($subcategory_id) && !empty($category) && $category !== '__new_sub__' && !empty($main_category_id) && is_numeric($main_category_id)) {
                            $check = $conn->prepare("SELECT id FROM categories WHERE name = ? AND parent_id = ?");
                            if ($check) {
                                $check->bind_param('si', $category, $main_category_id);
                                $check->execute();
                                $res = $check->get_result();
                                if ($res && $res->num_rows > 0) {
                                    $row = $res->fetch_assoc();
                                    $subcategory_id = $row['id'];
                                }
                                $check->close();
                            }
                        }

                        // Determine category_id: prefer subcategory (if chosen), else main category
                        $category_id = null;
                        if (!empty($subcategory_id)) {
                            $category_id = (int)$subcategory_id;
                        } elseif (!empty($main_category_id) && is_numeric($main_category_id)) {
                            $category_id = (int)$main_category_id;
                        } else {
                            // try to resolve by name as fallback
                            $res = $conn->prepare("SELECT id FROM categories WHERE name = ? LIMIT 1");
                            if ($res) {
                                $res->bind_param('s', $category);
                                $res->execute();
                                $r2 = $res->get_result()->fetch_assoc();
                                if ($r2) $category_id = (int)$r2['id'];
                                $res->close();
                            }
                        }

                        $stmt = $conn->prepare("UPDATE products SET name = ?, category = ?, category_id = ?, price = ?, brand = ?, image_main = ?, image_1 = ?, image_2 = ?, image_3 = ?, description = ?, tags = ?, specifications = ? WHERE id = ?");

                        $specs_json = json_encode($specifications);
                        $stmt->bind_param("ssisssssssssi", $name, $category, $category_id, $price, $brand, $image_main, $image_1, $image_2, $image_3, $description, $tags, $specs_json, $id);

                        

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
        <!-- Sidebar -->
        <aside class="w-64 bg-slate-800 p-6">
            <img src="../uploads/logo/logo.png" alt="DDbuildingTech Logo" class="h-8 mb-8">
            <nav>
                <a href="index.php" class="block py-2 px-4 rounded hover:bg-slate-700">Dashboard</a>
                <a href="products.php" class="block py-2 px-4 rounded bg-amber-500 text-slate-900">Products</a>
                <a href="manage_slides.php" class="block py-2 px-4 rounded hover:bg-slate-700">Hero Slides</a>
                <a href="add_product.php" class="block py-2 px-4 rounded hover:bg-slate-700" id="add-product-btn">Add New Product</a>
                <a href="gallery.php" class="block py-2 px-4 rounded hover:bg-slate-700">Manage Gallery</a>
                <a href="site_settings.php" class="block py-2 px-4 rounded hover:bg-slate-700">Site Settings</a>
                <a href="order_management.php" class="block py-2 px-4 rounded hover:bg-slate-700">Order Management</a>
                <a href="https://ddbuildingtech.com/blog" target="_blank" class="block py-2 px-4 rounded hover:bg-slate-700">Blog Management</a>
                <?php if ($_SESSION['admin_role'] === 'super'): ?>
                    <a href="manage_users.php" class="block py-2 px-4 rounded hover:bg-slate-700">Manage Users</a>
                    <a href="backup_restore.php" class="block py-2 px-4 rounded hover:bg-slate-700">Backup & Restore</a>
                <?php endif; ?>
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
                            <label class="text-slate-300 text-sm">Main Category</label>
                            <select id="main_category_id" name="main_category_id" class="w-full bg-slate-700 text-white rounded px-3 py-2 mb-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                                <option value="">Select Main Category</option>
                                <?php foreach ($categories as $cat): if ($cat['parent_id'] === null): ?>
                                    <option value="<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                                <?php endif; endforeach; ?>
                                <option value="__new__">+ Add New Main Category...</option>
                            </select>

                            <label class="text-slate-300 text-sm">Subcategory</label>
                            <select id="subcategory_id" name="category" class="w-full bg-slate-700 text-white rounded px-3 py-2 mb-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                                <option value="">Select Subcategory (optional)</option>
                                <!-- populated by JS -->
                                <option value="__new_sub__">+ Add New Subcategory...</option>
                            </select>

                            <div id="new-main-container" class="mt-2 hidden">
                                <input type="text" name="new_category" id="new_category" placeholder="New main category name" value="<?= htmlspecialchars($new_category ?? '') ?>" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                            </div>
                            <div id="new-sub-container" class="mt-2 hidden">
                                <input type="text" name="new_subcategory" id="new_subcategory" placeholder="New subcategory name" value="<?= htmlspecialchars($new_subcategory ?? '') ?>" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                            </div>
                            <script>
                                document.addEventListener('DOMContentLoaded', () => {
                                    const categories = <?= json_encode($categories) ?>;
                                    const mainSel = document.getElementById('main_category_id');
                                    const subSel = document.getElementById('subcategory_id');
                                    const newMain = document.getElementById('new-main-container');
                                    const newSub = document.getElementById('new-sub-container');

                                    function populateSubcategories(parentId) {
                                        // clear existing except the __new_sub__ option
                                        subSel.innerHTML = '<option value="">Select Subcategory (optional)</option>';
                                        categories.forEach(c => {
                                            if (c.parent_id == parentId) {
                                                const opt = document.createElement('option');
                                                opt.value = c.name;
                                                opt.textContent = c.name;
                                                subSel.appendChild(opt);
                                            }
                                        });
                                        const newOpt = document.createElement('option');
                                        newOpt.value = '__new_sub__';
                                        newOpt.textContent = '+ Add New Subcategory...';
                                        subSel.appendChild(newOpt);
                                    }

                                    mainSel.addEventListener('change', () => {
                                        if (mainSel.value === '__new__') {
                                            newMain.classList.remove('hidden');
                                            newSub.classList.add('hidden');
                                            subSel.innerHTML = '<option value="">Select Subcategory (optional)</option><option value="__new_sub__">+ Add New Subcategory...</option>';
                                        } else {
                                            newMain.classList.add('hidden');
                                            newSub.classList.add('hidden');
                                            populateSubcategories(mainSel.value);
                                        }
                                    });

                                    subSel.addEventListener('change', () => {
                                        if (subSel.value === '__new_sub__') {
                                            newSub.classList.remove('hidden');
                                        } else {
                                            newSub.classList.add('hidden');
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
                        <label for="brand" class="block text-slate-400 mb-2">Brand</label>
                        <input type="text" id="brand" name="brand" value="<?= htmlspecialchars($brand ?? '') ?>" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
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
                    <div class="mb-6">
                        <label for="tags" class="block text-slate-400 mb-2">Tags (for SEO, comma-separated)</label>
                        <input type="text" id="tags" name="tags" value="<?= htmlspecialchars($tags) ?>" placeholder="solar, panel, energy, renewable" class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    </div>
                    <div class="mb-6">
                        <label class="block text-slate-400 mb-2">Product Specifications</label>
                        <div id="specifications-container">
                            <?php if (empty($specifications)): ?>
                            <div class="spec-row grid grid-cols-2 gap-4 mb-2">
                                <input type="text" name="spec_title[]" placeholder="Specification Title" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                                <input type="text" name="spec_detail[]" placeholder="Specification Detail" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                            </div>
                            <?php else: ?>
                                <?php foreach ($specifications as $spec): ?>
                                <div class="spec-row grid grid-cols-2 gap-4 mb-2">
                                    <input type="text" name="spec_title[]" value="<?= htmlspecialchars($spec['title']) ?>" placeholder="Specification Title" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                                    <input type="text" name="spec_detail[]" value="<?= htmlspecialchars($spec['detail']) ?>" placeholder="Specification Detail" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        <button type="button" id="add-spec-btn" class="mt-2 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition">
                            Add Another Specification
                        </button>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const addSpecBtn = document.getElementById('add-spec-btn');
            const specsContainer = document.getElementById('specifications-container');

            addSpecBtn.addEventListener('click', function() {
                const specRow = document.createElement('div');
                specRow.className = 'spec-row grid grid-cols-2 gap-4 mb-2';
                specRow.innerHTML = `
                    <input type="text" name="spec_title[]" placeholder="Specification Title" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    <input type="text" name="spec_detail[]" placeholder="Specification Detail" class="bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                `;
                specsContainer.appendChild(specRow);
            });
        });
    </script>

</body>
</html>



