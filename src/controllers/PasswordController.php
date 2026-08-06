<?php
class PasswordController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid    = userId();
        $search = sanitize(input('q', ''));
        $cat    = sanitize(input('category', ''));
        $strength = sanitize(input('strength', ''));
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

        if ($strength !== '' && in_array($strength, ['weak', 'fair', 'good', 'strong', 'very-strong'], true)) {
            $where .= ' AND strength = ?';
            $params[] = $strength;
        }

        $total = (int) (Database::fetch('SELECT COUNT(*) as c' . $where, $params)['c'] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = 'SELECT id, title, username, url, category, notes, strength, created_at'
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

        $importPreview = $_SESSION['_csv_import_preview'] ?? null;
        if (!is_array($importPreview)) {
            $importPreview = null;
        }

        $pageTitle = 'Password Vault';
        view('pages/passwords', compact('passwords', 'categories', 'search', 'cat', 'strength', 'pageTitle', 'page', 'perPage', 'total', 'totalPages', 'importPreview'));
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

    public static function importCsv(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();

        $parsed = self::parseCsvUpload();
        if ($parsed === null) {
            redirect('/passwords');
        }

        [$rows, $skipped] = $parsed;

        if (empty($rows)) {
            flash('error', 'No valid password rows found in CSV.');
            redirect('/passwords');
        }

        $existing = Database::fetchAll('SELECT title, username, url FROM passwords WHERE user_id = ?', [$uid]);
        $existingKeys = [];
        foreach ($existing as $e) {
            $existingUsername = '';
            if (!empty($e['username'])) {
                $existingUsername = Crypto::decrypt((string) $e['username']) ?? '';
            }
            $existingKeys[self::dupKey((string) ($e['title'] ?? ''), $existingUsername, (string) ($e['url'] ?? ''))] = true;
        }

        $previewRows = [];
        $duplicateRows = 0;
        $seenImportKeys = [];
        foreach ($rows as $r) {
            [$title, $username, $password, $url, $category, $notes] = $r;
            $key = self::dupKey($title, $username, $url);
            $isDuplicate = isset($existingKeys[$key]) || isset($seenImportKeys[$key]);
            if ($isDuplicate) {
                $duplicateRows++;
            }

            $seenImportKeys[$key] = true;

            $previewRows[] = [
                'title' => $title,
                'username' => $username,
                'password' => $password,
                'url' => $url,
                'category' => $category,
                'notes' => $notes,
                'duplicate' => $isDuplicate,
            ];
        }

        $_SESSION['_csv_import_preview'] = [
            'rows' => $previewRows,
            'total' => count($previewRows),
            'duplicates' => $duplicateRows,
            'skipped' => $skipped,
            'created_at' => time(),
        ];

        flash('success', 'CSV preview prepared. Review and confirm import below.');
        redirect('/passwords');
    }

    public static function confirmImportCsv(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();

        $preview = $_SESSION['_csv_import_preview'] ?? null;
        if (!is_array($preview) || !isset($preview['rows']) || !is_array($preview['rows'])) {
            flash('error', 'No import preview found. Please upload CSV first.');
            redirect('/passwords');
        }

        $rows = $preview['rows'];
        $imported = 0;
        $skipped = 0;

        foreach ($rows as $row) {
            if (!is_array($row)) {
                $skipped++;
                continue;
            }
            if (!empty($row['duplicate'])) {
                $skipped++;
                continue;
            }

            $title = (string) ($row['title'] ?? '');
            $username = (string) ($row['username'] ?? '');
            $password = (string) ($row['password'] ?? '');
            $url = (string) ($row['url'] ?? '');
            $category = (string) ($row['category'] ?? 'Other');
            $notes = (string) ($row['notes'] ?? '');

            if ($title === '' || $password === '') {
                $skipped++;
                continue;
            }

            $strength = passwordStrength($password)['level'];

            Database::execute(
                'INSERT INTO passwords (user_id, title, username, password, url, category, notes, strength)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [
                    $uid,
                    $title,
                    Crypto::encrypt($username),
                    Crypto::encrypt($password),
                    $url,
                    $category,
                    $notes,
                    $strength,
                ]
            );
            $imported++;
        }

        unset($_SESSION['_csv_import_preview']);

        if ($imported > 0) {
            flash('success', 'Imported ' . $imported . ' password(s). Skipped ' . $skipped . ' row(s).');
        } else {
            flash('error', 'No rows were imported. All rows may be duplicates or invalid.');
        }

        redirect('/passwords');
    }

    public static function removeImportPreviewRow(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();

        $index = (int) input('index', -1);
        $preview = $_SESSION['_csv_import_preview'] ?? null;
        if (!is_array($preview) || !isset($preview['rows']) || !is_array($preview['rows'])) {
            flash('error', 'No import preview found.');
            redirect('/passwords');
        }

        if ($index < 0 || !isset($preview['rows'][$index])) {
            flash('error', 'Invalid preview row selected.');
            redirect('/passwords');
        }

        unset($preview['rows'][$index]);
        $preview['rows'] = array_values($preview['rows']);
        self::refreshPreviewMeta($preview);
        $_SESSION['_csv_import_preview'] = $preview;

        flash('success', 'Preview row removed.');
        redirect('/passwords');
    }

    public static function clearImportPreview(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();

        unset($_SESSION['_csv_import_preview']);
        flash('success', 'Import preview cleared.');
        redirect('/passwords');
    }

    public static function extensionSearch(): void
    {
        Auth::requireAuth();
        $uid = userId();
        $q = trim((string) input('q', ''));

        if (strlen($q) < 2) {
            json(['results' => []]);
        }

        $like = '%' . $q . '%';
        $rows = Database::fetchAll(
            'SELECT id, title, username, url, category FROM passwords
             WHERE user_id = ? AND (title LIKE ? OR category LIKE ? OR url LIKE ?)
             ORDER BY updated_at DESC LIMIT 20',
            [$uid, $like, $like, $like]
        );

        $results = [];
        foreach ($rows as $r) {
            $results[] = [
                'id' => (int) $r['id'],
                'title' => (string) $r['title'],
                'username' => $r['username'] ? (Crypto::decrypt((string) $r['username']) ?? '') : '',
                'url' => (string) ($r['url'] ?? ''),
                'category' => (string) ($r['category'] ?? ''),
            ];
        }

        json(['results' => $results]);
    }

    private static function mapCsvRow(array $headerMap, array $row): ?array
    {
        $v = static function (string $key) use ($headerMap, $row): string {
            $idx = $headerMap[$key] ?? null;
            if ($idx === null) return '';
            return trim((string) ($row[$idx] ?? ''));
        };

        // Chrome CSV
        $chromeName = $v('name');
        $chromePassword = $v('password');
        if ($chromeName !== '' && $chromePassword !== '' && isset($headerMap['username'])) {
            $url = $v('url');
            $title = sanitize($chromeName);
            $username = sanitize($v('username'));
            $notes = sanitize($v('note'));
            $category = detectCategory($title, $url);

            return [$title, $username, $chromePassword, sanitize($url), $category, $notes];
        }

        // Bitwarden CSV
        $bwName = $v('name');
        $bwPassword = $v('login_password');
        if ($bwName !== '' && $bwPassword !== '' && isset($headerMap['login_username'])) {
            $uri = $v('login_uri');
            if ($uri === '') {
                $uri = $v('uri');
            }

            $title = sanitize($bwName);
            $username = sanitize($v('login_username'));
            $notes = sanitize($v('notes'));
            $category = detectCategory($title, $uri);

            return [$title, $username, $bwPassword, sanitize($uri), $category, $notes];
        }

        return null;
    }

    private static function parseCsvUpload(): ?array
    {
        if (!isset($_FILES['csv_file']) || !is_array($_FILES['csv_file'])) {
            flash('error', 'Please choose a CSV file.');
            return null;
        }

        $file = $_FILES['csv_file'];
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            flash('error', 'CSV upload failed. Please try again.');
            return null;
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmpName === '' || $size <= 0) {
            flash('error', 'Uploaded CSV file is empty.');
            return null;
        }
        if ($size > 5 * 1024 * 1024) {
            flash('error', 'CSV file is too large. Maximum size is 5MB.');
            return null;
        }

        $fp = fopen($tmpName, 'rb');
        if ($fp === false) {
            flash('error', 'Unable to read uploaded CSV file.');
            return null;
        }

        $header = fgetcsv($fp);
        if (!is_array($header) || empty($header)) {
            fclose($fp);
            flash('error', 'CSV header is invalid.');
            return null;
        }

        $headerMap = [];
        foreach ($header as $i => $col) {
            $key = strtolower(trim((string) $col));
            if ($key !== '') {
                $headerMap[$key] = (int) $i;
            }
        }

        $rows = [];
        $skipped = 0;
        $maxRows = 3000;
        $count = 0;

        while (($row = fgetcsv($fp)) !== false) {
            $count++;
            if ($count > $maxRows) {
                $skipped++;
                continue;
            }

            $mapped = self::mapCsvRow($headerMap, $row);
            if ($mapped === null) {
                $skipped++;
                continue;
            }

            $rows[] = $mapped;
        }

        fclose($fp);
        return [$rows, $skipped];
    }

    private static function dupKey(string $title, string $username, string $url): string
    {
        return strtolower(trim($title)) . '|' . strtolower(trim($username)) . '|' . strtolower(trim($url));
    }

    private static function refreshPreviewMeta(array &$preview): void
    {
        $rows = isset($preview['rows']) && is_array($preview['rows']) ? $preview['rows'] : [];
        $duplicates = 0;
        foreach ($rows as $r) {
            if (is_array($r) && !empty($r['duplicate'])) {
                $duplicates++;
            }
        }

        $preview['total'] = count($rows);
        $preview['duplicates'] = $duplicates;
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
            'UPDATE passwords SET title=?, username=?, password=?, url=?, category=?, notes=?, strength=?, updated_at=' . Database::nowExpression() . ' WHERE id=? AND user_id=?',
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
