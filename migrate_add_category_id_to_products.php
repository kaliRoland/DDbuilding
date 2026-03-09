<?php
require_once 'config/database.php';

$colRes = $conn->query("SHOW COLUMNS FROM products LIKE 'category_id'");
if ($colRes && $colRes->num_rows > 0) {
    echo "'category_id' column already exists on products table.\n";
    exit;
}

$sql = "ALTER TABLE products ADD COLUMN category_id INT(11) NULL DEFAULT NULL AFTER category";
if ($conn->query($sql) === TRUE) {
    echo "Added 'category_id' column to products table.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
    exit;
}

// Backfill category_id from existing category name where possible
$backfill = "UPDATE products p
LEFT JOIN categories c ON p.category = c.name
SET p.category_id = c.id
WHERE c.id IS NOT NULL";
if ($conn->query($backfill) === TRUE) {
    echo "Backfilled category_id for products where category name matched.\n";
} else {
    echo "Error backfilling category_id: " . $conn->error . "\n";
}

// Note: after verifying, you may drop the old 'category' text column if desired.
$conn->close();
?>
