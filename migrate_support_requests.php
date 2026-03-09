<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/support_workflow.php';

dd_support_ensure_tables($conn);

$migratedSupport = 0;
$migratedInstall = 0;

$supportRes = $conn->query("SELECT id, user_id, subject, message, channel, contact_name, contact_email, contact_phone, status, created_at FROM support_tickets");
if ($supportRes) {
    while ($row = $supportRes->fetch_assoc()) {
        $legacyId = (int)$row['id'];
        $check = $conn->prepare(
            "SELECT id FROM support_requests
             WHERE request_type = 'support' AND subject = ? AND created_at = ?
             LIMIT 1"
        );
        $check->bind_param('ss', $row['subject'], $row['created_at']);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        if ($exists) {
            continue;
        }

        $statusMap = [
            'open' => 'new',
            'pending' => 'new',
            'processing' => 'in_progress',
            'resolved' => 'resolved',
            'closed' => 'closed',
        ];
        $status = $statusMap[$row['status']] ?? 'new';
        $priority = 'medium';
        $type = 'support';
        $stmt = $conn->prepare(
            "INSERT INTO support_requests
            (request_type, user_id, subject, message, channel, contact_name, contact_email, contact_phone, status, priority, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $updatedAt = $row['created_at'];
        $stmt->bind_param(
            'sissssssssss',
            $type,
            $row['user_id'],
            $row['subject'],
            $row['message'],
            $row['channel'],
            $row['contact_name'],
            $row['contact_email'],
            $row['contact_phone'],
            $status,
            $priority,
            $row['created_at'],
            $updatedAt
        );
        if ($stmt->execute()) {
            $migratedSupport++;
        }
    }
}

$installRes = $conn->query(
    "SELECT id, user_id, location, system_size, contact_name, contact_email, contact_phone, notes, preferred_visit_date, status, created_at
     FROM solar_installation_requests"
);
if ($installRes) {
    while ($row = $installRes->fetch_assoc()) {
        $subject = 'Installation Request - ' . $row['system_size'];
        $check = $conn->prepare(
            "SELECT id FROM support_requests
             WHERE request_type = 'installation' AND contact_phone = ? AND created_at = ?
             LIMIT 1"
        );
        $check->bind_param('ss', $row['contact_phone'], $row['created_at']);
        $check->execute();
        $exists = $check->get_result()->fetch_assoc();
        if ($exists) {
            continue;
        }

        $statusMap = [
            'open' => 'new',
            'pending' => 'new',
            'processing' => 'in_progress',
            'resolved' => 'resolved',
            'closed' => 'closed',
        ];
        $status = $statusMap[$row['status']] ?? 'new';
        $priority = 'medium';
        $type = 'installation';
        $channel = 'legacy_migration';
        $message = (string)($row['notes'] ?? 'Customer requested installation.');
        $stmt = $conn->prepare(
            "INSERT INTO support_requests
            (request_type, user_id, subject, message, location, system_size, preferred_visit_date, channel, contact_name, contact_email, contact_phone, status, priority, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $updatedAt = $row['created_at'];
        $stmt->bind_param(
            'sisssssssssssss',
            $type,
            $row['user_id'],
            $subject,
            $message,
            $row['location'],
            $row['system_size'],
            $row['preferred_visit_date'],
            $channel,
            $row['contact_name'],
            $row['contact_email'],
            $row['contact_phone'],
            $status,
            $priority,
            $row['created_at'],
            $updatedAt
        );
        if ($stmt->execute()) {
            $migratedInstall++;
        }
    }
}

echo 'Support rows migrated: ' . $migratedSupport . PHP_EOL;
echo 'Installation rows migrated: ' . $migratedInstall . PHP_EOL;

