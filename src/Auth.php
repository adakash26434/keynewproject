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

        self::validateAndTouchLoginSession();
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

        self::registerLoginSession((int) ($_SESSION['user_id'] ?? 0));
    }

    public static function logout(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $sessionId = (int) ($_SESSION['login_session_id'] ?? 0);
        $sessionToken = (string) ($_SESSION['login_session_token'] ?? '');

        if ($userId > 0 && $sessionId > 0) {
            Database::execute(
                'DELETE FROM login_sessions WHERE user_id = ? AND id = ?',
                [$userId, $sessionId]
            );
        } elseif ($userId > 0 && $sessionToken !== '') {
            Database::execute(
                'DELETE FROM login_sessions WHERE user_id = ? AND session_token = ?',
                [$userId, $sessionToken]
            );
        }

        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function loginSessions(int $userId, int $limit = 8): array
    {
        return Database::fetchAll(
            'SELECT id, session_token, user_agent, ip_address, created_at, last_active
             FROM login_sessions
             WHERE user_id = ?
             ORDER BY last_active DESC
             LIMIT ' . (int) max(1, min($limit, 20)),
            [$userId]
        );
    }

    public static function currentLoginSessionId(): int
    {
        return (int) ($_SESSION['login_session_id'] ?? 0);
    }

    public static function revokeSessionById(int $userId, int $sessionId): bool
    {
        if ($userId <= 0 || $sessionId <= 0) {
            return false;
        }

        $currentSessionId = self::currentLoginSessionId();
        if ($currentSessionId > 0 && $currentSessionId === $sessionId) {
            return false;
        }

        return Database::execute(
            'DELETE FROM login_sessions WHERE user_id = ? AND id = ?',
            [$userId, $sessionId]
        ) > 0;
    }

    public static function revokeAllSessions(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        Database::execute('DELETE FROM login_sessions WHERE user_id = ?', [$userId]);
    }

    private static function registerLoginSession(int $userId): void
    {
        if ($userId <= 0) {
            return;
        }

        $token = bin2hex(random_bytes(24));
        $_SESSION['login_session_token'] = $token;

        Database::execute(
            'INSERT INTO login_sessions (user_id, session_token, user_agent, ip_address, last_active)
             VALUES (?, ?, ?, ?, datetime(\'now\'))',
            [
                $userId,
                $token,
                substr((string) ($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'), 0, 255),
                substr(self::clientIp(), 0, 100),
            ]
        );

        $_SESSION['login_session_id'] = (int) Database::lastInsertId();
    }

    private static function validateAndTouchLoginSession(): void
    {
        $userId = (int) ($_SESSION['user_id'] ?? 0);
        $sessionId = (int) ($_SESSION['login_session_id'] ?? 0);
        $token  = (string) ($_SESSION['login_session_token'] ?? '');

        if ($userId <= 0 || $token === '') {
            self::logout();
            redirect('/login');
        }

        if ($sessionId > 0) {
            $row = Database::fetch(
                'SELECT id FROM login_sessions WHERE user_id = ? AND id = ? AND session_token = ? LIMIT 1',
                [$userId, $sessionId, $token]
            );
        } else {
            $row = Database::fetch(
                'SELECT id FROM login_sessions WHERE user_id = ? AND session_token = ? LIMIT 1',
                [$userId, $token]
            );
            if ($row) {
                $_SESSION['login_session_id'] = (int) $row['id'];
                $sessionId = (int) $row['id'];
            }
        }

        if (!$row) {
            self::logout();
            redirect('/login');
        }

        if ($sessionId > 0) {
            Database::execute(
                'UPDATE login_sessions SET last_active = datetime(\'now\') WHERE user_id = ? AND id = ?',
                [$userId, $sessionId]
            );
        } else {
            Database::execute(
                'UPDATE login_sessions SET last_active = datetime(\'now\') WHERE user_id = ? AND session_token = ?',
                [$userId, $token]
            );
        }
    }

    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    public static function isPasswordReused(int $userId, string $newPassword, int $historyLimit = 3): bool
    {
        if ($userId <= 0 || $newPassword === '') {
            return false;
        }

        $current = Database::fetch('SELECT password_hash FROM users WHERE id = ?', [$userId]);
        if ($current && self::verifyPassword($newPassword, (string) $current['password_hash'])) {
            return true;
        }

        $limit = (int) max(1, min($historyLimit, 10));
        $rows = Database::fetchAll(
            'SELECT password_hash FROM password_history WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT ' . $limit,
            [$userId]
        );

        foreach ($rows as $row) {
            if (self::verifyPassword($newPassword, (string) $row['password_hash'])) {
                return true;
            }
        }

        return false;
    }

    public static function recordPasswordHistory(int $userId, string $passwordHash, int $keep = 5): void
    {
        if ($userId <= 0 || $passwordHash === '') {
            return;
        }

        Database::execute(
            'INSERT INTO password_history (user_id, password_hash) VALUES (?, ?)',
            [$userId, $passwordHash]
        );

        $keepCount = (int) max(3, min($keep, 20));
        Database::execute(
            'DELETE FROM password_history
             WHERE user_id = ? AND id NOT IN (
                SELECT id FROM password_history
                WHERE user_id = ?
                ORDER BY created_at DESC, id DESC
                LIMIT ' . $keepCount . '
             )',
            [$userId, $userId]
        );
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
