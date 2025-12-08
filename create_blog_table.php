<?php
require_once 'config/database.php';

$sql = "CREATE TABLE IF NOT EXISTS blog_posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table blog_posts created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
