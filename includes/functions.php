<?php
function log_activity($conn, $user_id, $action) {
    if ($user_id === null) {
        // For guests, use a numeric representation of the session ID.
        $user_id = hexdec(substr(session_id(), 0, 8));
    }

    $stmt = $conn->prepare("INSERT INTO user_activity_log (user_id, action) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $action);
    if (!$stmt->execute()) {
        // Log error to a file
        $error_message = date('[Y-m-d H:i:s] ') . "Error logging activity: " . $stmt->error . "\n";
        file_put_contents(__DIR__ . '/../activity_log_errors.log', $error_message, FILE_APPEND);
    }
}
?>
