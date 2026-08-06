<?php

function loadEnvFile(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        $parts = explode('=', $line, 2);
        if (count($parts) !== 2) {
            continue;
        }

        $key = trim($parts[0]);
        $value = trim($parts[1]);

        if ($key === '' || getenv($key) !== false) {
            continue;
        }

        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"'))
            || (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        putenv($key . '=' . $value);
        $_ENV[$key] = $value;
        $_SERVER[$key] = $value;
    }
}

function preflightChecks(): array
{
    $errors = [];

    if (!extension_loaded('pdo_sqlite')) {
        $errors[] = 'Missing required PHP extension: pdo_sqlite.';
    }
    if (!extension_loaded('openssl')) {
        $errors[] = 'Missing required PHP extension: openssl.';
    }

    if (!is_dir(DATA_PATH)) {
        $errors[] = 'Data directory not found: ' . DATA_PATH;
    } elseif (!is_writable(DATA_PATH)) {
        $errors[] = 'Data directory is not writable: ' . DATA_PATH;
    }

    return $errors;
}

function failPreflight(array $errors): never
{
    http_response_code(500);
    header('Content-Type: text/plain; charset=UTF-8');
    echo "Application setup error:\n\n";
    foreach ($errors as $error) {
        echo '- ' . $error . "\n";
    }
    exit;
}

define('APP_NAME', 'Personal Key Wallet');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', __DIR__ . '/..');
define('DATA_PATH', BASE_PATH . '/data');
define('DB_PATH', DATA_PATH . '/wallet.db');
define('SESSION_LIFETIME', 3600 * 8); // 8 hours

loadEnvFile(BASE_PATH . '/.env');

// Ensure data directory exists
if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0700, true);
}

$preflightErrors = preflightChecks();
if (!empty($preflightErrors)) {
    failPreflight($preflightErrors);
}
