<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';
require_once __DIR__ . '/../includes/support_workflow.php';

if (!function_exists('dd_api_debug_log')) {
    function dd_api_debug_log(string $message, array $context = []): void
    {
        $line = '[' . date('Y-m-d H:i:s') . '] ' . $message;
        if (!empty($context)) {
            $line .= ' ' . json_encode($context);
        }
        $line .= PHP_EOL;
        @file_put_contents(__DIR__ . '/debug_support_api.log', $line, FILE_APPEND);
    }
}

register_shutdown_function(static function (): void {
    $e = error_get_last();
    if ($e !== null && in_array((int)$e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        dd_api_debug_log('fatal_shutdown', $e);
    }
});

set_exception_handler(static function (Throwable $e): void {
    dd_api_debug_log('uncaught_exception', [
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString(),
    ]);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
    }
    echo json_encode(['status' => 'error', 'message' => 'Internal server error']);
    exit;
});

dd_support_ensure_tables($conn);

$action = $_GET['action'] ?? 'submit';

if ($action === 'replies') {
    $user = api_require_user($conn);
    $userId = (int)$user['id'];
    $requestId = (int)($_GET['request_id'] ?? 0);
    if ($requestId <= 0) {
        api_send(['status' => 'error', 'message' => 'request_id is required'], 400);
    }

    $reqStmt = $conn->prepare(
        'SELECT id, user_id, request_type, subject, message, admin_response, status, created_at, updated_at
         FROM support_requests
         WHERE id = ? AND user_id = ?
         LIMIT 1'
    );
    if (!$reqStmt) {
        api_send(['status' => 'error', 'message' => $conn->error], 500);
    }
    $reqStmt->bind_param('ii', $requestId, $userId);
    $reqStmt->execute();
    $reqResult = $reqStmt->get_result();
    $request = $reqResult ? $reqResult->fetch_assoc() : null;
    if (!$request) {
        api_send(['status' => 'error', 'message' => 'Request not found'], 404);
    }

    $thread = [[
        'id' => 0,
        'support_request_id' => $requestId,
        'sender_role' => 'customer',
        'message' => (string)($request['message'] ?? ''),
        'created_at' => (string)($request['created_at'] ?? ''),
        'kind' => 'initial',
    ]];

    $adminResponse = trim((string)($request['admin_response'] ?? ''));
    if ($adminResponse !== '') {
        $thread[] = [
            'id' => 0,
            'support_request_id' => $requestId,
            'sender_role' => 'admin',
            'message' => $adminResponse,
            'created_at' => (string)($request['updated_at'] ?? $request['created_at'] ?? ''),
            'kind' => 'admin_response',
        ];
    }

    $stmt = $conn->prepare(
        'SELECT id, support_request_id, sender_role, message, created_at
         FROM support_request_replies
         WHERE support_request_id = ?
         ORDER BY created_at ASC, id ASC'
    );
    if (!$stmt) {
        api_send(['status' => 'error', 'message' => $conn->error], 500);
    }
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $thread[] = $row;
    }

    api_send(['status' => 'success', 'replies' => $thread, 'request' => $request]);
}

if ($action === 'reply') {
    $user = api_require_user($conn);
    $userId = (int)$user['id'];
    $input = api_input();
    $requestId = (int)($input['request_id'] ?? $_POST['request_id'] ?? 0);
    $message = trim((string)($input['message'] ?? $_POST['message'] ?? ''));
    if ($requestId <= 0 || $message === '') {
        api_send(['status' => 'error', 'message' => 'request_id and message are required'], 400);
    }

    $reqStmt = $conn->prepare(
        'SELECT id, request_type, subject, status, contact_name, contact_email, contact_phone
         FROM support_requests
         WHERE id = ? AND user_id = ?
         LIMIT 1'
    );
    if (!$reqStmt) {
        api_send(['status' => 'error', 'message' => $conn->error], 500);
    }
    $reqStmt->bind_param('ii', $requestId, $userId);
    $reqStmt->execute();
    $reqResult = $reqStmt->get_result();
    $request = $reqResult ? $reqResult->fetch_assoc() : null;
    if (!$request) {
        api_send(['status' => 'error', 'message' => 'Request not found'], 404);
    }

    $stmt = $conn->prepare(
        'INSERT INTO support_request_replies
         (support_request_id, user_id, sender_role, message, created_at)
         VALUES (?, ?, "customer", ?, NOW())'
    );
    if (!$stmt) {
        api_send(['status' => 'error', 'message' => $conn->error], 500);
    }
    $stmt->bind_param('iis', $requestId, $userId, $message);
    if (!$stmt->execute()) {
        api_send(['status' => 'error', 'message' => $stmt->error], 500);
    }

    $up = $conn->prepare('UPDATE support_requests SET updated_at = NOW() WHERE id = ?');
    if ($up) {
        $up->bind_param('i', $requestId);
        $up->execute();
    }

    dd_support_notify_admin_new_request([
        'id' => $requestId,
        'request_type' => (string)($request['request_type'] ?? 'support'),
        'subject' => 'Customer reply: ' . (string)($request['subject'] ?? ''),
        'message' => $message,
        'contact_name' => (string)($request['contact_name'] ?? ''),
        'contact_email' => (string)($request['contact_email'] ?? ''),
        'contact_phone' => (string)($request['contact_phone'] ?? ''),
        'status' => (string)($request['status'] ?? 'in_progress'),
        'created_at' => date('Y-m-d H:i:s'),
    ]);

    api_send(['status' => 'success', 'message' => 'Reply sent']);
}

if ($action === 'list') {
    $user = api_require_user($conn);
    $userId = (int)$user['id'];
    $type = trim((string)($_GET['type'] ?? ''));

    $sql = 'SELECT id, request_type, subject, message, location, system_size, preferred_visit_date, channel, contact_name, contact_email, contact_phone, status, priority, admin_response, created_at, updated_at
            FROM support_requests
            WHERE user_id = ?';
    if ($type !== '') {
        $sql .= ' AND request_type = ?';
    }
    $sql .= ' ORDER BY created_at DESC';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        api_send(['status' => 'error', 'message' => $conn->error], 500);
    }
    if ($type !== '') {
        $stmt->bind_param('is', $userId, $type);
    } else {
        $stmt->bind_param('i', $userId);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = [];
    while ($row = $result->fetch_assoc()) {
        $requests[] = $row;
    }

    api_send(['status' => 'success', 'requests' => $requests, 'tickets' => $requests]);
}

if ($action === 'notifications') {
    $user = api_require_user($conn);
    $userId = (int)$user['id'];
    $onlyUnread = (int)($_GET['unread_only'] ?? 0) === 1;

    $sql = 'SELECT id, support_request_id, channel, title, body, is_read, created_at
            FROM customer_notifications
            WHERE user_id = ?';
    if ($onlyUnread) {
        $sql .= ' AND is_read = 0';
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 100';

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        api_send(['status' => 'error', 'message' => $conn->error], 500);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $notifications[] = $row;
    }
    api_send(['status' => 'success', 'notifications' => $notifications]);
}

if ($action === 'mark_notification_read') {
    $user = api_require_user($conn);
    $userId = (int)$user['id'];
    $input = api_input();
    $id = (int)($input['id'] ?? $_POST['id'] ?? 0);
    if ($id <= 0) {
        api_send(['status' => 'error', 'message' => 'Notification ID is required'], 400);
    }
    $stmt = $conn->prepare('UPDATE customer_notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
    if (!$stmt) {
        api_send(['status' => 'error', 'message' => $conn->error], 500);
    }
    $stmt->bind_param('ii', $id, $userId);
    $stmt->execute();
    api_send(['status' => 'success']);
}

if ($action === 'update_status') {
    $admin = api_require_user($conn);
    if (($admin['role'] ?? '') !== 'admin' && ($admin['role'] ?? '') !== 'super') {
        api_send(['status' => 'error', 'message' => 'Forbidden'], 403);
    }
    $input = api_input();
    $requestId = (int)($input['request_id'] ?? $_POST['request_id'] ?? 0);
    $status = trim((string)($input['status'] ?? $_POST['status'] ?? ''));
    $adminResponse = trim((string)($input['admin_response'] ?? $_POST['admin_response'] ?? ''));
    $valid = ['new', 'in_progress', 'scheduled', 'resolved', 'closed'];
    if ($requestId <= 0 || !in_array($status, $valid, true)) {
        api_send(['status' => 'error', 'message' => 'Invalid request_id or status'], 400);
    }

    $getStmt = $conn->prepare('SELECT * FROM support_requests WHERE id = ? LIMIT 1');
    if (!$getStmt) {
        api_send(['status' => 'error', 'message' => $conn->error], 500);
    }
    $getStmt->bind_param('i', $requestId);
    $getStmt->execute();
    $res = $getStmt->get_result();
    $request = $res ? $res->fetch_assoc() : null;
    if (!$request) {
        api_send(['status' => 'error', 'message' => 'Request not found'], 404);
    }

    $stmt = $conn->prepare('UPDATE support_requests SET status = ?, admin_response = ?, updated_at = NOW() WHERE id = ?');
    if (!$stmt) {
        api_send(['status' => 'error', 'message' => $conn->error], 500);
    }
    $stmt->bind_param('ssi', $status, $adminResponse, $requestId);
    if (!$stmt->execute()) {
        api_send(['status' => 'error', 'message' => $stmt->error], 500);
    }

    dd_support_notify_customer_status($conn, $request, $status, $adminResponse);
    api_send(['status' => 'success']);
}

$input = api_input();
$user = api_auth_user($conn);
$subject = trim((string)($input['subject'] ?? $_POST['subject'] ?? ''));
$message = trim((string)($input['message'] ?? $_POST['message'] ?? ''));
$channel = trim((string)($input['channel'] ?? $_POST['channel'] ?? 'mobile_app'));
$contactName = trim((string)($input['contact_name'] ?? $_POST['contact_name'] ?? ($user['username'] ?? '')));
$contactEmail = trim((string)($input['contact_email'] ?? $_POST['contact_email'] ?? ($user['email'] ?? '')));
$contactPhone = trim((string)($input['contact_phone'] ?? $_POST['contact_phone'] ?? ''));
$userId = $user ? (int)$user['id'] : null;

if ($subject === '' || $message === '') {
    dd_api_debug_log('validation_failed', [
        'subject' => $subject,
        'message_len' => strlen($message),
        'contact_name' => $contactName,
        'contact_phone' => $contactPhone,
    ]);
    api_send(['status' => 'error', 'message' => 'Subject and message are required'], 400);
}

$type = 'support';
$priority = 'medium';
$status = 'new';
$userIdDb = $userId ?? 0;
$stmt = $conn->prepare(
    'INSERT INTO support_requests
     (request_type, user_id, subject, message, channel, contact_name, contact_email, contact_phone, status, priority, created_at, updated_at)
     VALUES (?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
);
if (!$stmt) {
    dd_api_debug_log('prepare_failed', ['error' => $conn->error]);
    api_send(['status' => 'error', 'message' => $conn->error], 500);
}
$stmt->bind_param(
    'sissssssss',
    $type,
    $userIdDb,
    $subject,
    $message,
    $channel,
    $contactName,
    $contactEmail,
    $contactPhone,
    $status,
    $priority
);
if (!$stmt->execute()) {
    dd_api_debug_log('execute_failed', ['error' => $stmt->error]);
    api_send(['status' => 'error', 'message' => $stmt->error], 500);
}

$id = (int)$stmt->insert_id;

dd_support_insert_customer_notification(
    $conn,
    $userId,
    $id,
    $contactEmail !== '' ? $contactEmail : null,
    $contactPhone !== '' ? $contactPhone : null,
    'in_app',
    'Support ticket received',
    'We received your support request #' . $id . '. Our team will respond shortly.'
);

dd_support_notify_admin_new_request([
    'id' => $id,
    'request_type' => $type,
    'subject' => $subject,
    'message' => $message,
    'contact_name' => $contactName,
    'contact_email' => $contactEmail,
    'contact_phone' => $contactPhone,
    'status' => $status,
    'created_at' => date('Y-m-d H:i:s'),
]);

dd_api_debug_log('submit_success', ['id' => $id, 'type' => $type]);
api_send(['status' => 'success', 'ticket_id' => $id, 'request_id' => $id], 201);
