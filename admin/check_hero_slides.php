<?php
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain');
$res = $conn->query("SHOW TABLES LIKE 'hero_slides'");
if ($res && $res->num_rows) {
    echo "hero_slides exists\n";
} else {
    echo "hero_slides missing\n";
}
if (isset($conn) && $conn instanceof mysqli) $conn->close();

?>


