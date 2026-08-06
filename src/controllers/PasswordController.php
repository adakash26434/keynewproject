<?php
class PasswordController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid    = userId();
        $search = sanitize(input('q', ''));
        $cat    = sanitize(input('category', ''));

        $sql    = 'SELECT id, title, username, url, category, strength, created_at FROM passwords WHERE user_id = ?';
        $params = [$uid];

        if ($cat) {
            $sql    .= ' AND category = ?';
            $params[] = $cat;
        }

        $sql .= ' ORDER BY title ASC';

        $passwords  = Database::fetchAll($sql, $params);
        foreach ($passwords as &$pw) {
            $pw['username'] = $pw['username'] ? (Crypto::decrypt($pw['username']) ?? '') : '';
        }
        unset($pw);

        if ($search !== '') {
            $needle = strtolower($search);
            $passwords = array_values(array_filter($passwords, function (array $pw) use ($needle): bool {
                $haystack = strtolower(
                    ($pw['title'] ?? '') . ' ' . ($pw['username'] ?? '') . ' ' . ($pw['url'] ?? '') . ' ' . ($pw['category'] ?? '')
                );
                return str_contains($haystack, $needle);
            }));
        }

        $categories = Database::fetchAll(
            'SELECT DISTINCT category FROM passwords WHERE user_id = ? ORDER BY category', [$uid]
        );

        $pageTitle = 'Password Vault';
        view('pages/passwords', compact('passwords', 'categories', 'search', 'cat', 'pageTitle'));
    }

    public static function store(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();

        $title    = sanitize(input('title', ''));
        $username = sanitize(input('username', ''));
        $password = input('password', '');
        $url      = sanitize(input('url', ''));
        $category = sanitize(input('category', '')) ?: detectCategory($title, $url);
        $notes    = sanitize(input('notes', ''));

        if (!$title) {
            flash('error', 'Title is required.');
            redirect('/passwords');
        }

        $strength = passwordStrength($password)['level'];

        Database::execute(
            "INSERT INTO passwords (user_id, title, username, password, url, category, notes, strength)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$uid, $title, Crypto::encrypt($username), Crypto::encrypt($password), $url, $category, $notes, $strength]
        );

        flash('success', 'Password saved successfully.');
        redirect('/passwords');
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();
        $id  = (int) $params['id'];

        $row = Database::fetch('SELECT * FROM passwords WHERE id = ? AND user_id = ?', [$id, $uid]);
        if (!$row) abort(404);

        $title    = sanitize(input('title', $row['title']));
        $username = sanitize(input('username', ''));
        $password = input('password', '');
        $url      = sanitize(input('url', $row['url'] ?? ''));
        $category = sanitize(input('category', $row['category'])) ?: detectCategory($title, $url);
        $notes    = sanitize(input('notes', ''));

        if (!$title) {
            flash('error', 'Title is required.');
            redirect('/passwords');
        }

        $encryptedPassword = $row['password'];
        $strength = $row['strength'] ?: 'weak';
        if ($password !== '') {
            $encryptedPassword = Crypto::encrypt($password);
            $strength = passwordStrength($password)['level'];
        }

        Database::execute(
            "UPDATE passwords SET title=?, username=?, password=?, url=?, category=?, notes=?, strength=?, updated_at=datetime('now') WHERE id=? AND user_id=?",
            [$title, Crypto::encrypt($username), $encryptedPassword, $url, $category, $notes, $strength, $id, $uid]
        );

        flash('success', 'Password updated successfully.');
        redirect('/passwords');
    }

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();
        $id  = (int) $params['id'];

        Database::execute('DELETE FROM passwords WHERE id = ? AND user_id = ?', [$id, $uid]);
        flash('success', 'Password deleted.');
        redirect('/passwords');
    }

    public static function reveal(array $params): void
    {
        Auth::requireAuth();
        $uid = userId();
        $id  = (int) $params['id'];

        $row = Database::fetch('SELECT password, username FROM passwords WHERE id = ? AND user_id = ?', [$id, $uid]);
        if (!$row) json(['error' => 'Not found'], 404);

        json([
            'password' => Crypto::decrypt($row['password']),
            'username' => Crypto::decrypt($row['username']),
        ]);
    }
}
