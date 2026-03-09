<?php
// cPanel debug helper — safe to remove after use
// Place this file in the same folder you uploaded your site (e.g. public_html or public_html/DD)

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>cPanel Debug Helper</h2>";

echo "<h3>PHP Info</h3>";
// Show PHP info (use phpinfo() only if available)
echo "<h3>PHP Info</h3>";
if (function_exists('phpinfo')) {
    ob_start();
    phpinfo();
    $phpinfo = ob_get_clean();
    // Only show PHP page title to keep output smaller
    if (preg_match('/<title>(.*?)<\/title>/i', $phpinfo, $m)) {
        echo "<p><strong>Page title:</strong> " . htmlspecialchars($m[1]) . "</p>";
    }
} else {
    echo "<p style='color:orange'><strong>phpinfo()</strong> is disabled on this server. Showing basic PHP info instead.</p>";
}

echo "<p><strong>PHP SAPI:</strong> <code>" . php_sapi_name() . "</code></p>";
echo "<p><strong>PHP Version:</strong> <code>" . PHP_VERSION . "</code></p>";

$disabled = ini_get('disable_functions');
if ($disabled) {
    echo "<p><strong>Disabled functions:</strong> <code>" . htmlspecialchars($disabled) . "</code></p>";
}

// Show current directory and list files
echo "<h3>Files In This Directory (" . __DIR__ . ")</h3>";
$files = scandir(__DIR__);
echo '<pre>' . htmlspecialchars(implode("\n", $files)) . '</pre>';

// Check index files existence
$indexCandidates = ['index.php', 'index.html', 'index.htm', 'default.php'];
$found = [];
foreach ($indexCandidates as $c) {
    if (file_exists(__DIR__ . '/' . $c)) $found[] = $c;
}
if ($found) {
    echo "<p><strong>Index files found:</strong> " . implode(', ', $found) . "</p>";
} else {
    echo "<p style='color:orange'><strong>No index.php/index.html found in this directory.</strong></p>";
}

// Try to include config/database.php (if present) and test DB connection
echo "<h3>Database Check</h3>";
$dbFile = __DIR__ . '/config/database.php';
if (!file_exists($dbFile)) {
    echo "<p style='color:red'>config/database.php not found at: " . htmlspecialchars($dbFile) . "</p>";
} else {
    echo "<p>Found config/database.php — attempting to require and connect.</p>";
    // require in isolated scope to avoid side-effects
    try {
        require $dbFile;
        if (isset($conn) && $conn instanceof mysqli) {
            if ($conn->connect_error) {
                echo "<p style='color:red'>DB connect error: " . htmlspecialchars($conn->connect_error) . "</p>";
            } else {
                echo "<p style='color:green'>DB connection OK. Database: " . htmlspecialchars($conn->host_info ?? '') . "</p>";
                // Count products table if exists
                $res = @$conn->query("SELECT COUNT(*) as c FROM products");
                if ($res) {
                    $r = $res->fetch_assoc();
                    echo "<p>Products table row count: <strong>" . intval($r['c']) . "</strong></p>";
                } else {
                    echo "<p style='color:orange'>Could not query products table: " . htmlspecialchars($conn->error) . "</p>";
                }
            }
        } else {
            echo "<p style='color:red'>No \$conn mysqli variable found after including config/database.php.</p>";
        }
    } catch (Throwable $e) {
        echo "<pre style='color:red'>Error requiring config/database.php:\n" . htmlspecialchars($e->getMessage()) . "</pre>";
    }
}

// Check .htaccess
echo "<h3>.htaccess</h3>";
$ht = __DIR__ . '/.htaccess';
if (file_exists($ht)) {
    echo '<pre>' . htmlspecialchars(file_get_contents($ht)) . '</pre>';
} else {
    echo "<p>No .htaccess file in this directory.</p>";
}

// File permissions quick check
echo "<h3>Permissions</h3>";
foreach ($files as $f) {
    if ($f === '.' || $f === '..') continue;
    $perm = substr(sprintf('%o', fileperms(__DIR__ . '/' . $f)), -4);
    echo htmlspecialchars($f) . ' - ' . $perm . '<br>';
}

echo "<hr><p>Remove this file after debugging. Share any errors you find and I will help fix them.</p>";

?>