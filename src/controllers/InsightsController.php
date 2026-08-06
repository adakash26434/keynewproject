<?php
class InsightsController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid = userId();

        $cacheKey = 'insights:' . $uid . ':' . date('Y-m-d-H-i');
        $data = cacheRemember($cacheKey, 25, function () use ($uid): array {
            $passwords = Database::fetchAll(
                'SELECT strength, password FROM passwords WHERE user_id = ?', [$uid]
            );
            $totalPw   = count($passwords);
            $weakPw    = count(array_filter($passwords, fn($p) => $p['strength'] === 'weak'));
            $strongPw  = count(array_filter($passwords, fn($p) => in_array($p['strength'], ['strong','very-strong'])));

            $pwValues   = array_map(fn($p) => Crypto::decrypt($p['password'] ?? ''), $passwords);
            $nonEmpty   = array_filter($pwValues);
            $duplicates = count($nonEmpty) - count(array_unique($nonEmpty));

            $documents = Database::fetchAll(
                'SELECT expiry_date FROM documents WHERE user_id = ?', [$uid]
            );
            $totalDocs = count($documents);
            $expiringDocs = count(array_filter($documents, fn($d) => in_array(expiryStatus($d['expiry_date']), ['expired','expiring-soon'])));

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

            $tasks       = Database::fetchAll('SELECT * FROM tasks WHERE user_id = ?', [$uid]);
            $totalTasks  = count($tasks);
            $doneTasks   = count(array_filter($tasks, fn($t) => $t['completed']));
            $overdueTasks= count(array_filter($tasks, fn($t) => !$t['completed'] && $t['due_date'] && daysUntil($t['due_date']) < 0));

            $score = 100;
            if ($totalPw > 0)   $score -= (int) min(35, ($weakPw / $totalPw) * 100);
            if ($totalDocs > 0) $score -= (int) min(25, ($expiringDocs / $totalDocs) * 100);
            if ($duplicates > 0) $score -= min(20, $duplicates * 5);
            $score = max(0, $score);

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

            return [
                'score' => $score,
                'alerts' => $alerts,
                'totalPw' => $totalPw,
                'weakPw' => $weakPw,
                'strongPw' => $strongPw,
                'duplicates' => $duplicates,
                'totalDocs' => $totalDocs,
                'expiringDocs' => $expiringDocs,
                'totalIncome' => $totalIncome,
                'totalExpenses' => $totalExpenses,
                'savings' => $savings,
                'savingsRate' => $savingsRate,
                'totalTasks' => $totalTasks,
                'doneTasks' => $doneTasks,
                'overdueTasks' => $overdueTasks,
            ];
        });

        view('pages/insights', [
            'user'          => Auth::user(),
            'score'         => (int) ($data['score'] ?? 0),
            'alerts'        => is_array($data['alerts'] ?? null) ? $data['alerts'] : [],
            'totalPw'       => (int) ($data['totalPw'] ?? 0),
            'weakPw'        => (int) ($data['weakPw'] ?? 0),
            'strongPw'      => (int) ($data['strongPw'] ?? 0),
            'duplicates'    => (int) ($data['duplicates'] ?? 0),
            'totalDocs'     => (int) ($data['totalDocs'] ?? 0),
            'expiringDocs'  => (int) ($data['expiringDocs'] ?? 0),
            'totalIncome'   => (float) ($data['totalIncome'] ?? 0),
            'totalExpenses' => (float) ($data['totalExpenses'] ?? 0),
            'savings'       => (float) ($data['savings'] ?? 0),
            'savingsRate'   => (float) ($data['savingsRate'] ?? 0),
            'totalTasks'    => (int) ($data['totalTasks'] ?? 0),
            'doneTasks'     => (int) ($data['doneTasks'] ?? 0),
            'overdueTasks'  => (int) ($data['overdueTasks'] ?? 0),
        ]);
    }
}
