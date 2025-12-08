<?php
require_once 'config/database.php';

$sql = "
ALTER TABLE gallery_items
ADD COLUMN image_path_1 VARCHAR(255) DEFAULT NULL,
ADD COLUMN image_path_2 VARCHAR(255) DEFAULT NULL,
ADD COLUMN image_path_3 VARCHAR(255) DEFAULT NULL,
ADD COLUMN image_path_4 VARCHAR(255) DEFAULT NULL,
ADD COLUMN image_path_5 VARCHAR(255) DEFAULT NULL,
ADD COLUMN youtube_url VARCHAR(255) DEFAULT NULL,
DROP COLUMN image_path;
";

if ($conn->multi_query($sql) === TRUE) {
    echo "Table 'gallery_items' altered successfully.<br>";
} else {
    echo "Error altering table: " . $conn->error . "<br>";
}

// It's important to consume all results from a multi_query
while ($conn->next_result()) {
    if (!$conn->more_results()) break;
}

$conn->close();
?>