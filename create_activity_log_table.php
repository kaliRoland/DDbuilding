<?php
require_once 'config/database.php';

$sql = "CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

if ($conn->query($sql) === TRUE) {
    echo "Table user_activity_log created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
