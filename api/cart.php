<?php
session_start();

require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

// Initialize cart if it doesn't exist
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        $id = $_POST['id'] ?? null;
        if ($id) {
            $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $product = $result->fetch_assoc();

            if ($product) {
                log_activity($conn, null, 'add_to_cart');
                $found = false;
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['id'] == $id) {
                        $item['quantity']++;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $product['quantity'] = 1;
                    $_SESSION['cart'][] = $product;
                }
            }
        }
        break;

    case 'remove':
        $id = $_POST['id'] ?? null;
        if ($id) {
            log_activity($conn, null, 'remove_from_cart');
            $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($id) {
                return $item['id'] != $id;
            });
        }
        break;

    case 'update':
        $id = $_POST['id'] ?? null;
        $change = $_POST['change'] ?? null;
        if ($id && $change) {
            log_activity($conn, null, 'update_cart_quantity');
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['id'] == $id) {
                    $item['quantity'] += $change;
                    if ($item['quantity'] <= 0) {
                        // Mark for removal
                        $item['quantity'] = 0;
                    }
                    break;
                }
            }
            // Remove items with 0 quantity
            $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) {
                return $item['quantity'] > 0;
            });
        }
        break;

    case 'get':
    default:
        // The cart is returned at the end
        break;
}

echo json_encode(array_values($_SESSION['cart']));
