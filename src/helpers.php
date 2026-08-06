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

function daysSince(?string $date): ?int
{
    if (!$date) return null;
    try {
        $diff = (new DateTime('today'))->diff(new DateTime($date));
        return $diff->invert ? $diff->days : -$diff->days;
    } catch (Exception) {
        return null;
    }
}

function isCommonPassword(string $password): bool
{
    $p = strtolower(trim($password));
    if ($p === '') return false;

    static $common = [
        '123456', '12345678', '123456789', 'password', 'password123',
        'qwerty', 'qwerty123', 'abc123', '111111', '123123',
        '000000', 'admin', 'welcome', 'iloveyou', 'letmein',
        '1234', '12345', 'qwertyuiop', 'pass@123', 'test123',
        'khalti123', 'esewa123',
    ];

    if (in_array($p, $common, true)) {
        return true;
    }

    // Basic sequence/repetition patterns often used in breached passwords.
    if (preg_match('/^(.)\1{5,}$/', $p)) {
        return true;
    }
    if (preg_match('/^(1234|12345|123456|1234567|12345678|1234567890)$/', $p)) {
        return true;
    }

    return false;
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

function cacheDirPath(): string
{
    $dir = DATA_PATH . '/cache';
    if (!is_dir($dir)) {
        mkdir($dir, 0700, true);
    }
    return $dir;
}

function cacheFilePath(string $key): string
{
    return cacheDirPath() . '/' . hash('sha256', $key) . '.cache';
}

function cacheGet(string $key, int $ttlSeconds): mixed
{
    $file = cacheFilePath($key);
    if (!is_file($file)) {
        return null;
    }

    $fp = fopen($file, 'rb');
    if ($fp === false) {
        return null;
    }

    try {
        if (!flock($fp, LOCK_SH)) {
            return null;
        }

        $raw = stream_get_contents($fp);
        if ($raw === false || $raw === '') {
            return null;
        }

        $payload = json_decode($raw, true);
        if (!is_array($payload) || !isset($payload['created_at'])) {
            return null;
        }

        $createdAt = (int) $payload['created_at'];
        if ($createdAt <= 0 || (time() - $createdAt) > $ttlSeconds) {
            return null;
        }

        return $payload['value'] ?? null;
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function cachePut(string $key, mixed $value): void
{
    $file = cacheFilePath($key);
    $payload = json_encode([
        'created_at' => time(),
        'value' => $value,
    ]);

    if ($payload === false) {
        return;
    }

    $fp = fopen($file, 'cb');
    if ($fp === false) {
        return;
    }

    try {
        if (!flock($fp, LOCK_EX)) {
            return;
        }

        ftruncate($fp, 0);
        rewind($fp);
        fwrite($fp, $payload);
        fflush($fp);
    } finally {
        flock($fp, LOCK_UN);
        fclose($fp);
    }
}

function cacheRemember(string $key, int $ttlSeconds, callable $resolver): mixed
{
    $cached = cacheGet($key, $ttlSeconds);
    if ($cached !== null) {
        return $cached;
    }

    $value = $resolver();
    cachePut($key, $value);
    return $value;
}

function breachCheckEnabled(): bool
{
    $raw = strtolower(trim((string) getenv('BREACH_CHECK_ENABLED')));
    return in_array($raw, ['1', 'true', 'yes', 'on'], true);
}

function pwnedPasswordCount(string $password): ?int
{
    if ($password === '') {
        return 0;
    }

    if (!breachCheckEnabled()) {
        return null;
    }

    $sha1 = strtoupper(sha1($password));
    $prefix = substr($sha1, 0, 5);
    $suffix = substr($sha1, 5);
    $cacheKey = 'hibp:' . $sha1;

    return cacheRemember($cacheKey, 86400, function () use ($prefix, $suffix): ?int {
        $url = 'https://api.pwnedpasswords.com/range/' . $prefix;
        $ctx = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 4,
                'ignore_errors' => true,
                'header' => "User-Agent: AakashKeyVault/1.0\r\nAdd-Padding: true\r\n",
            ],
        ]);

        $resp = @file_get_contents($url, false, $ctx);
        if ($resp === false || trim($resp) === '') {
            return null;
        }

        $lines = preg_split('/\r\n|\r|\n/', trim($resp));
        if (!is_array($lines)) {
            return null;
        }

        foreach ($lines as $line) {
            $parts = explode(':', trim($line), 2);
            if (count($parts) !== 2) {
                continue;
            }

            if (strtoupper($parts[0]) === $suffix) {
                return (int) trim($parts[1]);
            }
        }

        return 0;
    });
}

function alertWebhookUrl(): string
{
    return trim((string) getenv('ALERT_WEBHOOK_URL'));
}

function alertWebhookMode(): string
{
    $mode = strtolower(trim((string) getenv('ALERT_WEBHOOK_MODE')));
    if ($mode === '') {
        return 'generic';
    }
    return $mode;
}

function sendAlertWebhook(string $title, string $message, array $payload = []): bool
{
    $url = alertWebhookUrl();
    if ($url === '' || !preg_match('#^https?://#i', $url)) {
        return false;
    }

    $mode = alertWebhookMode();
    $bodyData = [
        'app' => APP_NAME,
        'title' => $title,
        'message' => $message,
        'payload' => $payload,
        'sent_at' => gmdate('c'),
    ];

    if ($mode === 'telegram') {
        $bodyData = [
            'text' => "*" . APP_NAME . "*\n" . $title . "\n\n" . $message,
            'parse_mode' => 'Markdown',
        ];
    }

    $body = json_encode($bodyData, JSON_UNESCAPED_SLASHES);

    if ($body === false) {
        return false;
    }

    $ctx = stream_context_create([
        'http' => [
            'method' => 'POST',
            'timeout' => 3,
            'ignore_errors' => true,
            'header' => "Content-Type: application/json\r\n",
            'content' => $body,
        ],
    ]);

    $res = @file_get_contents($url, false, $ctx);
    return $res !== false;
}

function enqueueAlertWebhook(string $title, string $message, array $payload = []): void
{
    $queue = cacheGet('alert:webhook:queue', 365 * 24 * 3600);
    if (!is_array($queue)) {
        $queue = [];
    }

    $queue[] = [
        'title' => $title,
        'message' => $message,
        'payload' => $payload,
        'queued_at' => gmdate('c'),
    ];

    // Bound queue to avoid unbounded growth.
    if (count($queue) > 30) {
        $queue = array_slice($queue, -30);
    }

    cachePut('alert:webhook:queue', $queue);
}

function flushQueuedAlertWebhooks(int $maxToSend = 5): void
{
    $queue = cacheGet('alert:webhook:queue', 365 * 24 * 3600);
    if (!is_array($queue) || empty($queue)) {
        return;
    }

    $remaining = [];
    $sentCount = 0;

    foreach ($queue as $item) {
        if (!is_array($item)) {
            continue;
        }

        if ($sentCount >= $maxToSend) {
            $remaining[] = $item;
            continue;
        }

        $ok = sendAlertWebhook(
            (string) ($item['title'] ?? 'Alert'),
            (string) ($item['message'] ?? ''),
            is_array($item['payload'] ?? null) ? $item['payload'] : []
        );

        if ($ok) {
            $sentCount++;
            continue;
        }

        $remaining[] = $item;
    }

    if (empty($remaining)) {
        cachePut('alert:webhook:queue', []);
    } else {
        cachePut('alert:webhook:queue', $remaining);
    }
}

function sendDailySmartAlertDigest(int $userId, array $alerts): void
{
    if ($userId <= 0 || empty($alerts)) {
        return;
    }

    $dayKey = gmdate('Y-m-d');
    $cacheKey = 'alert-digest:' . $userId . ':' . $dayKey;
    if (cacheGet($cacheKey, 86400) !== null) {
        return;
    }

    $top = array_slice($alerts, 0, 3);
    $lines = [];
    foreach ($top as $a) {
        if (!is_array($a)) continue;
        $lines[] = '- ' . ((string) ($a['title'] ?? 'Alert')) . ': ' . ((string) ($a['message'] ?? ''));
    }

    if (empty($lines)) {
        return;
    }

    flushQueuedAlertWebhooks();

    $sent = sendAlertWebhook(
        'Daily Smart Alerts',
        implode("\n", $lines),
        ['user_id' => $userId, 'count' => count($alerts)]
    );

    if ($sent) {
        cachePut($cacheKey, ['sent' => true]);
    } else {
        enqueueAlertWebhook(
            'Daily Smart Alerts',
            implode("\n", $lines),
            ['user_id' => $userId, 'count' => count($alerts)]
        );
    }
}

function siteSettingsDefaults(): array
{
    return [
        'site_name' => APP_NAME,
        'site_tagline' => 'Your secure digital vault',
        'logo_url' => '',
        'support_email' => '',
        'allow_signup' => '1',
        'allow_share' => '1',
        'maintenance_notice' => '',
    ];
}

function siteSettings(bool $fresh = false): array
{
    $cacheKey = 'site:settings:v1';
    if (!$fresh) {
        $cached = cacheGet($cacheKey, 120);
        if (is_array($cached) && !empty($cached)) {
            return $cached;
        }
    }

    $defaults = siteSettingsDefaults();
    $rows = Database::fetchAll('SELECT setting_key, setting_value FROM site_settings');
    foreach ($rows as $row) {
        $k = (string) ($row['setting_key'] ?? '');
        if ($k === '' || !array_key_exists($k, $defaults)) {
            continue;
        }
        $defaults[$k] = (string) ($row['setting_value'] ?? '');
    }

    cachePut($cacheKey, $defaults);
    return $defaults;
}

function siteSetting(string $key, ?string $default = null): string
{
    $settings = siteSettings();
    if (array_key_exists($key, $settings)) {
        return (string) $settings[$key];
    }
    if ($default !== null) {
        return $default;
    }
    $defs = siteSettingsDefaults();
    return (string) ($defs[$key] ?? '');
}

function setSiteSetting(string $key, string $value): bool
{
    $defaults = siteSettingsDefaults();
    if (!array_key_exists($key, $defaults)) {
        return false;
    }

    $existing = Database::fetch('SELECT setting_key FROM site_settings WHERE setting_key = ? LIMIT 1', [$key]);
    if ($existing) {
        Database::execute(
            'UPDATE site_settings SET setting_value = ?, updated_at = ' . Database::nowExpression() . ' WHERE setting_key = ?',
            [$value, $key]
        );
    } else {
        Database::execute(
            'INSERT INTO site_settings (setting_key, setting_value) VALUES (?, ?)',
            [$key, $value]
        );
    }

    cachePut('site:settings:v1', []);
    return true;
}

function isSignupAllowed(): bool
{
    return siteSetting('allow_signup', '1') !== '0';
}

function isShareEnabled(): bool
{
    return siteSetting('allow_share', '1') !== '0';
}
