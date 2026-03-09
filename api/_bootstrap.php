<?php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

require_once __DIR__ . '/../config/env.php';
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json; charset=utf-8');

function api_send(array $payload, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

function api_input(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === false || $raw === '') {
        return [];
    }
    $decoded = json_decode($raw, true);
    return is_array($decoded) ? $decoded : [];
}

function api_asset_url(string $path): string
{
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
        return $path;
    }
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $normalized = ltrim($path, '/');
    
    // Ensure uploads/ prefix if not already present
    if (!str_starts_with($normalized, 'uploads/')) {
        $normalized = 'uploads/' . $normalized;
    }

    // Derive app base path from the runtime URL path:
    // /dd4/api/*.php -> /dd4
    // /api/*.php     -> (root)
    $scriptName = str_replace('\\', '/', (string)($_SERVER['SCRIPT_NAME'] ?? '/api/index.php'));
    $apiPath = rtrim(dirname($scriptName), '/');
    $appBasePath = rtrim(dirname($apiPath), '/');
    if ($appBasePath === '' || $appBasePath === '.' || $appBasePath === '/') {
        $appBasePath = '';
    }

    return $scheme . '://' . $host . $appBasePath . '/' . $normalized;
}

function api_get_bearer_token(): ?string
{
    $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['Authorization'] ?? '';
    if ($auth === '' && function_exists('apache_request_headers')) {
        $headers = apache_request_headers();
        $auth = $headers['Authorization'] ?? '';
    }
    if (preg_match('/Bearer\s+(.+)/i', $auth, $m) === 1) {
        return trim($m[1]);
    }
    return null;
}

function api_create_token(mysqli $conn, int $userId): ?string
{
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
    $stmt = $conn->prepare('INSERT INTO user_tokens (user_id, token, expires_at) VALUES (?, ?, ?)');
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('iss', $userId, $token, $expires);
    if (!$stmt->execute()) {
        return null;
    }
    return $token;
}

function api_auth_user(mysqli $conn): ?array
{
    $token = api_get_bearer_token();
    if (!$token) {
        return null;
    }

    $stmt = $conn->prepare(
        'SELECT u.id, u.username, u.email, u.role
         FROM user_tokens t
         JOIN users u ON u.id = t.user_id
         WHERE t.token = ? AND t.expires_at > NOW()
         LIMIT 1'
    );
    if (!$stmt) {
        return null;
    }
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    return $user ?: null;
}

function api_require_user(mysqli $conn): array
{
    $user = api_auth_user($conn);
    if (!$user) {
        api_send(['status' => 'error', 'message' => 'Unauthorized'], 401);
    }
    return $user;
}

function api_bind_params(mysqli_stmt $stmt, string $types, array &$params): bool
{
    if ($types === '') {
        return true;
    }
    $refs = [];
    $refs[] = &$types;
    foreach ($params as $k => &$value) {
        $refs[] = &$value;
    }
    return call_user_func_array([$stmt, 'bind_param'], $refs);
}
