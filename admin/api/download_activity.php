<?php
require_once __DIR__ . '/../includes/session.php';
require_once '../../config/database.php';
require_once '../../includes/functions.php';

// Check if the admin is logged in and has super role
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'super') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized access.']);
    exit;
}

// Fetch all user activity logs with usernames
$activity_log_stmt = $conn->prepare("SELECT ual.action, u.username, ual.timestamp FROM user_activity_log ual LEFT JOIN users u ON ual.user_id = u.id ORDER BY ual.timestamp DESC");
$activity_log_stmt->execute();
$activity_log_result = $activity_log_stmt->get_result();

$filename = 'user_activity_log_' . date('Y-m-d_H-i-s') . '.txt';

header('Content-Type: text/plain');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Add header to TXT file
fwrite($output, "========================================\n");
fwrite($output, "USER ACTIVITY LOG REPORT\n");
fwrite($output, "Generated: " . date('Y-m-d H:i:s') . "\n");
fwrite($output, "========================================\n\n");

// Add data to TXT file in a readable format
while ($row = $activity_log_result->fetch_assoc()) {
    fwrite($output, "User: " . htmlspecialchars($row['username'] ?? 'N/A') . "\n");
    fwrite($output, "Action: " . htmlspecialchars($row['action']) . "\n");
    fwrite($output, "Timestamp: " . htmlspecialchars($row['timestamp']) . "\n");
    fwrite($output, "----------------------------------------\n\n");
}

fwrite($output, "========================================\n");
fwrite($output, "End of Report\n");
fwrite($output, "========================================\n");

fclose($output);
exit;
?>
