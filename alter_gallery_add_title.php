<?php
require_once 'config/database.php';

// Add title column to gallery_items table if it doesn't exist
$sql = "ALTER TABLE gallery_items ADD COLUMN title VARCHAR(255) AFTER id";

try {
    if ($conn->query($sql) === TRUE) {
        echo "Title column added successfully to gallery_items table.<br>";
    } else {
        // Column might already exist, so this error is okay
        if (strpos($conn->error, 'Duplicate column name') !== false) {
            echo "Title column already exists.<br>";
        } else {
            echo "Error altering table: " . $conn->error . "<br>";
        }
    }
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage() . "<br>";
}

$conn->close();
?>
