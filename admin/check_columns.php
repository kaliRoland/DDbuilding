<?php
require_once __DIR__ . '/../config/database.php';
$res = $conn->query("SHOW COLUMNS FROM products");
if (!$res) { echo "Query failed: " . $conn->error . "\n"; exit; }
while ($row = $res->fetch_assoc()) {
    echo $row['Field'] . "\n";
}
?>

