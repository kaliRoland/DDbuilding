<?php
require_once '../config/database.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? 'get_all';

switch ($action) {
    case 'get_all':
        $gallery_items = [];
        
        // Check if title column exists, if not use empty string
        $check_column = $conn->query("SHOW COLUMNS FROM gallery_items LIKE 'title'");
        $has_title = $check_column && $check_column->num_rows > 0;
        
        if ($has_title) {
            $result = $conn->query("SELECT id, title, description, youtube_url, image_path_1, image_path_2, image_path_3, image_path_4, image_path_5 FROM gallery_items ORDER BY created_at DESC");
        } else {
            $result = $conn->query("SELECT id, '' as title, description, youtube_url, image_path_1, image_path_2, image_path_3, image_path_4, image_path_5 FROM gallery_items ORDER BY created_at DESC");
        }
        
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $gallery_items[] = $row;
            }
            echo json_encode(['status' => 'success', 'items' => $gallery_items]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Database query failed: ' . $conn->error]);
        }
        break;
    default:
        echo json_encode(['status' => 'error', 'message' => 'Invalid action']);
        break;
}
?>