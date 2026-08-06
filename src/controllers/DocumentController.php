<?php
class DocumentController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid    = userId();
        $search = sanitize(input('q', ''));
        $type   = sanitize(input('type', ''));
        $page   = max(1, (int) input('page', 1));
        $perPage = (int) input('per_page', 30);
        $perPage = max(10, min($perPage, 80));
        $offset = ($page - 1) * $perPage;

        $where  = ' FROM documents WHERE user_id = ?';
        $params = [$uid];

        if ($search) {
            $where  .= ' AND (title LIKE ? OR type LIKE ?)';
            $params  = array_merge($params, ["%$search%", "%$search%"]);
        }
        if ($type) {
            $where  .= ' AND type = ?';
            $params[] = $type;
        }

        $total = (int) (Database::fetch('SELECT COUNT(*) as c' . $where, $params)['c'] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $sql = 'SELECT id, title, type, number, issued_by, issue_date, expiry_date, notes, created_at'
            . $where
            . ' ORDER BY title ASC LIMIT ' . $perPage . ' OFFSET ' . $offset;

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
        view('pages/documents', compact('documents', 'types', 'search', 'type', 'pageTitle', 'page', 'perPage', 'total', 'totalPages'));
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
            'UPDATE documents SET title=?, type=?, number=?, issued_by=?, issue_date=?, expiry_date=?, notes=?, updated_at=' . Database::nowExpression() . ' WHERE id=? AND user_id=?',
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
