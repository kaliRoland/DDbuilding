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

$stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    log_activity($conn, $_SESSION['admin_id'], 'delete_product: ' . $id);
    header("Location: products.php");
    exit;
} else {
    die("Error deleting product: " . $stmt->error);
}
