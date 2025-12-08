<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_role'] = $admin['role'];
            log_activity($conn, $admin['id'], 'admin_login');
            header("Location: index.php");
            exit;
        }
    } else {
        // Check in the 'users' table
        $stmt = $conn->prepare("SELECT id, password, role FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_role'] = $user['role'];
                log_activity($conn, $user['id'], 'user_login');
                header("Location: index.php");
                exit;
            }
        }
    }
    $error = "An error occurred";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-900 text-slate-100 flex items-center justify-center min-h-screen">
    <div class="bg-slate-800 p-8 rounded-lg shadow-lg w-full max-w-sm">
        <h1 class="text-2xl font-bold text-center mb-6">Admin Login</h1>
        <?php if ($error): ?>
            <div class="bg-red-500 text-white p-3 rounded mb-4">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <form method="POST">
            <div class="mb-4">
                <label for="username" class="block text-slate-400 mb-2">Username</label>
                <input type="text" id="username" name="username" required class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
            </div>
            <div class="mb-6">
                <label for="password" class="block text-slate-400 mb-2">Password</label>
                <input type="password" id="password" name="password" required class="w-full bg-slate-700 text-white rounded px-3 py-2 focus:outline-none focus:border-amber-500 border-2 border-transparent">
            </div>
            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-3 rounded transition">
                Login
            </button>
        </form>
    </div>
</body>
</html>
