<?php

require_once __DIR__ . '/includes/session.php';

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security_headers.php';

$error = '';

// Brute-force protection functions
function get_client_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

function is_ip_blocked($conn, $ip) {
    // Check if IP has more than 5 failed attempts in the last 15 minutes
    $stmt = $conn->prepare("SELECT COUNT(*) as attempts FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    if ($stmt === false) {
        @file_put_contents(__DIR__ . '/../cpanel_debug_errors.log', "[".date('Y-m-d H:i:s')."] DB PREPARE FAILED in is_ip_blocked: " . ($conn->error ?? 'unknown') . "\n", FILE_APPEND);
        return false; // fail open: don't block if query can't run
    }
    $stmt->bind_param("s", $ip);
    if (!$stmt->execute()) {
        @file_put_contents(__DIR__ . '/../cpanel_debug_errors.log', "[".date('Y-m-d H:i:s')."] DB EXECUTE FAILED in is_ip_blocked: " . $stmt->error . "\n", FILE_APPEND);
        $stmt->close();
        return false;
    }
    $result = $stmt->get_result();
    $row = $result ? $result->fetch_assoc() : ['attempts' => 0];
    $stmt->close();

    return (!empty($row['attempts']) && $row['attempts'] >= 5);
}

function record_failed_attempt($conn, $ip) {
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address) VALUES (?)");
    if ($stmt === false) {
        @file_put_contents(__DIR__ . '/../cpanel_debug_errors.log', "[".date('Y-m-d H:i:s')."] DB PREPARE FAILED in record_failed_attempt: " . ($conn->error ?? 'unknown') . "\n", FILE_APPEND);
        return;
    }
    $stmt->bind_param("s", $ip);
    if (!$stmt->execute()) {
        @file_put_contents(__DIR__ . '/../cpanel_debug_errors.log', "[".date('Y-m-d H:i:s')."] DB EXECUTE FAILED in record_failed_attempt: " . $stmt->error . "\n", FILE_APPEND);
    }
    $stmt->close();

    // Clean up old attempts (older than 1 hour)
    $conn->query("DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
}

function clear_failed_attempts($conn, $ip) {
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    if ($stmt === false) {
        @file_put_contents(__DIR__ . '/../cpanel_debug_errors.log', "[".date('Y-m-d H:i:s')."] DB PREPARE FAILED in clear_failed_attempts: " . ($conn->error ?? 'unknown') . "\n", FILE_APPEND);
        return;
    }
    $stmt->bind_param("s", $ip);
    if (!$stmt->execute()) {
        @file_put_contents(__DIR__ . '/../cpanel_debug_errors.log', "[".date('Y-m-d H:i:s')."] DB EXECUTE FAILED in clear_failed_attempts: " . $stmt->error . "\n", FILE_APPEND);
    }
    $stmt->close();
}

// Check if IP is blocked before processing login
$client_ip = get_client_ip();
if (is_ip_blocked($conn, $client_ip)) {
    $error = "Too many failed login attempts. Please try again in 15 minutes.";
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT id, password, role FROM admins WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $admin = $result->fetch_assoc();
        if (password_verify($password, $admin['password'])) {
            // Regenerate session ID for security
            session_regenerate_id(true);
            $_SESSION['admin_id'] = $admin['id'];
            $_SESSION['admin_role'] = $admin['role'];
            // Clear failed attempts on successful login
            clear_failed_attempts($conn, $client_ip);
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
                // Regenerate session ID for security
                session_regenerate_id(true);
                $_SESSION['admin_id'] = $user['id'];
                $_SESSION['admin_role'] = $user['role'];
                // Clear failed attempts on successful login
                clear_failed_attempts($conn, $client_ip);
                log_activity($conn, $user['id'], 'user_login');
                header("Location: index.php");
                exit;
            }
        }
    }

    // Record failed attempt
    record_failed_attempt($conn, $client_ip);
    $error = "Invalid username or password";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/brand.css">
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-5NVJVRF7');</script>
    <!-- End Google Tag Manager -->
</head>
<body class="bg-slate-900 text-slate-100 flex items-center justify-center min-h-screen">
    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-5NVJVRF7"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
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
                <div class="relative">
                    <input type="password" id="password" name="password" required class="w-full bg-slate-700 text-white rounded px-3 py-2 pr-10 focus:outline-none focus:border-amber-500 border-2 border-transparent">
                    <button type="button" id="toggle-password" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-200">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="lucide lucide-eye">
                            <path d="M2.99902 3L20.999 21"/>
                            <path d="M9.60998 9.61C9.71537 9.49778 9.84087 9.40774 9.97998 9.345C10.1191 9.28226 10.2691 9.24833 10.4205 9.245C10.5719 9.24167 10.7227 9.26947 10.8641 9.32697C11.0055 9.38447 11.1347 9.47047 11.2441 9.58C11.3535 9.68953 11.4411 9.82081 11.5003 9.96417C11.5595 10.1075 11.5891 10.2602 11.5875 10.414C11.5859 10.5678 11.5531 10.7198 11.4911 10.862C11.4291 11.0042 11.3393 11.1334 11.226 11.242L9.60998 9.61Z"/>
                            <path d="M10.5 5.5C6.5 5.5 3.5 8.5 3.5 12C3.5 13.5 4 14.5 4.5 15"/>
                            <path d="M20.5 8.5C21 9.5 21.5 10.5 21.5 12C21.5 15.5 18.5 18.5 14.5 18.5C13 18.5 11.5 18 10.5 17"/>
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit" class="w-full bg-amber-500 hover:bg-amber-600 text-slate-900 font-bold py-3 rounded transition">
                Login
            </button>
        </form>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const togglePassword = document.getElementById('toggle-password');
            const passwordInput = document.getElementById('password');

            togglePassword.addEventListener('click', function() {
                const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);

                // Toggle eye icon
                const eyeIcon = togglePassword.querySelector('svg');
                if (type === 'password') {
                    eyeIcon.innerHTML = `
                        <path d="M2.99902 3L20.999 21"/>
                        <path d="M9.60998 9.61C9.71537 9.49778 9.84087 9.40774 9.97998 9.345C10.1191 9.28226 10.2691 9.24833 10.4205 9.245C10.5719 9.24167 10.7227 9.26947 10.8641 9.32697C11.0055 9.38447 11.1347 9.47047 11.2441 9.58C11.3535 9.68953 11.4411 9.82081 11.5003 9.96417C11.5595 10.1075 11.5891 10.2602 11.5875 10.414C11.5859 10.5678 11.5531 10.7198 11.4911 10.862C11.4291 11.0042 11.3393 11.1334 11.226 11.242L9.60998 9.61Z"/>
                        <path d="M10.5 5.5C6.5 5.5 3.5 8.5 3.5 12C3.5 13.5 4 14.5 4.5 15"/>
                        <path d="M20.5 8.5C21 9.5 21.5 10.5 21.5 12C21.5 15.5 18.5 18.5 14.5 18.5C13 18.5 11.5 18 10.5 17"/>
                    `;
                } else {
                    eyeIcon.innerHTML = `
                        <path d="M2 12s3-7 10-7 10 7 10 7-3 7-10 7-10-7-10-7Z"/>
                        <circle cx="12" cy="12" r="3"/>
                    `;
                }
            });
        });
    </script>
</body>
</html>


