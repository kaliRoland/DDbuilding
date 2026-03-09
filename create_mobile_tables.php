<?php
require_once __DIR__ . '/config/database.php';

$queries = [
    "CREATE TABLE IF NOT EXISTS user_tokens (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(10) UNSIGNED NOT NULL,
        token VARCHAR(128) NOT NULL UNIQUE,
        expires_at DATETIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_user_tokens_user_id (user_id),
        INDEX idx_user_tokens_expires_at (expires_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS support_tickets (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(10) UNSIGNED NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        channel VARCHAR(50) NOT NULL DEFAULT 'ticket',
        contact_name VARCHAR(150) NULL,
        contact_email VARCHAR(150) NULL,
        contact_phone VARCHAR(50) NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_support_user_id (user_id),
        INDEX idx_support_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS solar_installation_requests (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(10) UNSIGNED NULL,
        location VARCHAR(255) NOT NULL,
        system_size VARCHAR(100) NOT NULL,
        contact_name VARCHAR(150) NOT NULL,
        contact_email VARCHAR(150) NULL,
        contact_phone VARCHAR(50) NOT NULL,
        notes TEXT NULL,
        preferred_visit_date VARCHAR(50) NULL,
        attachment_path VARCHAR(255) NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_solar_user_id (user_id),
        INDEX idx_solar_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS support_requests (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        request_type VARCHAR(30) NOT NULL,
        user_id INT(10) UNSIGNED NULL,
        subject VARCHAR(200) NOT NULL,
        message TEXT NOT NULL,
        location VARCHAR(255) NULL,
        system_size VARCHAR(100) NULL,
        preferred_visit_date VARCHAR(50) NULL,
        channel VARCHAR(50) NOT NULL DEFAULT 'mobile_app',
        contact_name VARCHAR(150) NULL,
        contact_email VARCHAR(150) NULL,
        contact_phone VARCHAR(50) NULL,
        status VARCHAR(30) NOT NULL DEFAULT 'new',
        priority VARCHAR(20) NOT NULL DEFAULT 'medium',
        admin_response TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_support_requests_user_id (user_id),
        INDEX idx_support_requests_type (request_type),
        INDEX idx_support_requests_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    "CREATE TABLE IF NOT EXISTS customer_notifications (
        id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT(10) UNSIGNED NULL,
        support_request_id BIGINT UNSIGNED NULL,
        contact_email VARCHAR(150) NULL,
        contact_phone VARCHAR(50) NULL,
        channel VARCHAR(30) NOT NULL DEFAULT 'in_app',
        title VARCHAR(200) NOT NULL,
        body TEXT NOT NULL,
        is_read TINYINT(1) NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_customer_notifications_user_id (user_id),
        INDEX idx_customer_notifications_request_id (support_request_id),
        INDEX idx_customer_notifications_is_read (is_read)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4"
];

$allOk = true;
foreach ($queries as $sql) {
    if (!$conn->query($sql)) {
        $allOk = false;
        echo "Error: " . $conn->error . "<br>";
    }
}

if ($allOk) {
    echo "Mobile API tables created or already exist.";
}

$conn->close();
