<?php
session_start();
require_once '../config/database.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit;
}

$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'get_products':
        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $max_price = $_GET['max_price'] ?? null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) && $_GET['limit'] == 'all' ? null : 10;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT * FROM products";
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
            $count_stmt->bind_param($types, ...$params);
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
            $stmt->bind_param($types, ...$params);
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
        $id = $_POST['id'] ?? null;
        $name = $_POST['name'] ?? '';
        $category = $_POST['category'] ?? '';
        $price = $_POST['price'] ?? '';
        $description = $_POST['description'] ?? '';

        if (empty($name) || empty($price)) {
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

        if ($id) { // Update
            $sql = "UPDATE products SET name = ?, category = ?, price = ?, description = ?";
            $params = [$name, $category, $price, $description];
            $types = "ssds";
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
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
        } else { // Insert
            $sql = "INSERT INTO products (name, category, price, description, image_main, image_1, image_2, image_3) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $image_main = $image_paths['image_main'] ?? null;
            $image_1 = $image_paths['image_1'] ?? null;
            $image_2 = $image_paths['image_2'] ?? null;
            $image_3 = $image_paths['image_3'] ?? null;
            $stmt->bind_param("ssdsssss", $name, $category, $price, $description, $image_main, $image_1, $image_2, $image_3);
        }

        if ($stmt->execute()) {
            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => $stmt->error]);
        }
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

    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
