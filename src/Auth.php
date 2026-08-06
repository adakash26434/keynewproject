<?php
class Auth
{
    private static function clientIp(): string
    {
        $forwarded = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '';
        if ($forwarded !== '') {
            $parts = explode(',', $forwarded);
            $candidate = trim($parts[0]);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    public static function throttleKey(string $scope, string $identifier = ''): string
    {
        $id = $identifier !== '' ? strtolower(trim($identifier)) : 'anonymous';
        return $scope . '|' . $id . '|' . self::clientIp();
    }

    public static function tooManyAttempts(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $now = time();
        $attempts = $_SESSION['_rate_limits'][$key] ?? [];
        $attempts = array_values(array_filter($attempts, static fn (int $ts): bool => ($now - $ts) < $windowSeconds));
        $_SESSION['_rate_limits'][$key] = $attempts;

        return count($attempts) >= $maxAttempts;
    }

    public static function recordAttempt(string $key): void
    {
        if (!isset($_SESSION['_rate_limits'][$key]) || !is_array($_SESSION['_rate_limits'][$key])) {
            $_SESSION['_rate_limits'][$key] = [];
        }
        $_SESSION['_rate_limits'][$key][] = time();
    }

    public static function clearAttempts(string $key): void
    {
        unset($_SESSION['_rate_limits'][$key]);
    }

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
                || (($_SERVER['SERVER_PORT'] ?? '') === '443');

            ini_set('session.use_strict_mode', '1');
            session_set_cookie_params([
                'lifetime' => SESSION_LIFETIME,
                'path'     => '/',
                'secure'   => $isHttps,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function user(): ?array
    {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['totp_passed'])) {
            return null;
        }
        if (!$_SESSION['totp_passed']) {
            return null;
        }
        return [
            'id'    => $_SESSION['user_id'],
            'email' => $_SESSION['user_email'],
            'name'  => $_SESSION['user_name'],
        ];
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function requireAuth(): void
    {
        if (!self::check()) {
            redirect('/login');
        }
    }

    public static function requireGuest(): void
    {
        if (self::check()) {
            redirect('/dashboard');
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['totp_passed'] = false;
        $_SESSION['awaiting_totp_user_id'] = $user['id'];
    }

    public static function completeTwoFactor(): void
    {
        session_regenerate_id(true);
        $_SESSION['totp_passed'] = true;
        unset($_SESSION['awaiting_totp_user_id']);
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function csrfToken(): string
    {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            http_response_code(403);
            $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
            if (str_contains($accept, 'application/json')) {
                header('Content-Type: application/json');
                die(json_encode(['error' => 'Invalid CSRF token']));
            }

            die('Invalid CSRF token');
        }
    }
}
