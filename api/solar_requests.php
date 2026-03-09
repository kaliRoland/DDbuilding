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
        @file_put_contents(__DIR__ . '/debug_solar_api.log', $line, FILE_APPEND);
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

if ($action === 'list') {
    $user = api_require_user($conn);
    $userId = (int)$user['id'];
    $stmt = $conn->prepare(
        'SELECT id, subject, message, location, system_size, contact_name, contact_email, contact_phone, preferred_visit_date, status, priority, admin_response, created_at, updated_at
         FROM support_requests
         WHERE user_id = ? AND request_type = "installation"
         ORDER BY created_at DESC'
    );
    if (!$stmt) {
        api_send(['status' => 'error', 'message' => $conn->error], 500);
    }
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    api_send(['status' => 'success', 'requests' => $items]);
}

$input = api_input();
$user = api_auth_user($conn);
$userId = $user ? (int)$user['id'] : null;

$location = trim((string)($input['location'] ?? $_POST['location'] ?? ''));
$systemSize = trim((string)($input['system_size'] ?? $_POST['system_size'] ?? ''));
$contactName = trim((string)($input['contact_name'] ?? $_POST['contact_name'] ?? ($user['username'] ?? '')));
$contactEmail = trim((string)($input['contact_email'] ?? $_POST['contact_email'] ?? ($user['email'] ?? '')));
$contactPhone = trim((string)($input['contact_phone'] ?? $_POST['contact_phone'] ?? ''));
$notes = trim((string)($input['notes'] ?? $_POST['notes'] ?? ''));
$visitDate = trim((string)($input['preferred_visit_date'] ?? $_POST['preferred_visit_date'] ?? ''));
$channel = trim((string)($input['channel'] ?? $_POST['channel'] ?? 'mobile_app'));

if ($location === '' || $systemSize === '' || $contactName === '' || $contactPhone === '') {
    dd_api_debug_log('validation_failed', [
        'location' => $location,
        'system_size' => $systemSize,
        'contact_name' => $contactName,
        'contact_phone' => $contactPhone,
    ]);
    api_send(['status' => 'error', 'message' => 'location, system_size, contact_name, and contact_phone are required'], 400);
}

$subject = 'Installation Request - ' . $systemSize;
$message = $notes !== '' ? $notes : 'Customer requested installation.';
$type = 'installation';
$status = 'new';
$priority = 'medium';
$userIdDb = $userId ?? 0;

$stmt = $conn->prepare(
    'INSERT INTO support_requests
     (request_type, user_id, subject, message, location, system_size, preferred_visit_date, channel, contact_name, contact_email, contact_phone, status, priority, created_at, updated_at)
     VALUES (?, NULLIF(?, 0), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())'
);
if (!$stmt) {
    dd_api_debug_log('prepare_failed', ['error' => $conn->error]);
    api_send(['status' => 'error', 'message' => $conn->error], 500);
}
$stmt->bind_param(
    'sisssssssssss',
    $type,
    $userIdDb,
    $subject,
    $message,
    $location,
    $systemSize,
    $visitDate,
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
    'Installation request received',
    'We received your installation request #' . $id . '. We will contact you to schedule a visit.'
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
    'location' => $location,
    'system_size' => $systemSize,
    'preferred_visit_date' => $visitDate,
]);

dd_api_debug_log('submit_success', ['id' => $id, 'type' => $type]);
api_send(['status' => 'success', 'request_id' => $id], 201);
