<?php
require_once __DIR__ . '/includes/session.php';
require_once '../config/database.php';
require_once '../includes/functions.php';

if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

$message = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_main') {
        $name = trim($_POST['name'] ?? '');
        if ($name === '') $errors[] = 'Name required for main category.';
        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO categories (name, parent_id) VALUES (?, NULL)");
            $stmt->bind_param('s', $name);
            if ($stmt->execute()) {
                $message = 'Main category added';
            } else {
                $errors[] = 'Could not add category: ' . $stmt->error;
            }
            $stmt->close();
        }

    } elseif ($action === 'add_sub') {
        $parent = isset($_POST['parent_id']) && is_numeric($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;
        $name = trim($_POST['name'] ?? '');
        if (!$parent) $errors[] = 'Parent required';
        if ($name === '') $errors[] = 'Name required for subcategory.';
        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
            $stmt->bind_param('si', $name, $parent);
            if ($stmt->execute()) {
                $message = 'Subcategory added';
            } else {
                $errors[] = 'Could not add subcategory: ' . $stmt->error;
            }
            $stmt->close();
        }

    } elseif ($action === 'delete') {
        $id = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
        if (!$id) $errors[] = 'Invalid id to delete.';
        if (empty($errors)) {
            // check children
            $res = $conn->prepare("SELECT COUNT(*) as cnt FROM categories WHERE parent_id = ?");
            $res->bind_param('i', $id);
            $res->execute();
            $r = $res->get_result()->fetch_assoc();
            $res->close();
            if ($r['cnt'] > 0) {
                $errors[] = 'Cannot delete category with subcategories.';
            } else {
                // check products
                $p = $conn->prepare("SELECT COUNT(*) as cnt FROM products WHERE category_id = ? OR category = (SELECT name FROM categories WHERE id = ?)");
                $p->bind_param('ii', $id, $id);
                $p->execute();
                $pr = $p->get_result()->fetch_assoc();
                $p->close();
                if ($pr['cnt'] > 0) {
                    $errors[] = 'Cannot delete category linked to products.';
                } else {
                    $d = $conn->prepare("DELETE FROM categories WHERE id = ?");
                    $d->bind_param('i', $id);
                    if ($d->execute()) {
                        $message = 'Category deleted';
                    } else {
                        $errors[] = 'Delete failed: ' . $d->error;
                    }
                    $d->close();
                }
            }
        }

    } elseif ($action === 'edit') {
        $id = isset($_POST['id']) && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        if (!$id) $errors[] = 'Invalid id to edit.';
        if ($name === '') $errors[] = 'Name required for edit.';
        if (empty($errors)) {
            $u = $conn->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $u->bind_param('si', $name, $id);
            if ($u->execute()) {
                $message = 'Category updated';
            } else {
                $errors[] = 'Update failed: ' . $u->error;
            }
            $u->close();
        }
    }
}

// fetch categories hierarchy
$cats = [];
// Attempt to query with parent_id; if the column doesn't exist, fall back to a simple list
try {
    $res = $conn->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id, name");
    $has_parent = true;
} catch (mysqli_sql_exception $e) {
    // column missing (migration not run) — fall back
    $res = $conn->query("SELECT id, name FROM categories ORDER BY name");
    $has_parent = false;
}

if ($res) {
    while ($r = $res->fetch_assoc()) {
        $pid = $has_parent ? $r['parent_id'] : null;
        if ($pid === null) $cats[$r['id']] = ['id' => $r['id'], 'name' => $r['name'], 'subs' => []];
    }
    if ($has_parent) {
        // second pass for subs
        $res->data_seek(0);
        while ($r = $res->fetch_assoc()) {
            if ($r['parent_id'] !== null) {
                $pid = $r['parent_id'];
                if (isset($cats[$pid])) {
                    $cats[$pid]['subs'][] = $r;
                }
            }
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Manage Categories</title>
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
        <main class="flex-1 p-10">
            <h1 class="text-2xl font-bold mb-6">Manage Categories</h1>
            <?php if ($message): ?><div class="bg-green-600 p-3 rounded mb-4"><?= htmlspecialchars($message) ?></div><?php endif; ?>
            <?php if (!empty($errors)): ?><div class="bg-red-600 p-3 rounded mb-4"><?php foreach($errors as $e) echo '<div>'.htmlspecialchars($e).'</div>'; ?></div><?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-slate-800 p-6 rounded">
                    <h2 class="font-bold mb-3">Add Main Category</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_main">
                        <input name="name" placeholder="Main category name" class="w-full mb-3 bg-slate-700 p-2 rounded" required>
                        <button class="bg-amber-500 px-4 py-2 rounded text-slate-900 font-bold">Add</button>
                    </form>
                </div>
                <div class="bg-slate-800 p-6 rounded">
                    <h2 class="font-bold mb-3">Add Subcategory</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="add_sub">
                        <select name="parent_id" class="w-full mb-3 bg-slate-700 p-2 rounded" required>
                            <option value="">Select main category</option>
                            <?php foreach($cats as $c): ?>
                                <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input name="name" placeholder="Subcategory name" class="w-full mb-3 bg-slate-700 p-2 rounded" required>
                        <button class="bg-amber-500 px-4 py-2 rounded text-slate-900 font-bold">Add Sub</button>
                    </form>
                </div>
            </div>

            <div class="mt-6 bg-slate-800 p-6 rounded">
                <h2 class="font-bold mb-3">Existing Categories</h2>
                <?php foreach($cats as $c): ?>
                    <div class="mb-4 border-b border-slate-700 pb-2">
                        <div class="flex justify-between items-center">
                                <div class="font-semibold">
                                    <form method="POST" class="inline-flex items-center">
                                        <input type="hidden" name="action" value="edit">
                                        <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                        <input name="name" value="<?= htmlspecialchars($c['name']) ?>" class="bg-slate-700 p-1 rounded mr-2 text-slate-100">
                                        <button class="bg-amber-500 px-3 py-1 rounded text-slate-900 font-bold">Save</button>
                                    </form>
                                </div>
                                <form method="POST" onsubmit="return confirm('Delete this category?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                    <button class="bg-red-600 px-3 py-1 rounded">Delete</button>
                                </form>
                            </div>
                        <div class="mt-2 ml-4">
                                <?php if (!empty($c['subs'])): foreach($c['subs'] as $s): ?>
                                <div class="flex justify-between items-center py-1">
                                    <div class="text-slate-300">
                                        <form method="POST" class="inline-flex items-center">
                                            <input type="hidden" name="action" value="edit">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <input name="name" value="<?= htmlspecialchars($s['name']) ?>" class="bg-slate-700 p-1 rounded mr-2 text-slate-100">
                                            <button class="bg-amber-500 px-2 py-1 rounded text-slate-900 font-bold">Save</button>
                                        </form>
                                    </div>
                                    <form method="POST" onsubmit="return confirm('Delete this subcategory?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                        <button class="bg-red-600 px-2 py-1 rounded">Delete</button>
                                    </form>
                                </div>
                            <?php endforeach; else: ?>
                                <div class="text-slate-500">No subcategories</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>
</body>
</html>



