<?php
class DocumentController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid    = userId();
        $search = sanitize(input('q', ''));
        $type   = sanitize(input('type', ''));

        $sql    = 'SELECT id, title, type, number, issued_by, issue_date, expiry_date, notes, created_at FROM documents WHERE user_id = ?';
        $params = [$uid];

        if ($search) {
            $sql    .= ' AND (title LIKE ? OR type LIKE ?)';
            $params  = array_merge($params, ["%$search%", "%$search%"]);
        }
        if ($type) {
            $sql    .= ' AND type = ?';
            $params[] = $type;
        }

        $sql .= ' ORDER BY title ASC';

        $documents = Database::fetchAll($sql, $params);
        // Decrypt document numbers
        foreach ($documents as &$doc) {
            $doc['number_display'] = $doc['number'] ? Crypto::decrypt($doc['number']) : null;
        }
        unset($doc);

        $types = [
            'Citizenship', 'Passport', 'Driving Licence', 'Voter ID',
            'PAN Card', 'National ID', 'Birth Certificate',
            'Vehicle Registration', 'Insurance', 'Other'
        ];

        $pageTitle = 'Documents';
        view('pages/documents', compact('documents', 'types', 'search', 'type', 'pageTitle'));
    }

    public static function store(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();

        $title      = sanitize(input('title', ''));
        $type       = sanitize(input('type', ''));
        $number     = sanitize(input('number', ''));
        $issued_by  = sanitize(input('issued_by', ''));
        $issue_date = sanitize(input('issue_date', ''));
        $expiry_date= sanitize(input('expiry_date', ''));
        $notes      = sanitize(input('notes', ''));

        if (!$title || !$type) {
            flash('error', 'Title and type are required.');
            redirect('/documents');
        }

        Database::execute(
            "INSERT INTO documents (user_id, title, type, number, issued_by, issue_date, expiry_date, notes)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
            [$uid, $title, $type, Crypto::encrypt($number), $issued_by, $issue_date ?: null, $expiry_date ?: null, $notes]
        );

        flash('success', 'Document saved successfully.');
        redirect('/documents');
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();
        $id  = (int) $params['id'];

        $row = Database::fetch('SELECT id FROM documents WHERE id = ? AND user_id = ?', [$id, $uid]);
        if (!$row) abort(404);

        $title      = sanitize(input('title', ''));
        $type       = sanitize(input('type', ''));
        $number     = sanitize(input('number', ''));
        $issued_by  = sanitize(input('issued_by', ''));
        $issue_date = sanitize(input('issue_date', ''));
        $expiry_date= sanitize(input('expiry_date', ''));
        $notes      = sanitize(input('notes', ''));

        if (!$title || !$type) {
            flash('error', 'Title and type are required.');
            redirect('/documents');
        }

        Database::execute(
            "UPDATE documents SET title=?, type=?, number=?, issued_by=?, issue_date=?, expiry_date=?, notes=?, updated_at=datetime('now') WHERE id=? AND user_id=?",
            [$title, $type, Crypto::encrypt($number), $issued_by, $issue_date ?: null, $expiry_date ?: null, $notes, $id, $uid]
        );

        flash('success', 'Document updated successfully.');
        redirect('/documents');
    }

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();
        $id  = (int) $params['id'];

        Database::execute('DELETE FROM documents WHERE id = ? AND user_id = ?', [$id, $uid]);
        flash('success', 'Document deleted.');
        redirect('/documents');
    }
}
