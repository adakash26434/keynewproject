<?php
class ProfileController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid = userId();
        $user = Database::fetch('SELECT * FROM users WHERE id = ?', [$uid]);
        $sessions = Auth::loginSessions($uid, 10);
        $currentSessionId = Auth::currentLoginSessionId();
        $pageTitle = 'Profile';
        view('pages/profile', compact('user', 'sessions', 'currentSessionId', 'pageTitle'));
    }

    public static function update(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();

        $name     = sanitize(input('name', ''));
        $bio      = sanitize(input('bio', ''));
        $phone    = sanitize(input('phone', ''));
        $location = sanitize(input('location', ''));

        if (!$name) {
            flash('error', 'Name is required.');
            redirect('/profile');
        }

        Database::execute(
            "UPDATE users SET name=?, bio=?, phone=?, location=?, updated_at=datetime('now') WHERE id=?",
            [$name, $bio, $phone, $location, $uid]
        );

        // Update session name
        $_SESSION['user_name'] = $name;

        flash('success', 'Profile updated successfully.');
        redirect('/profile');
    }

    public static function changePassword(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();

        $current = input('current_password', '');
        $new     = input('new_password', '');
        $confirm = input('confirm_password', '');

        $user = Database::fetch('SELECT password_hash FROM users WHERE id = ?', [$uid]);

        if (!Auth::verifyPassword($current, $user['password_hash'])) {
            flash('error', 'Current password is incorrect.');
            redirect('/profile');
        }

        if (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.');
            redirect('/profile');
        }

        if ($new !== $confirm) {
            flash('error', 'New passwords do not match.');
            redirect('/profile');
        }

        Database::execute(
            "UPDATE users SET password_hash=?, updated_at=datetime('now') WHERE id=?",
            [Auth::hashPassword($new), $uid]
        );

        flash('success', 'Password changed successfully.');
        redirect('/profile');
    }

    public static function revokeSession(array $params): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();

        $uid = userId();
        $sessionId = (int) ($params['id'] ?? 0);

        if ($sessionId <= 0) {
            flash('error', 'Invalid session selected.');
            redirect('/profile');
        }

        if ($sessionId === Auth::currentLoginSessionId()) {
            flash('error', 'Current session cannot be revoked from this action.');
            redirect('/profile');
        }

        if (Auth::revokeSessionById($uid, $sessionId)) {
            flash('success', 'Selected session has been signed out.');
        } else {
            flash('error', 'Session not found or already revoked.');
        }

        redirect('/profile');
    }
}
