<?php
define('APP_NAME', 'Personal Key Wallet');
define('APP_VERSION', '1.0.0');
define('BASE_PATH', __DIR__ . '/..');
define('DATA_PATH', BASE_PATH . '/data');
define('DB_PATH', DATA_PATH . '/wallet.db');
define('SESSION_LIFETIME', 3600 * 8); // 8 hours

// Ensure data directory exists
if (!is_dir(DATA_PATH)) {
    mkdir(DATA_PATH, 0700, true);
}
