<?php
require_once 'config/database.php';

$sql = "
CREATE TABLE IF NOT EXISTS gallery_items (
    id INT(11) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    image_path VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
";

if ($conn->query($sql) === TRUE) {
    echo "Table 'gallery_items' created successfully or already exists.<br>";
} else {
    echo "Error creating table: " . $conn->error . "<br>";
}

$conn->close();
?>