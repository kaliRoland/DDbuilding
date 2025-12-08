<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if the admin is logged in and has super role
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'super') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

// Fetch all user activity logs
$activity_log_stmt = $conn->prepare("SELECT user_id, action, timestamp FROM user_activity_log ORDER BY timestamp DESC");
$activity_log_stmt->execute();
$activity_log_result = $activity_log_stmt->get_result();

$filename = 'user_activity_log_' . date('Y-m-d_H-i-s') . '.csv';

header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add CSV headers
fputcsv($output, ['User ID', 'Action', 'Timestamp']);

// Add data to CSV
while ($row = $activity_log_result->fetch_assoc()) {
    fputcsv($output, [$row['user_id'], $row['action'], $row['timestamp']]);
}

fclose($output);
exit;
?>