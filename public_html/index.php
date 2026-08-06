<?php
declare(strict_types=1);

// Support both deployment styles:
// 1) Secure layout: project root contains public_html/, src/, data/
// 2) Flat layout: all project files uploaded directly into public_html/
$bootstrapCandidates = [
    __DIR__ . '/../src/bootstrap.php',
    __DIR__ . '/src/bootstrap.php',
];

foreach ($bootstrapCandidates as $bootstrapPath) {
    if (is_file($bootstrapPath)) {
        require $bootstrapPath;
        exit;
    }
}

http_response_code(500);
echo 'Bootstrap file not found. Check deployment structure.';
