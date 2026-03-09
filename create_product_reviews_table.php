<?php
require_once 'config/database.php';

$sql = "CREATE TABLE IF NOT EXISTS product_reviews (
    id INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
    product_id INT(10) UNSIGNED NOT NULL,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(255) DEFAULT NULL,
    rating TINYINT(1) UNSIGNED NOT NULL,
    review_text TEXT NOT NULL,
    status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    approved_at TIMESTAMP NULL DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_product_status (product_id, status),
    CONSTRAINT fk_product_reviews_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql) === TRUE) {
    echo "Table product_reviews created successfully";
} else {
    echo "Error creating table: " . $conn->error;
}

$conn->close();
?>
