<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'get_all';

switch ($action) {
    case 'get_all':
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = 10; // Products per page
        // Check for an explicit limit override (e.g., for homepage display)
        if (isset($_GET['limit'])) {
            $limit = (int)$_GET['limit'];
            if ($limit <= 0) $limit = 10; // Ensure limit is positive
        }
        $offset = ($page - 1) * $limit;

        $search = $_GET['search'] ?? '';
        $category = $_GET['category'] ?? '';
        $max_price = $_GET['max_price'] ?? null;

        $sort = $_GET['sort'] ?? 'created_at_desc'; // Default sort

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
        $total_pages = ceil($total_products / $limit);

        // Add sorting logic
        $order_by_clause = " ORDER BY ";
        switch ($sort) {
            case 'price_asc':
                $order_by_clause .= "price ASC";
                break;
            case 'price_desc':
                $order_by_clause .= "price DESC";
                break;
            case 'name_asc':
                $order_by_clause .= "name ASC";
                break;
            case 'name_desc':
                $order_by_clause .= "name DESC";
                break;
            default:
                $order_by_clause .= "created_at DESC";
                break;
        }
        $sql .= $order_by_clause;

        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';

        $stmt = $conn->prepare($sql);
        if (!empty($types)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $products_result = $stmt->get_result();
        
        $products = [];
        while($row = $products_result->fetch_assoc()) {
            $products[] = $row;
        }

        // Get all unique categories for the filter dropdown
        $category_result = $conn->query("SELECT DISTINCT category FROM products WHERE category IS NOT NULL AND category != '' ORDER BY category");
        $categories = [];
        while($row = $category_result->fetch_assoc()) {
            $categories[] = $row['category'];
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
    case 'get_featured':
        $result = $conn->query("SELECT * FROM products WHERE is_featured = 1 ORDER BY created_at DESC LIMIT 4");
        $products = [];
        while($row = $result->fetch_assoc()) {
            $products[] = $row;
        }
        echo json_encode($products);
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
