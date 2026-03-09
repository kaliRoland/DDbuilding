<?php
// restore_hero_slides.php
// Small utility to restore the `hero_slides` table from a SQL dump in the backups/ folder.
// Usage (browser): admin/restore_hero_slides.php?file=backup_2025-12-29_14-30-39_database.sql&table=hero_slides

require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');

$file = $_GET['file'] ?? 'backup_2025-12-29_14-30-39_database.sql';
$file = basename($file); // sanitize
$table = $_GET['table'] ?? 'hero_slides';

$backups_dir = __DIR__ . '/../backups/';
$path = $backups_dir . $file;

$out = ['ok' => false, 'file' => $file, 'table' => $table, 'actions' => []];

if (!file_exists($path)) {
    $out['error'] = 'Backup file not found: ' . $file;
    echo json_encode($out, JSON_PRETTY_PRINT);
    exit;
}

$content = file_get_contents($path);
if ($content === false) {
    $out['error'] = 'Failed to read backup file.';
    echo json_encode($out, JSON_PRETTY_PRINT);
    exit;
}

// Prepare regex-safe table name
$table_esc = preg_quote($table, '/');

// Find DROP statement (optional)
$statements = [];
if (preg_match('/DROP TABLE IF EXISTS `'. $table_esc .'`;/', $content, $m)) {
    $statements[] = $m[0];
}

// Find CREATE TABLE block
if (preg_match('/CREATE TABLE `'. $table_esc .'`\s*\(.*?\);/si', $content, $m)) {
    $statements[] = $m[0];
} else {
    $out['warning_create'] = 'CREATE TABLE not found for ' . $table;
}

// Find all INSERT INTO statements for the table
if (preg_match_all('/INSERT INTO `'. $table_esc .'` .*?;/si', $content, $inserts)) {
    foreach ($inserts[0] as $ins) $statements[] = $ins;
} else {
    $out['warning_insert'] = 'No INSERT statements found for ' . $table;
}

if (empty($statements)) {
    $out['error'] = 'No SQL statements found to restore ' . $table;
    echo json_encode($out, JSON_PRETTY_PRINT);
    exit;
}

// Execute statements one by one
foreach ($statements as $idx => $sql) {
    // Clean up mysql-specific comments that can break ->query
    $clean = preg_replace('/\/\*![0-9]+ .*?\*\//s', '', $sql);
    $res = $conn->query($clean);
    if ($res === true) {
        $out['actions'][] = ['idx' => $idx, 'status' => 'ok', 'statement_preview' => substr(trim($sql), 0, 200)];
    } else {
        $out['actions'][] = ['idx' => $idx, 'status' => 'error', 'error' => $conn->error, 'statement_preview' => substr(trim($sql), 0, 200)];
        // Stop on first error
        break;
    }
}

// If last action was ok, consider success
$last = end($out['actions']);
if ($last && isset($last['status']) && $last['status'] === 'ok') {
    $out['ok'] = true;
}

echo json_encode($out, JSON_PRETTY_PRINT);

// Close connection if present
if (isset($conn) && $conn instanceof mysqli) $conn->close();

?>


