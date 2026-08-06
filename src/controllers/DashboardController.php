<?php
class DashboardController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid = userId();

        $passwordCount = (int) Database::fetch(
            'SELECT COUNT(*) as c FROM passwords WHERE user_id = ?', [$uid]
        )['c'];

        $documentCount = (int) Database::fetch(
            'SELECT COUNT(*) as c FROM documents WHERE user_id = ?', [$uid]
        )['c'];

        $taskCount = (int) Database::fetch(
            'SELECT COUNT(*) as c FROM tasks WHERE user_id = ? AND completed = 0', [$uid]
        )['c'];

        // Finance summary for current month
        $month = date('Y-m');
        $income = (float) (Database::fetch(
            "SELECT COALESCE(SUM(amount), 0) as s FROM finance_records WHERE user_id = ? AND type='income' AND strftime('%Y-%m', record_date) = ?",
            [$uid, $month]
        )['s'] ?? 0);
        $expenses = (float) (Database::fetch(
            "SELECT COALESCE(SUM(amount), 0) as s FROM finance_records WHERE user_id = ? AND type='expense' AND strftime('%Y-%m', record_date) = ?",
            [$uid, $month]
        )['s'] ?? 0);

        // Weak passwords
        $weakPasswords = Database::fetchAll(
            "SELECT COUNT(*) as c FROM passwords WHERE user_id = ? AND strength IN ('weak', 'fair')",
            [$uid]
        );
        $weakCount = (int) ($weakPasswords[0]['c'] ?? 0);

        // Expiring documents (within 60 days)
        $expiringDocs = Database::fetchAll(
            "SELECT title, expiry_date FROM documents WHERE user_id = ? AND expiry_date IS NOT NULL AND expiry_date != '' AND date(expiry_date) BETWEEN date('now') AND date('now', '+60 days') ORDER BY expiry_date ASC LIMIT 5",
            [$uid]
        );

        // Recent passwords
        $recentPasswords = Database::fetchAll(
            'SELECT id, title, username, category, created_at FROM passwords WHERE user_id = ? ORDER BY created_at DESC LIMIT 5',
            [$uid]
        );
        foreach ($recentPasswords as &$pw) {
            $pw['username'] = $pw['username'] ? (Crypto::decrypt($pw['username']) ?? '') : '';
        }
        unset($pw);

        // Recent tasks
        $recentTasks = Database::fetchAll(
            'SELECT id, title, priority, due_date, completed FROM tasks WHERE user_id = ? ORDER BY created_at DESC LIMIT 5',
            [$uid]
        );

        // Security score
        $totalPasswords = $passwordCount;
        $score = 100;
        if ($totalPasswords > 0) {
            $weakRatio = $weakCount / $totalPasswords;
            $score = max(0, (int) (100 - $weakRatio * 50));
        }
        if (count($expiringDocs) > 0) $score = max(0, $score - 15);

        $pageTitle = 'Dashboard';

        view('pages/dashboard', compact(
            'passwordCount', 'documentCount', 'taskCount',
            'income', 'expenses', 'weakCount', 'expiringDocs',
            'recentPasswords', 'recentTasks', 'score', 'pageTitle'
        ));
    }
}
