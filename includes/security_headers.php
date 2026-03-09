<?php
// Security Headers for Admin Panel
header("X-Frame-Options: DENY"); // Prevent clickjacking
header("X-Content-Type-Options: nosniff"); // Prevent MIME type sniffing
header("X-XSS-Protection: 1; mode=block"); // Enable XSS filtering
header("Referrer-Policy: strict-origin-when-cross-origin"); // Control referrer information

// Content Security Policy for admin panel
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com https://www.googletagmanager.com; style-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com; img-src 'self' data: https:; font-src 'self' https://fonts.gstatic.com; connect-src 'self' https://www.googletagmanager.com https://www.google-analytics.com; frame-src 'self' https://www.googletagmanager.com; frame-ancestors 'none';");

// Prevent caching of sensitive pages
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");
?>
