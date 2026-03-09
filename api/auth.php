<?php
declare(strict_types=1);
require_once __DIR__ . '/_bootstrap.php';

$action = $_GET['action'] ?? 'me';
$input = api_input();

if ($action === 'register') {
    $username = trim((string)($input['username'] ?? ''));
    $email = trim((string)($input['email'] ?? ''));
    $password = (string)($input['password'] ?? '');

    if ($username === '' || $email === '' || strlen($password) < 6) {
        api_send(['status' => 'error', 'message' => 'Username, email, and password(>=6) are required'], 400);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $role = 'admin';
    $stmt = $conn->prepare('INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)');
    if (!$stmt) {
        api_send(['status' => 'error', 'message' => $conn->error], 500);
    }
    $stmt->bind_param('ssss', $username, $email, $hash, $role);
    if (!$stmt->execute()) {
        if ($conn->errno === 1062) {
            api_send(['status' => 'error', 'message' => 'Username or email already exists'], 409);
        }
        api_send(['status' => 'error', 'message' => $stmt->error], 500);
    }

    $userId = (int)$stmt->insert_id;
    $token = api_create_token($conn, $userId);
    if (!$token) {
        api_send(['status' => 'error', 'message' => 'Token creation failed'], 500);
    }
    api_send([
        'status' => 'success',
        'token' => $token,
        'user' => ['id' => $userId, 'username' => $username, 'email' => $email, 'role' => $role]
    ], 201);
}

if ($action === 'login') {
    $usernameOrEmail = trim((string)($input['username_or_email'] ?? ''));
    $password = (string)($input['password'] ?? '');
    if ($usernameOrEmail === '' || $password === '') {
        api_send(['status' => 'error', 'message' => 'Credentials are required'], 400);
    }

    $stmt = $conn->prepare(
        'SELECT id, username, email, password, role FROM users
         WHERE username = ? OR email = ?
         LIMIT 1'
    );
    $stmt->bind_param('ss', $usernameOrEmail, $usernameOrEmail);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result ? $result->fetch_assoc() : null;
    if (!$user || !password_verify($password, (string)$user['password'])) {
        api_send(['status' => 'error', 'message' => 'Invalid credentials'], 401);
    }

    $token = api_create_token($conn, (int)$user['id']);
    if (!$token) {
        api_send(['status' => 'error', 'message' => 'Token creation failed'], 500);
    }
    unset($user['password']);
    api_send(['status' => 'success', 'token' => $token, 'user' => $user]);
}

if ($action === 'logout') {
    $token = api_get_bearer_token();
    if (!$token) {
        api_send(['status' => 'success']);
    }
    $stmt = $conn->prepare('DELETE FROM user_tokens WHERE token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    api_send(['status' => 'success']);
}

$user = api_require_user($conn);
api_send(['status' => 'success', 'user' => $user]);

