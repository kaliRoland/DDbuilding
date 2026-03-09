<?php
declare(strict_types=1);

function dd_support_ensure_tables(mysqli $conn): void
{
    $queries = [
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
            INDEX idx_support_requests_status (status),
            INDEX idx_support_requests_created_at (created_at)
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        "CREATE TABLE IF NOT EXISTS support_request_replies (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            support_request_id BIGINT UNSIGNED NOT NULL,
            user_id INT(10) UNSIGNED NULL,
            sender_role VARCHAR(20) NOT NULL DEFAULT 'customer',
            message TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_support_replies_request_id (support_request_id),
            INDEX idx_support_replies_user_id (user_id),
            INDEX idx_support_replies_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
    ];

    foreach ($queries as $sql) {
        $conn->query($sql);
    }
}

function dd_support_insert_customer_notification(
    mysqli $conn,
    ?int $userId,
    ?int $supportRequestId,
    ?string $contactEmail,
    ?string $contactPhone,
    string $channel,
    string $title,
    string $body
): void {
    $userIdDb = $userId ?? 0;
    $supportRequestIdDb = $supportRequestId ?? 0;
    $stmt = $conn->prepare(
        "INSERT INTO customer_notifications
        (user_id, support_request_id, contact_email, contact_phone, channel, title, body, is_read, created_at)
        VALUES (NULLIF(?, 0), NULLIF(?, 0), ?, ?, ?, ?, ?, 0, NOW())"
    );
    if (!$stmt) {
        return;
    }
    $stmt->bind_param(
        'iisssss',
        $userIdDb,
        $supportRequestIdDb,
        $contactEmail,
        $contactPhone,
        $channel,
        $title,
        $body
    );
    $stmt->execute();
}

function dd_support_send_mail(string $to, string $subject, string $body): bool
{
    $to = trim($to);
    if ($to === '') {
        return false;
    }

    $smtpHost = trim((string)(getenv('SMTP_HOST') ?: ''));
    if ($smtpHost !== '') {
        return dd_support_send_mail_smtp($to, $subject, $body);
    }

    if (!function_exists('mail')) {
        return false;
    }

    $from = getenv('SUPPORT_FROM_EMAIL') ?: 'no-reply@ddbuildingtech.com';
    $headers = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/plain;charset=UTF-8\r\n";
    $headers .= "From: DDbuildingtech <{$from}>\r\n";
    return @mail($to, $subject, $body, $headers);
}

function dd_support_send_mail_smtp(string $to, string $subject, string $body): bool
{
    $host = trim((string)(getenv('SMTP_HOST') ?: ''));
    $port = (int)(getenv('SMTP_PORT') ?: 587);
    $username = trim((string)(getenv('SMTP_USERNAME') ?: ''));
    $password = (string)(getenv('SMTP_PASSWORD') ?: '');
    $encryption = strtolower(trim((string)(getenv('SMTP_ENCRYPTION') ?: 'tls')));
    $timeout = (int)(getenv('SMTP_TIMEOUT') ?: 20);
    $from = trim((string)(getenv('SUPPORT_FROM_EMAIL') ?: $username));
    if ($host === '' || $username === '' || $password === '' || $from === '') {
        @file_put_contents(
            __DIR__ . '/../support_alerts.log',
            '[' . date('Y-m-d H:i:s') . '] smtp_config_invalid host=' . $host .
            ' port=' . $port .
            ' enc=' . $encryption .
            ' user=' . dd_mask_email_for_log($username) .
            ' from=' . dd_mask_email_for_log($from) . PHP_EOL,
            FILE_APPEND
        );
        return false;
    }

    $remote = ($encryption === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;
    @file_put_contents(
        __DIR__ . '/../support_alerts.log',
        '[' . date('Y-m-d H:i:s') . '] smtp_connect_attempt host=' . $host .
        ' port=' . $port .
        ' enc=' . $encryption .
        ' user=' . dd_mask_email_for_log($username) .
        ' from=' . dd_mask_email_for_log($from) .
        ' to=' . dd_mask_email_for_log($to) . PHP_EOL,
        FILE_APPEND
    );
    $fp = @stream_socket_client($remote, $errno, $errstr, $timeout);
    if (!$fp) {
        @file_put_contents(
            __DIR__ . '/../support_alerts.log',
            '[' . date('Y-m-d H:i:s') . '] smtp_connect_failed ' . $errno . ' ' . $errstr . PHP_EOL,
            FILE_APPEND
        );
        return false;
    }

    stream_set_timeout($fp, $timeout);
    try {
        dd_smtp_expect($fp, [220]);
        dd_smtp_cmd($fp, 'EHLO ddbuildingtech.com', [250]);

        if ($encryption === 'tls') {
            dd_smtp_cmd($fp, 'STARTTLS', [220]);
            $crypto = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            if ($crypto !== true) {
                throw new RuntimeException('STARTTLS failed');
            }
            dd_smtp_cmd($fp, 'EHLO ddbuildingtech.com', [250]);
        }

        dd_smtp_cmd($fp, 'AUTH LOGIN', [334]);
        dd_smtp_cmd($fp, base64_encode($username), [334]);
        dd_smtp_cmd($fp, base64_encode($password), [235]);

        dd_smtp_cmd($fp, 'MAIL FROM:<' . $from . '>', [250]);
        dd_smtp_cmd($fp, 'RCPT TO:<' . $to . '>', [250, 251]);
        dd_smtp_cmd($fp, 'DATA', [354]);

        $headers = [];
        $headers[] = 'From: DDbuildingtech <' . $from . '>';
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'Subject: ' . $subject;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $payload = implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.";
        fwrite($fp, $payload . "\r\n");
        dd_smtp_expect($fp, [250]);
        dd_smtp_cmd($fp, 'QUIT', [221]);
        fclose($fp);
        @file_put_contents(
            __DIR__ . '/../support_alerts.log',
            '[' . date('Y-m-d H:i:s') . '] smtp_send_success to=' . dd_mask_email_for_log($to) . ' subject=' . $subject . PHP_EOL,
            FILE_APPEND
        );
        return true;
    } catch (Throwable $e) {
        @file_put_contents(
            __DIR__ . '/../support_alerts.log',
            '[' . date('Y-m-d H:i:s') . '] smtp_send_failed ' . $e->getMessage() . PHP_EOL,
            FILE_APPEND
        );
        if (is_resource($fp)) {
            @fclose($fp);
        }
        return false;
    }
}

function dd_mask_email_for_log(string $value): string
{
    $value = trim($value);
    if ($value === '') {
        return '';
    }
    if (!str_contains($value, '@')) {
        return '***';
    }
    [$local, $domain] = explode('@', $value, 2);
    $localMasked = strlen($local) <= 2 ? str_repeat('*', strlen($local)) : (substr($local, 0, 2) . '***');
    return $localMasked . '@' . $domain;
}

function dd_smtp_cmd($fp, string $command, array $expectedCodes): void
{
    fwrite($fp, $command . "\r\n");
    dd_smtp_expect($fp, $expectedCodes);
}

function dd_smtp_expect($fp, array $expectedCodes): void
{
    $response = '';
    while (($line = fgets($fp, 515)) !== false) {
        $response .= $line;
        if (strlen($line) >= 4 && $line[3] === ' ') {
            break;
        }
    }
    if ($response === '') {
        throw new RuntimeException('Empty SMTP response');
    }
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, $expectedCodes, true)) {
        throw new RuntimeException('SMTP ' . $code . ' ' . trim($response));
    }
}

function dd_support_notify_admin_new_request(array $request): void
{
    $adminEmail = trim((string)(getenv('ADMIN_ALERT_EMAIL') ?: ''));
    $title = '[DD] New ' . strtoupper((string)$request['request_type']) . ' request #' . (string)$request['id'];
    $lines = [
        'A new request was submitted.',
        'Request ID: ' . (string)$request['id'],
        'Type: ' . (string)$request['request_type'],
        'Subject: ' . (string)$request['subject'],
        'Customer: ' . (string)$request['contact_name'],
        'Phone: ' . (string)$request['contact_phone'],
        'Email: ' . (string)$request['contact_email'],
        'Status: ' . (string)$request['status'],
        'Created At: ' . (string)$request['created_at'],
        '',
        'Message:',
        (string)$request['message'],
    ];
    if (!empty($request['location'])) {
        $lines[] = '';
        $lines[] = 'Location: ' . (string)$request['location'];
    }
    if (!empty($request['system_size'])) {
        $lines[] = 'System Size: ' . (string)$request['system_size'];
    }
    if (!empty($request['preferred_visit_date'])) {
        $lines[] = 'Preferred Visit Date: ' . (string)$request['preferred_visit_date'];
    }
    $body = implode("\n", $lines);

    if ($adminEmail !== '') {
        dd_support_send_mail($adminEmail, $title, $body);
    }

    $logLine = '[' . date('Y-m-d H:i:s') . '] ' . $title . ' :: '
        . 'name=' . (string)$request['contact_name'] . ' phone=' . (string)$request['contact_phone'] . "\n";
    @file_put_contents(__DIR__ . '/../support_alerts.log', $logLine, FILE_APPEND);
}

function dd_support_notify_customer_status(
    mysqli $conn,
    array $request,
    string $status,
    string $adminResponse
): void {
    $typeLabel = $request['request_type'] === 'installation' ? 'Installation request' : 'Support ticket';
    $title = $typeLabel . ' #' . (string)$request['id'] . ' updated';
    $body = 'Status: ' . $status;
    if (trim($adminResponse) !== '') {
        $body .= "\nResponse: " . trim($adminResponse);
    }

    dd_support_insert_customer_notification(
        $conn,
        isset($request['user_id']) ? (int)$request['user_id'] : null,
        (int)$request['id'],
        $request['contact_email'] ?? null,
        $request['contact_phone'] ?? null,
        'in_app',
        $title,
        $body
    );

    if (!empty($request['contact_email'])) {
        dd_support_send_mail((string)$request['contact_email'], '[DD] ' . $title, $body);
    }
}
