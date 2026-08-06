<?php
/**
 * PHP Built-in Server Router
 * Serves static files directly, routes everything else through the app.
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Serve static assets directly from public/
if (preg_match('/\.(css|js|png|jpg|jpeg|ico|svg|webp|woff|woff2|ttf|eot|map)$/i', $uri)) {
    $file = __DIR__ . '/public' . $uri;
    if (file_exists($file)) {
        return false; // Let PHP built-in server serve the file
    }
}

require __DIR__ . '/src/bootstrap.php';
