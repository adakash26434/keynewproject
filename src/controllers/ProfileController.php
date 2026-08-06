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

        if (Auth::isPasswordReused($uid, $new, 3)) {
            flash('error', 'You cannot reuse your recent passwords. Please choose a different one.');
            redirect('/profile');
        }

        $newHash = Auth::hashPassword($new);

        Database::execute(
            "UPDATE users SET password_hash=?, updated_at=datetime('now') WHERE id=?",
            [$newHash, $uid]
        );

        Auth::recordPasswordHistory($uid, $newHash);

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

    public static function exportBackup(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();

        $uid = userId();
        $user = Database::fetch('SELECT email, name, created_at FROM users WHERE id = ?', [$uid]);
        if (!$user) {
            flash('error', 'User account not found.');
            redirect('/profile');
        }

        $payload = [
            'version' => 1,
            'exported_at' => gmdate('c'),
            'app' => APP_NAME,
            'user' => [
                'email' => $user['email'],
                'name' => $user['name'],
                'created_at' => $user['created_at'],
            ],
            'data' => [
                'passwords' => Database::fetchAll(
                    'SELECT title, username, password, url, category, notes, strength, created_at, updated_at FROM passwords WHERE user_id = ? ORDER BY id ASC',
                    [$uid]
                ),
                'documents' => Database::fetchAll(
                    'SELECT title, type, number, issued_by, issue_date, expiry_date, notes, created_at, updated_at FROM documents WHERE user_id = ? ORDER BY id ASC',
                    [$uid]
                ),
                'finance_records' => Database::fetchAll(
                    'SELECT type, amount, category, description, record_date, created_at, updated_at FROM finance_records WHERE user_id = ? ORDER BY id ASC',
                    [$uid]
                ),
                'tasks' => Database::fetchAll(
                    'SELECT title, description, priority, category, due_date, completed, created_at, updated_at FROM tasks WHERE user_id = ? ORDER BY id ASC',
                    [$uid]
                ),
                'cv_profile' => Database::fetch(
                    'SELECT full_name, job_title, email, phone, address, website, linkedin, summary, experience, education, skills, languages, template_color, created_at, updated_at FROM cv_profiles WHERE user_id = ?',
                    [$uid]
                ),
            ],
        ];

        $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            flash('error', 'Failed to build backup payload.');
            redirect('/profile');
        }

        $encrypted = Crypto::encrypt($json);
        if ($encrypted === null || $encrypted === '') {
            flash('error', 'Failed to encrypt backup data.');
            redirect('/profile');
        }

        $filename = 'key-wallet-backup-' . date('Ymd-His') . '.kwb';
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($encrypted));
        echo $encrypted;
        exit;
    }

    public static function importBackup(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();

        $uid = userId();

        if (!isset($_FILES['backup_file']) || !is_array($_FILES['backup_file'])) {
            flash('error', 'Please choose a backup file to import.');
            redirect('/profile');
        }

        $file = $_FILES['backup_file'];
        $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
        if ($errorCode !== UPLOAD_ERR_OK) {
            flash('error', 'Upload failed. Please try again.');
            redirect('/profile');
        }

        $tmpName = (string) ($file['tmp_name'] ?? '');
        $size = (int) ($file['size'] ?? 0);
        if ($tmpName === '' || $size <= 0) {
            flash('error', 'Uploaded file is empty.');
            redirect('/profile');
        }

        if ($size > 10 * 1024 * 1024) {
            flash('error', 'Backup file is too large. Maximum allowed size is 10MB.');
            redirect('/profile');
        }

        $encrypted = file_get_contents($tmpName);
        if ($encrypted === false || trim($encrypted) === '') {
            flash('error', 'Could not read uploaded backup file.');
            redirect('/profile');
        }

        $decrypted = Crypto::decrypt(trim($encrypted));
        if ($decrypted === null || $decrypted === '') {
            flash('error', 'Invalid backup file or encryption key mismatch.');
            redirect('/profile');
        }

        $decoded = json_decode($decrypted, true);
        if (!is_array($decoded) || !isset($decoded['data']) || !is_array($decoded['data'])) {
            flash('error', 'Backup file format is invalid.');
            redirect('/profile');
        }

        $data = $decoded['data'];
        $passwords = isset($data['passwords']) && is_array($data['passwords']) ? $data['passwords'] : [];
        $documents = isset($data['documents']) && is_array($data['documents']) ? $data['documents'] : [];
        $financeRecords = isset($data['finance_records']) && is_array($data['finance_records']) ? $data['finance_records'] : [];
        $tasks = isset($data['tasks']) && is_array($data['tasks']) ? $data['tasks'] : [];
        $cvProfile = isset($data['cv_profile']) && is_array($data['cv_profile']) ? $data['cv_profile'] : null;

        $db = Database::getInstance();

        try {
            $db->beginTransaction();

            Database::execute('DELETE FROM passwords WHERE user_id = ?', [$uid]);
            Database::execute('DELETE FROM documents WHERE user_id = ?', [$uid]);
            Database::execute('DELETE FROM finance_records WHERE user_id = ?', [$uid]);
            Database::execute('DELETE FROM tasks WHERE user_id = ?', [$uid]);
            Database::execute('DELETE FROM cv_profiles WHERE user_id = ?', [$uid]);

            foreach ($passwords as $row) {
                if (!is_array($row) || empty($row['title'])) {
                    continue;
                }
                Database::execute(
                    'INSERT INTO passwords (user_id, title, username, password, url, category, notes, strength, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $uid,
                        (string) $row['title'],
                        (string) ($row['username'] ?? ''),
                        (string) ($row['password'] ?? ''),
                        (string) ($row['url'] ?? ''),
                        (string) ($row['category'] ?? 'Other'),
                        (string) ($row['notes'] ?? ''),
                        (string) ($row['strength'] ?? 'weak'),
                        (string) ($row['created_at'] ?? date('c')),
                        (string) ($row['updated_at'] ?? date('c')),
                    ]
                );
            }

            foreach ($documents as $row) {
                if (!is_array($row) || empty($row['title']) || empty($row['type'])) {
                    continue;
                }
                Database::execute(
                    'INSERT INTO documents (user_id, title, type, number, issued_by, issue_date, expiry_date, notes, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $uid,
                        (string) $row['title'],
                        (string) $row['type'],
                        (string) ($row['number'] ?? ''),
                        (string) ($row['issued_by'] ?? ''),
                        ($row['issue_date'] ?? null) ?: null,
                        ($row['expiry_date'] ?? null) ?: null,
                        (string) ($row['notes'] ?? ''),
                        (string) ($row['created_at'] ?? date('c')),
                        (string) ($row['updated_at'] ?? date('c')),
                    ]
                );
            }

            foreach ($financeRecords as $row) {
                if (!is_array($row)) {
                    continue;
                }

                $type = (string) ($row['type'] ?? '');
                if (!in_array($type, ['income', 'expense'], true)) {
                    continue;
                }

                $amount = (float) ($row['amount'] ?? 0);
                $category = (string) ($row['category'] ?? 'Other');
                if ($amount <= 0 || $category === '') {
                    continue;
                }

                Database::execute(
                    'INSERT INTO finance_records (user_id, type, amount, category, description, record_date, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $uid,
                        $type,
                        $amount,
                        $category,
                        (string) ($row['description'] ?? ''),
                        (string) ($row['record_date'] ?? date('Y-m-d')),
                        (string) ($row['created_at'] ?? date('c')),
                        (string) ($row['updated_at'] ?? date('c')),
                    ]
                );
            }

            foreach ($tasks as $row) {
                if (!is_array($row) || empty($row['title'])) {
                    continue;
                }

                $priority = (string) ($row['priority'] ?? 'medium');
                if (!in_array($priority, ['low', 'medium', 'high'], true)) {
                    $priority = 'medium';
                }

                Database::execute(
                    'INSERT INTO tasks (user_id, title, description, priority, category, due_date, completed, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $uid,
                        (string) $row['title'],
                        (string) ($row['description'] ?? ''),
                        $priority,
                        (string) ($row['category'] ?? 'Personal'),
                        ($row['due_date'] ?? null) ?: null,
                        !empty($row['completed']) ? 1 : 0,
                        (string) ($row['created_at'] ?? date('c')),
                        (string) ($row['updated_at'] ?? date('c')),
                    ]
                );
            }

            if ($cvProfile !== null) {
                Database::execute(
                    'INSERT INTO cv_profiles (user_id, full_name, job_title, email, phone, address, website, linkedin, summary, experience, education, skills, languages, template_color, created_at, updated_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [
                        $uid,
                        (string) ($cvProfile['full_name'] ?? ''),
                        (string) ($cvProfile['job_title'] ?? ''),
                        (string) ($cvProfile['email'] ?? ''),
                        (string) ($cvProfile['phone'] ?? ''),
                        (string) ($cvProfile['address'] ?? ''),
                        (string) ($cvProfile['website'] ?? ''),
                        (string) ($cvProfile['linkedin'] ?? ''),
                        (string) ($cvProfile['summary'] ?? ''),
                        (string) ($cvProfile['experience'] ?? '[]'),
                        (string) ($cvProfile['education'] ?? '[]'),
                        (string) ($cvProfile['skills'] ?? '[]'),
                        (string) ($cvProfile['languages'] ?? '[]'),
                        (string) ($cvProfile['template_color'] ?? '#0078D4'),
                        (string) ($cvProfile['created_at'] ?? date('c')),
                        (string) ($cvProfile['updated_at'] ?? date('c')),
                    ]
                );
            }

            $db->commit();
        } catch (Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            flash('error', 'Backup restore failed. Please verify the backup file and try again.');
            redirect('/profile');
        }

        flash('success', 'Backup restored successfully.');
        redirect('/profile');
    }
}
