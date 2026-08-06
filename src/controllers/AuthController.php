<?php
class AuthController
{
    public static function showLogin(): void
    {
        Auth::requireGuest();
        view('auth/login');
    }

    public static function handleLogin(): void
    {
        Auth::requireGuest();
        Auth::verifyCsrf();

        $email    = sanitize(input('email', ''));
        $password = input('password', '');

        $loginKey = Auth::throttleKey('login', $email !== '' ? $email : 'unknown');
        if (Auth::tooManyAttempts($loginKey, 8, 300)) {
            flash('error', 'Too many login attempts. Please wait 5 minutes and try again.');
            redirect('/login');
        }

        if (!$email || !$password) {
            flash('error', 'Email and password are required.');
            redirect('/login');
        }

        $user = Database::fetch('SELECT * FROM users WHERE email = ?', [$email]);

        if (!$user || !Auth::verifyPassword($password, $user['password_hash'])) {
            Auth::recordAttempt($loginKey);
            flash('error', 'Invalid email or password.');
            redirect('/login');
        }

        Auth::clearAttempts($loginKey);

        Auth::login($user);

        // Check if TOTP is set up
        if ($user['totp_verified']) {
            redirect('/verify-2fa');
        } else {
            redirect('/setup-2fa');
        }
    }

    public static function showSignup(): void
    {
        Auth::requireGuest();
        view('auth/signup');
    }

    public static function handleSignup(): void
    {
        Auth::requireGuest();
        Auth::verifyCsrf();

        $name     = sanitize(input('name', ''));
        $email    = sanitize(input('email', ''));
        $password = input('password', '');
        $confirm  = input('password_confirm', '');

        $_SESSION['_old'] = compact('name', 'email');

        if (!$name || !$email || !$password) {
            flash('error', 'All fields are required.');
            redirect('/signup');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Invalid email address.');
            redirect('/signup');
        }

        if (strlen($password) < 8) {
            flash('error', 'Password must be at least 8 characters.');
            redirect('/signup');
        }

        if ($password !== $confirm) {
            flash('error', 'Passwords do not match.');
            redirect('/signup');
        }

        $exists = Database::fetch('SELECT id FROM users WHERE email = ?', [$email]);
        if ($exists) {
            flash('error', 'An account with this email already exists.');
            redirect('/signup');
        }

        $hash   = Auth::hashPassword($password);
        $secret = Totp::generateSecret();

        Database::execute(
            'INSERT INTO users (name, email, password_hash, totp_secret) VALUES (?, ?, ?, ?)',
            [$name, $email, $hash, $secret]
        );

        $user = Database::fetch('SELECT * FROM users WHERE email = ?', [$email]);
        Auth::login($user);

        unset($_SESSION['_old']);
        redirect('/setup-2fa');
    }

    public static function showSetup2fa(): void
    {
        if (!isset($_SESSION['user_id'])) redirect('/login');

        $user   = Database::fetch('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
        $secret = $user['totp_secret'];
        $qrUri  = Totp::getQrUri($secret, $user['email']);

        view('auth/setup-2fa', compact('secret', 'qrUri', 'user'));
    }

    public static function handleSetup2fa(): void
    {
        if (!isset($_SESSION['user_id'])) redirect('/login');
        Auth::verifyCsrf();

        $code = sanitize(input('code', ''));
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
        $twoFaKey = Auth::throttleKey('setup-2fa', (string) ($_SESSION['user_id'] ?? 'unknown'));

        if (Auth::tooManyAttempts($twoFaKey, 10, 300)) {
            flash('error', 'Too many verification attempts. Please wait 5 minutes.');
            redirect('/setup-2fa');
        }

        if (!Totp::verify($user['totp_secret'], $code)) {
            Auth::recordAttempt($twoFaKey);
            flash('error', 'Invalid code. Please try again.');
            redirect('/setup-2fa');
        }

        Auth::clearAttempts($twoFaKey);

        Database::execute(
            "UPDATE users SET totp_verified = 1, updated_at = datetime('now') WHERE id = ?",
            [$user['id']]
        );

        Auth::completeTwoFactor();
        redirect('/dashboard');
    }

    public static function showVerify2fa(): void
    {
        if (!isset($_SESSION['user_id'])) redirect('/login');
        if (Auth::check()) redirect('/dashboard');
        view('auth/verify-2fa');
    }

    public static function handleVerify2fa(): void
    {
        if (!isset($_SESSION['user_id'])) redirect('/login');
        Auth::verifyCsrf();

        $code = sanitize(input('code', ''));
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$_SESSION['user_id']]);
        $twoFaKey = Auth::throttleKey('verify-2fa', (string) ($_SESSION['user_id'] ?? 'unknown'));

        if (Auth::tooManyAttempts($twoFaKey, 10, 300)) {
            flash('error', 'Too many verification attempts. Please wait 5 minutes.');
            redirect('/verify-2fa');
        }

        if (!$user || !Totp::verify($user['totp_secret'], $code)) {
            Auth::recordAttempt($twoFaKey);
            flash('error', 'Invalid or expired code. Please try again.');
            redirect('/verify-2fa');
        }

        Auth::clearAttempts($twoFaKey);

        Auth::completeTwoFactor();
        redirect('/dashboard');
    }

    public static function logout(): void
    {
        Auth::verifyCsrf();
        Auth::logout();
        redirect('/login');
    }

    public static function logoutAll(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();

        Auth::revokeAllSessions(userId());
        Auth::logout();
        redirect('/login');
    }
}
