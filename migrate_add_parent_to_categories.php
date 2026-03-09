<?php
require_once 'config/database.php';

$colRes = $conn->query("SHOW COLUMNS FROM categories LIKE 'parent_id'");
if ($colRes && $colRes->num_rows > 0) {
    echo "'parent_id' column already exists on categories table.\n";
    exit;
}

$sql = "ALTER TABLE categories ADD COLUMN parent_id INT(11) NULL DEFAULT NULL AFTER name";
if ($conn->query($sql) === TRUE) {
    echo "Added 'parent_id' column to categories table.\n";
} else {
    echo "Error adding column: " . $conn->error . "\n";
}

$conn->close();

?>
