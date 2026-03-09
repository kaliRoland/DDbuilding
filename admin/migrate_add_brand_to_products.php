<?php
require_once '../config/database.php';

// Check if column exists
$res = $conn->query("SHOW COLUMNS FROM products LIKE 'brand'");
if ($res && $res->num_rows > 0) {
    echo "Column 'brand' already exists.\n";
    exit;
}

$sql = "ALTER TABLE products ADD COLUMN brand VARCHAR(255) DEFAULT ''";
if ($conn->query($sql) === TRUE) {
    echo "Column 'brand' added successfully.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

?>

