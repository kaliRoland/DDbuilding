<?php
// Minimal .env loader (no external dependencies)
// Loads KEY=VALUE pairs from project root .env into $_ENV and getenv()

$envPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
if (!file_exists($envPath)) {
    return;
}

$lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
if ($lines === false) {
    return;
}

foreach ($lines as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#')) {
        continue;
    }
    $eqPos = strpos($line, '=');
    if ($eqPos === false) {
        continue;
    }
    $key = trim(substr($line, 0, $eqPos));
    $value = trim(substr($line, $eqPos + 1));

    // Strip surrounding quotes
    if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
        (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
        $value = substr($value, 1, -1);
    }

    if ($key !== '' && getenv($key) === false) {
        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
    }
}
