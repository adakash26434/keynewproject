<?php
class InsightsController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid = userId();

        // Password stats
        $passwords = Database::fetchAll(
            'SELECT strength, password FROM passwords WHERE user_id = ?', [$uid]
        );
        $totalPw   = count($passwords);
        $weakPw    = count(array_filter($passwords, fn($p) => $p['strength'] === 'weak'));
        $strongPw  = count(array_filter($passwords, fn($p) => in_array($p['strength'], ['strong','very-strong'])));

        // Duplicate password detection (compare decrypted values)
        $pwValues   = array_map(fn($p) => Crypto::decrypt($p['password'] ?? ''), $passwords);
        $nonEmpty   = array_filter($pwValues);
        $duplicates = count($nonEmpty) - count(array_unique($nonEmpty));

        // Document expiry
        $documents = Database::fetchAll(
            'SELECT expiry_date FROM documents WHERE user_id = ?', [$uid]
        );
        $totalDocs    = count($documents);
        $expiringDocs = count(array_filter($documents, fn($d) => in_array(expiryStatus($d['expiry_date']), ['expired','expiring-soon'])));

        // Finance this month
        $month = (int) date('n');
        $year  = (int) date('Y');
        $fin   = Database::fetchAll(
            'SELECT type, amount FROM finance_records
             WHERE user_id = ? AND strftime("%m",record_date)=? AND strftime("%Y",record_date)=?',
            [$uid, str_pad($month, 2, '0', STR_PAD_LEFT), (string) $year]
        );
        $totalIncome   = array_sum(array_column(array_filter($fin, fn($f) => $f['type'] === 'income'),  'amount'));
        $totalExpenses = array_sum(array_column(array_filter($fin, fn($f) => $f['type'] === 'expense'), 'amount'));
        $savings       = $totalIncome - $totalExpenses;
        $savingsRate   = $totalIncome > 0 ? ($savings / $totalIncome) * 100 : 0;

        // Tasks
        $tasks       = Database::fetchAll('SELECT * FROM tasks WHERE user_id = ?', [$uid]);
        $totalTasks  = count($tasks);
        $doneTasks   = count(array_filter($tasks, fn($t) => $t['completed']));
        $overdueTasks= count(array_filter($tasks, fn($t) => !$t['completed'] && $t['due_date'] && daysUntil($t['due_date']) < 0));

        // Security score
        $score = 100;
        if ($totalPw > 0)   $score -= (int) min(35, ($weakPw / $totalPw) * 100);
        if ($totalDocs > 0) $score -= (int) min(25, ($expiringDocs / $totalDocs) * 100);
        if ($duplicates > 0) $score -= min(20, $duplicates * 5);
        $score = max(0, $score);

        // Build alerts
        $alerts = [];
        if ($weakPw > 0) {
            $alerts[] = ['severity'=>'high','title'=>'Weak Passwords Detected',
                'message'=>"$weakPw of your passwords are weak. Update them with the built-in generator."];
        }
        if ($duplicates > 0) {
            $alerts[] = ['severity'=>'high','title'=>'Duplicate Passwords',
                'message'=>"$duplicates passwords are reused across multiple accounts. Each account needs a unique password."];
        }
        if ($expiringDocs > 0) {
            $alerts[] = ['severity'=>'medium','title'=>'Documents Expiring',
                'message'=>"$expiringDocs document(s) have expired or will expire soon. Renew them promptly."];
        }
        if ($overdueTasks > 0) {
            $alerts[] = ['severity'=>'medium','title'=>'Overdue Tasks',
                'message'=>"$overdueTasks task(s) are overdue. Review your task list."];
        }
        if ($savingsRate < 10 && $totalIncome > 0) {
            $alerts[] = ['severity'=>'low','title'=>'Low Savings Rate',
                'message'=>'Your savings rate this month is below 10%. Try to reduce discretionary expenses.'];
        }
        if (empty($alerts)) {
            $alerts[] = ['severity'=>'low','title'=>'Everything looks great!',
                'message'=>'No security issues detected. Keep maintaining strong passwords and up-to-date documents.'];
        }

        view('pages/insights', [
            'user'          => Auth::user(),
            'score'         => $score,
            'alerts'        => $alerts,
            'totalPw'       => $totalPw,
            'weakPw'        => $weakPw,
            'strongPw'      => $strongPw,
            'duplicates'    => $duplicates,
            'totalDocs'     => $totalDocs,
            'expiringDocs'  => $expiringDocs,
            'totalIncome'   => $totalIncome,
            'totalExpenses' => $totalExpenses,
            'savings'       => $savings,
            'savingsRate'   => $savingsRate,
            'totalTasks'    => $totalTasks,
            'doneTasks'     => $doneTasks,
            'overdueTasks'  => $overdueTasks,
        ]);
    }
}
