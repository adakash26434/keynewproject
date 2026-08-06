<?php
class PasswordController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid    = userId();
        $search = sanitize(input('q', ''));
        $cat    = sanitize(input('category', ''));
        $page   = max(1, (int) input('page', 1));
        $perPage = (int) input('per_page', 40);
        $perPage = max(10, min($perPage, 100));
        $offset = ($page - 1) * $perPage;

        $where = ' FROM passwords WHERE user_id = ?';
        $params = [$uid];

        if ($search !== '') {
            $where .= ' AND (title LIKE ? OR url LIKE ? OR category LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        if ($cat) {
            $where .= ' AND category = ?';
            $params[] = $cat;
        }

        $total = (int) (Database::fetch('SELECT COUNT(*) as c' . $where, $params)['c'] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = 'SELECT id, title, username, url, category, strength, created_at'
            . $where
            . ' ORDER BY title ASC LIMIT ' . $perPage . ' OFFSET ' . $offset;

        $passwords  = Database::fetchAll($sql, $params);
        foreach ($passwords as &$pw) {
            $pw['username'] = $pw['username'] ? (Crypto::decrypt($pw['username']) ?? '') : '';
        }
        unset($pw);

        $categories = Database::fetchAll(
            'SELECT DISTINCT category FROM passwords WHERE user_id = ? ORDER BY category', [$uid]
        );

        $pageTitle = 'Password Vault';
        view('pages/passwords', compact('passwords', 'categories', 'search', 'cat', 'pageTitle', 'page', 'perPage', 'total', 'totalPages'));
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
