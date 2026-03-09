<?php
// Centralized session bootstrap for admin area.
// Use a dedicated admin cookie to avoid collisions with frontend PHPSESSID cookies.

$isHttps =
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ||
    ((int)($_SERVER['SERVER_PORT'] ?? 0) === 443) ||
    (strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')) === 'https');

// Keep cookie scoped to the admin path (e.g. /admin or /dd4/admin).
$scriptDir = rtrim(str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/admin'))), '/');
$cookiePath = ($scriptDir === '' || $scriptDir === '.') ? '/admin' : $scriptDir;

session_name('DDADMINSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path' => $cookiePath,
    'secure' => $isHttps,
    'httponly' => true,
    'samesite' => 'Lax',
]);

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
