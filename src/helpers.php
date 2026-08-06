<?php
function view(string $template, array $data = []): void
{
    extract($data, EXTR_SKIP);
    ob_start();
    $templateFile = BASE_PATH . '/src/views/' . $template . '.php';
    if (file_exists($templateFile)) {
        include $templateFile;
    }
    $pageContent = ob_get_clean();

    // Auth pages use their own full layout
    if (str_starts_with($template, 'auth/')) {
        echo $pageContent;
        return;
    }

    // Error pages
    if (str_starts_with($template, 'errors/')) {
        echo $pageContent;
        return;
    }

    include BASE_PATH . '/src/views/layout.php';
}

function redirect(string $path, int $code = 302): never
{
    http_response_code($code);
    header('Location: ' . $path);
    exit;
}

function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function old(string $key, mixed $default = ''): string
{
    return e($_SESSION['_old'][$key] ?? $default);
}

function flash(string $type, string $message): void
{
    $_SESSION['_flash'][$type] = $message;
}

function getFlash(string $type): ?string
{
    $msg = $_SESSION['_flash'][$type] ?? null;
    unset($_SESSION['_flash'][$type]);
    return $msg;
}

function csrf(): string
{
    $token = Auth::csrfToken();
    return '<input type="hidden" name="_csrf" value="' . e($token) . '">';
}

function json(mixed $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function abort(int $code = 404, string $message = 'Not Found'): never
{
    http_response_code($code);
    echo $message;
    exit;
}

function passwordStrength(string $password): array
{
    $score  = 0;
    $checks = [
        'length'    => strlen($password) >= 12,
        'uppercase' => (bool) preg_match('/[A-Z]/', $password),
        'lowercase' => (bool) preg_match('/[a-z]/', $password),
        'numbers'   => (bool) preg_match('/[0-9]/', $password),
        'symbols'   => (bool) preg_match('/[^a-zA-Z0-9]/', $password),
    ];

    foreach ($checks as $passed) {
        if ($passed) $score++;
    }

    $level = match (true) {
        $score <= 1 => 'weak',
        $score <= 2 => 'fair',
        $score <= 3 => 'good',
        $score == 4 => 'strong',
        default     => 'very-strong',
    };

    return ['score' => $score, 'level' => $level, 'checks' => $checks];
}

function detectCategory(string $title, string $url = ''): string
{
    $text = strtolower($title . ' ' . $url);

    $map = [
        'Digital Wallet' => ['esewa', 'khalti', 'ime pay', 'imepay', 'connectips', 'fonepay', 'prabhupay'],
        'Banking'        => ['nic asia', 'nabil', 'laxmi', 'global ime', 'sanima', 'himalayan', 'nepal bank', 'rastriya', 'sbi', 'everest bank', 'citizens bank', 'century bank'],
        'Telecom'        => ['ntc', 'ncell', 'nepal telecom', 'smartcell', 'vianet', 'worldlink', 'subisu'],
        'Government'     => ['nagarik', 'dofe', 'inland revenue', 'ird.gov', 'passport', 'driving', 'vehicle'],
        'Social'         => ['facebook', 'instagram', 'twitter', 'tiktok', 'linkedin', 'snapchat', 'youtube'],
        'Email'          => ['gmail', 'outlook', 'yahoo mail', 'protonmail', 'hotmail'],
        'Shopping'       => ['amazon', 'daraz', 'sasto deal', 'hamrobazaar', 'sastodeal', 'gyapu'],
        'Work'           => ['office', 'slack', 'jira', 'github', 'gitlab', 'bitbucket', 'figma'],
        'Entertainment'  => ['netflix', 'spotify', 'youtube', 'prime video', 'hotstar'],
    ];

    foreach ($map as $category => $keywords) {
        foreach ($keywords as $keyword) {
            if (str_contains($text, $keyword)) {
                return $category;
            }
        }
    }

    return 'Other';
}

function formatNPR(float $amount): string
{
    return 'Rs. ' . number_format($amount, 2);
}

function formatDate(?string $date): string
{
    if (!$date) return '—';
    try {
        return (new DateTime($date))->format('M d, Y');
    } catch (Exception) {
        return $date;
    }
}

function daysUntil(?string $date): ?int
{
    if (!$date) return null;
    try {
        $diff = (new DateTime($date))->diff(new DateTime('today'));
        return $diff->invert ? -$diff->days : $diff->days;
    } catch (Exception) {
        return null;
    }
}

function expiryStatus(?string $date): string
{
    $days = daysUntil($date);
    if ($days === null) return 'none';
    if ($days < 0) return 'expired';
    if ($days <= 30) return 'expiring-soon';
    return 'valid';
}

function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

function input(string $key, mixed $default = null): mixed
{
    return $_POST[$key] ?? $_GET[$key] ?? $default;
}

function sanitize(string $value): string
{
    return trim(strip_tags($value));
}

function userId(): int
{
    return (int) ($_SESSION['user_id'] ?? 0);
}
