<?php
require_once __DIR__ . '/includes/session.php';
require_once '../config/database.php';

// Helper to bind params safely (ensures arguments are passed by reference)
function bind_params_stmt($stmt, $types, &$params) {
    if (empty($types)) return true;
    $bind_names[] = &$types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    return call_user_func_array([$stmt, 'bind_param'], $bind_names);
}

// Ensure products table has required columns; auto-add if missing
function ensure_product_columns_exist($conn, $dbgFile = null) {
    $needed = [
        'tags' => "TEXT NULL",
        'specifications' => "TEXT NULL"
    ];
    foreach ($needed as $col => $def) {
        $res = $conn->query("SHOW COLUMNS FROM products LIKE '" . $conn->real_escape_string($col) . "'");
        if ($res && $res->num_rows == 0) {
            $alter_sql = "ALTER TABLE products ADD COLUMN `" . $col . "` " . $def;
            if ($dbgFile) file_put_contents($dbgFile, "Adding missing column: $col with SQL: $alter_sql\n", FILE_APPEND);
            if (!$conn->query($alter_sql)) {
                if ($dbgFile) file_put_contents($dbgFile, "Failed to add column $col: " . $conn->error . "\n", FILE_APPEND);
            } else {
                if ($dbgFile) file_put_contents($dbgFile, "Added column $col successfully.\n", FILE_APPEND);
            }
        }
    }
}

function reviews_table_exists($conn) {
    $res = $conn->query("SHOW TABLES LIKE 'product_reviews'");
    return $res && $res->num_rows > 0;
}

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

// For debugging: enable PHP error display so browser shows the error
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

switch ($action) {
    case 'get_products':
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $max_price = $_GET['max_price'] ?? null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) && $_GET['limit'] == 'all' ? null : 10;
        $offset = ($page - 1) * $limit;

        // Select products and join category info (subcategory and main category)
        $sql = "SELECT products.*, c.name AS subcategory, p.name AS main_category FROM products LEFT JOIN categories c ON products.category_id = c.id LEFT JOIN categories p ON c.parent_id = p.id";
        $count_sql = "SELECT COUNT(*) as total FROM products";
        $where_clauses = [];
        $params = [];
        $types = '';

        if (!empty($search)) {
            $where_clauses[] = "(name LIKE ? OR description LIKE ?)";
            $search_param = "%{$search}%";
            $params[] = $search_param;
            $params[] = $search_param;
            $types .= 'ss';
        }
        if (!empty($category)) {
            $where_clauses[] = "category = ?";
            $params[] = $category;
            $types .= 's';
        }
        if ($max_price !== null) {
            $where_clauses[] = "price <= ?";
            $params[] = $max_price;
            $types .= 'd';
        }

        if (!empty($where_clauses)) {
            $sql .= " WHERE " . implode(' AND ', $where_clauses);
            $count_sql .= " WHERE " . implode(' AND ', $where_clauses);
        }

        // Get total count for pagination
        $count_stmt = $conn->prepare($count_sql);
        if (!empty($types)) {
            bind_params_stmt($count_stmt, $types, $params);
        }
        $count_stmt->execute();
        $total_result = $count_stmt->get_result()->fetch_assoc();
        $total_products = $total_result['total'];
        $total_pages = $limit ? ceil($total_products / $limit) : 1;

        $sql .= " ORDER BY created_at DESC";
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
        }

        $stmt = $conn->prepare($sql);
        if (!empty($types)) {
            bind_params_stmt($stmt, $types, $params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $products = [];
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }

        // Get all unique categories for the filter dropdown
        $category_result = $conn->query("SELECT name FROM categories ORDER BY name");
        $categories = [];
        while($row = $category_result->fetch_assoc()) {
            $categories[] = $row['name'];
        }

        echo json_encode([
            'status' => 'success', 
            'products' => $products,
            'pagination' => [
                'page' => $page,
                'total_pages' => $total_pages
            ],
            'categories' => $categories
        ]);
        break;

    case 'get_categories':
        $category_result = $conn->query("SELECT id, name, parent_id FROM categories ORDER BY parent_id, name");
        $categories = [];
        while($row = $category_result->fetch_assoc()) {
            $categories[] = $row; // return id, name, parent_id
        }
        echo json_encode(['status' => 'success', 'categories' => $categories]);
        break;

    case 'get_product':
        $id = $_GET['id'] ?? null;
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Product ID is required.']);
            exit;
        }
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        if ($product) {
            echo json_encode(['status' => 'success', 'product' => $product]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Product not found.']);
        }
        break;

    case 'save_product':
        // Normalize ID: accept only numeric IDs; treat empty or non-numeric as null
        $id = isset($_POST['id']) && $_POST['id'] !== '' && is_numeric($_POST['id']) ? (int)$_POST['id'] : null;
        $name = trim($_POST['name'] ?? '');
        $category = $_POST['category'] ?? '';
        $category_id = isset($_POST['category_id']) && $_POST['category_id'] !== '' && is_numeric($_POST['category_id']) ? (int)$_POST['category_id'] : null;
        $raw_price = $_POST['price'] ?? '';
        $price = is_string($raw_price) ? trim($raw_price) : $raw_price;
        if (is_string($price)) {
            // Normalize price like "50,000" or "₦50,000" to numeric string
            $price = preg_replace('/[^0-9.]/', '', $price);
        }
        $brand = $_POST['brand'] ?? '';
        $description = $_POST['description'] ?? '';
        $tags = $_POST['tags'] ?? '';
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;

        // Debug log start
        $dbgFile = __DIR__ . '/save_product_debug.log';
        $dbg = fopen($dbgFile, 'a');
        if ($dbg) {
            fwrite($dbg, "---- save_product called: " . date('c') . " ----\n");
            fwrite($dbg, "POST: " . print_r($_POST, true) . "\n");
            fwrite($dbg, "Normalized price: raw=" . print_r($raw_price, true) . " normalized=" . print_r($price, true) . "\n");
            fwrite($dbg, "FILES keys: " . print_r(array_keys($_FILES), true) . "\n");
        }

        // Install error and shutdown handlers to capture fatal errors
        set_error_handler(function($errno, $errstr, $errfile, $errline) use ($dbgFile) {
            $msg = "PHP Error [$errno] $errstr in $errfile:$errline\n";
            file_put_contents($dbgFile, $msg, FILE_APPEND);
            return false; // allow normal error handling too
        });
        register_shutdown_function(function() use ($dbgFile) {
            $err = error_get_last();
            if ($err) {
                $msg = "Shutdown Error: " . print_r($err, true) . "\n";
                file_put_contents($dbgFile, $msg, FILE_APPEND);
            }
        });

        // Ensure the products table has required columns before preparing statements
        ensure_product_columns_exist($conn, $dbgFile);

        // Process specifications arrays if provided
        $spec_titles = $_POST['spec_title'] ?? [];
        $spec_details = $_POST['spec_detail'] ?? [];
        $specifications = [];
        for ($i = 0; $i < count($spec_titles); $i++) {
            if (!empty($spec_titles[$i]) && !empty($spec_details[$i])) {
                $specifications[] = ['title' => $spec_titles[$i], 'detail' => $spec_details[$i]];
            }
        }
        $specs_json = json_encode($specifications);

        if ($name === '' || $price === '' || !is_numeric($price)) {
            echo json_encode(['status' => 'error', 'message' => 'Name and Price are required.']);
            exit;
        }

        $image_paths = [];
        $image_fields = ['image_main', 'image_1', 'image_2', 'image_3'];
        $upload_dir = '../uploads/';

        foreach ($image_fields as $field) {
            if (isset($_FILES[$field]) && $_FILES[$field]['error'] === UPLOAD_ERR_OK) {
                $file = $_FILES[$field];
                // Validate file size (2MB limit)
                if ($file['size'] > 2 * 1024 * 1024) {
                    echo json_encode(['status' => 'error', 'message' => "File {$file['name']} is too large."]);
                    exit;
                }
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($file['type'], $allowed_types)) {
                    echo json_encode(['status' => 'error', 'message' => "Invalid file type for {$file['name']}."]);
                    exit;
                }
                // Generate unique name and move file
                $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
                $unique_filename = uniqid($field . '_', true) . '.' . $extension;
                if (move_uploaded_file($file['tmp_name'], $upload_dir . $unique_filename)) {
                    $image_paths[$field] = 'uploads/' . $unique_filename;
                } else {
                    echo json_encode(['status' => 'error', 'message' => "Failed to upload {$file['name']}."]);
                    exit;
                }
            }
        }

        if ($id !== null) { // Update when a valid numeric id is provided
            $sql = "UPDATE products SET name = ?, category = ?, category_id = ?, price = ?, brand = ?, description = ?, tags = ?, specifications = ?, is_featured = ?";
            $params = [$name, $category, $category_id, $price, $brand, $description, $tags, $specs_json, $is_featured];
            $types = "ssidssssi"; // s(name), s(category), i(category_id), d(price), s(brand), s(description), s(tags), s(specs_json), i(is_featured)
            foreach ($image_fields as $field) {
                if (isset($image_paths[$field])) {
                    $sql .= ", $field = ?";
                    $params[] = $image_paths[$field];
                    $types .= "s";
                }
            }
            $sql .= " WHERE id = ?";
            $params[] = $id;
            $types .= "i";
            if ($dbg) fwrite($dbg, "Prepared SQL: " . $sql . "\nTypes: " . $types . "\nParams: " . print_r($params, true) . "\n");
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                if ($dbg) fwrite($dbg, "Prepare failed: " . $conn->error . "\n");
                if ($dbg) fclose($dbg);
                echo json_encode(['status' => 'error', 'message' => 'Database prepare failed.']);
                exit;
            }
            $bind_ok = bind_params_stmt($stmt, $types, $params);
            if ($dbg) fwrite($dbg, "Prepare OK. bind_ok=" . ($bind_ok ? '1' : '0') . "\n");
            if (!$bind_ok) {
                if ($dbg) fwrite($dbg, "bind_param failed: could not bind params\n");
                if ($dbg) fclose($dbg);
                echo json_encode(['status' => 'error', 'message' => 'Parameter binding failed.']);
                exit;
            }
        } else { // Insert
            $sql = "INSERT INTO products (name, category, category_id, price, brand, description, tags, specifications, image_main, image_1, image_2, image_3, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            if ($dbg) fwrite($dbg, "Insert SQL: " . $sql . "\n");
            $stmt = $conn->prepare($sql);
            if (!$stmt) {
                if ($dbg) fwrite($dbg, "Prepare failed (insert): " . $conn->error . "\n");
                if ($dbg) fclose($dbg);
                echo json_encode(['status' => 'error', 'message' => 'Database prepare failed.']);
                exit;
            }
            $image_main = $image_paths['image_main'] ?? null;
            $image_1 = $image_paths['image_1'] ?? null;
            $image_2 = $image_paths['image_2'] ?? null;
            $image_3 = $image_paths['image_3'] ?? null;
            $bind_ok = $stmt->bind_param("ssisssssssssi", $name, $category, $category_id, $price, $brand, $description, $tags, $specs_json, $image_main, $image_1, $image_2, $image_3, $is_featured);
            if (!$bind_ok) {
                if ($dbg) fwrite($dbg, "bind_param failed (insert): " . $stmt->error . "\n");
                if ($dbg) fclose($dbg);
                echo json_encode(['status' => 'error', 'message' => 'Parameter binding failed.']);
                exit;
            }
        }

        if ($dbg) fwrite($dbg, "About to execute statement...\n");
        $exec_ok = false;
        try {
            $exec_ok = $stmt->execute();
        } catch (\Throwable $e) {
            if ($dbg) fwrite($dbg, "Execute threw exception: " . $e->getMessage() . "\n");
            echo json_encode(['status' => 'error', 'message' => 'Execution exception']);
            if ($dbg) fclose($dbg);
            $stmt->close();
            exit;
        }
        if ($exec_ok) {
            if ($dbg) fwrite($dbg, "Execute success.\n");
            echo json_encode(['status' => 'success']);
        } else {
            if ($dbg) fwrite($dbg, "Execute failed: " . $stmt->error . "\n");
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        if ($dbg) fclose($dbg);
        $stmt->close();
        break;
        
    case 'delete_product':
        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Product ID is required.']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
        break;

    case 'toggle_featured':
        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Product ID is required.']);
            exit;
        }

        // First, get the current status
        $stmt = $conn->prepare("SELECT is_featured FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $new_status = !$product['is_featured'];

        // If we are trying to feature a new product, check the count
        if ($new_status == 1) {
            $count_res = $conn->query("SELECT COUNT(*) as featured_count FROM products WHERE is_featured = 1");
            $featured_count = $count_res->fetch_assoc()['featured_count'];
            if ($featured_count >= 5) {
                echo json_encode(['status' => 'error', 'message' => 'You can only feature a maximum of 5 products.']);
                exit;
            }
        }

        $update_stmt = $conn->prepare("UPDATE products SET is_featured = ? WHERE id = ?");
        $update_stmt->bind_param("ii", $new_status, $id);
        if ($update_stmt->execute()) {
            echo json_encode(['status' => 'success', 'new_status' => $new_status]);
        } else {
            echo json_encode(['status' => 'error', 'message' => $update_stmt->error]);
        }
        break;

    case 'bulk_delete':
        $ids = $_POST['ids'] ?? null;
        if (!$ids || !is_array($ids)) {
            echo json_encode(['status' => 'error', 'message' => 'Product IDs are required.']);
            exit;
        }
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $types = str_repeat('i', count($ids));
        
        $stmt = $conn->prepare("DELETE FROM products WHERE id IN ($placeholders)");
        $stmt->bind_param($types, ...$ids);

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
        break;

    case 'add_category':
        $category_name = $_POST['category_name'] ?? '';

        if (empty($category_name)) {
            echo json_encode(['status' => 'error', 'message' => 'Category name cannot be empty.']);
            exit;
        }

        $stmt = $conn->prepare("INSERT INTO categories (name) VALUES (?)");
        $stmt->bind_param("s", $category_name);
        
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success', 'message' => 'Category added successfully.']);
        } else {
            if ($conn->errno == 1062) { // Duplicate entry
                echo json_encode(['status' => 'error', 'message' => 'Category already exists.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => $stmt->error]);
            }
        }
        $stmt->close();
        break;

    case 'get_reviews':
        if (!reviews_table_exists($conn)) {
            echo json_encode(['status' => 'error', 'message' => 'Reviews table not found.']);
            exit;
        }
        $status = $_GET['status'] ?? 'pending';
        if (!in_array($status, ['pending', 'approved'], true)) {
            echo json_encode(['status' => 'error', 'message' => 'Invalid status.']);
            exit;
        }
        $stmt = $conn->prepare("SELECT r.id, r.product_id, r.name, r.rating, r.review_text, r.created_at, p.name AS product_name FROM product_reviews r JOIN products p ON r.product_id = p.id WHERE r.status = ? ORDER BY r.created_at DESC");
        $stmt->bind_param('s', $status);
        $stmt->execute();
        $result = $stmt->get_result();
        $reviews = [];
        while ($row = $result->fetch_assoc()) {
            $row['created_at_formatted'] = date('M d, Y', strtotime($row['created_at']));
            $reviews[] = $row;
        }
        $stmt->close();
        echo json_encode(['status' => 'success', 'reviews' => $reviews]);
        break;

    case 'approve_review':
        if (!reviews_table_exists($conn)) {
            echo json_encode(['status' => 'error', 'message' => 'Reviews table not found.']);
            exit;
        }
        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Review ID is required.']);
            exit;
        }
        $stmt = $conn->prepare("UPDATE product_reviews SET status = 'approved', approved_at = NOW() WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
        break;

    case 'delete_review':
        if (!reviews_table_exists($conn)) {
            echo json_encode(['status' => 'error', 'message' => 'Reviews table not found.']);
            exit;
        }
        $id = $_POST['id'] ?? null;
        if (!$id) {
            echo json_encode(['status' => 'error', 'message' => 'Review ID is required.']);
            exit;
        }
        $stmt = $conn->prepare("DELETE FROM product_reviews WHERE id = ?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
        $stmt->close();
        break;

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}



