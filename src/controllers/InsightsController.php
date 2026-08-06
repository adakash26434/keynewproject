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
                'SELECT strength, password, created_at FROM passwords WHERE user_id = ?', [$uid]
            );
            $totalPw   = count($passwords);
            $weakPw    = count(array_filter($passwords, fn($p) => $p['strength'] === 'weak'));
            $strongPw  = count(array_filter($passwords, fn($p) => in_array($p['strength'], ['strong','very-strong'])));

            $pwValues   = array_map(fn($p) => Crypto::decrypt($p['password'] ?? ''), $passwords);
            $nonEmpty   = array_filter($pwValues);
            $duplicates = count($nonEmpty) - count(array_unique($nonEmpty));
            $commonRiskPw = 0;
            foreach ($pwValues as $plain) {
                if (is_string($plain) && isCommonPassword($plain)) {
                    $commonRiskPw++;
                }
            }

            $stalePw = 0;
            foreach ($passwords as $row) {
                $age = daysSince((string) ($row['created_at'] ?? ''));
                if ($age !== null && $age >= 180) {
                    $stalePw++;
                }
            }

            $breachedPw = 0;
            $breachChecksSkipped = 0;
            $seenPw = [];
            $toCheck = 0;
            foreach ($pwValues as $plain) {
                if (!is_string($plain) || $plain === '') {
                    continue;
                }
                if (isset($seenPw[$plain])) {
                    continue;
                }
                $seenPw[$plain] = true;

                $toCheck++;
                if ($toCheck > 25) {
                    $breachChecksSkipped++;
                    continue;
                }

                $pwnedCount = pwnedPasswordCount($plain);
                if ($pwnedCount === null) {
                    $breachChecksSkipped++;
                    continue;
                }
                if ($pwnedCount > 0) {
                    $breachedPw++;
                }
            }

            $documents = Database::fetchAll(
                'SELECT expiry_date FROM documents WHERE user_id = ?', [$uid]
            );
            $totalDocs = count($documents);
            $expiringDocs = count(array_filter($documents, fn($d) => in_array(expiryStatus($d['expiry_date']), ['expired','expiring-soon'])));

            $month = (int) date('n');
            $year  = (int) date('Y');
            $monthStr = sprintf('%04d-%02d', $year, $month);
            $yearMonthExpr = Database::yearMonthExpression('record_date');
            $fin   = Database::fetchAll(
                'SELECT type, amount FROM finance_records
                 WHERE user_id = ? AND ' . $yearMonthExpr . ' = ?',
                [$uid, $monthStr]
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
            if ($breachedPw > 0) $score -= min(25, $breachedPw * 6);
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
            if ($commonRiskPw > 0) {
                $alerts[] = ['severity'=>'high','title'=>'Common Password Risk',
                    'message'=>"$commonRiskPw password(s) look like commonly breached patterns. Change them immediately."];
            }
            if ($stalePw > 0) {
                $alerts[] = ['severity'=>'medium','title'=>'Old Passwords Need Rotation',
                    'message'=>"$stalePw password(s) are older than 180 days. Rotate them for better security."];
            }
            if ($breachedPw > 0) {
                $alerts[] = ['severity'=>'high','title'=>'Breach Exposure Detected',
                    'message'=>"$breachedPw password(s) were found in known breach datasets. Change them immediately."];
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
                'commonRiskPw' => $commonRiskPw,
                'stalePw' => $stalePw,
                'breachedPw' => $breachedPw,
                'breachChecksSkipped' => $breachChecksSkipped,
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
            'commonRiskPw'  => (int) ($data['commonRiskPw'] ?? 0),
            'stalePw'       => (int) ($data['stalePw'] ?? 0),
            'breachedPw'    => (int) ($data['breachedPw'] ?? 0),
            'breachChecksSkipped' => (int) ($data['breachChecksSkipped'] ?? 0),
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
