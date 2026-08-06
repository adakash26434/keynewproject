<?php
class DashboardController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid = userId();

        $cacheKey = 'dashboard:' . $uid . ':' . date('Y-m-d-H-i');
        $data = cacheRemember($cacheKey, 20, function () use ($uid): array {
            $yearMonthExpr = Database::yearMonthExpression('record_date');
            $passwordCount = (int) Database::fetch(
                'SELECT COUNT(*) as c FROM passwords WHERE user_id = ?', [$uid]
            )['c'];

            $documentCount = (int) Database::fetch(
                'SELECT COUNT(*) as c FROM documents WHERE user_id = ?', [$uid]
            )['c'];

            $taskCount = (int) Database::fetch(
                'SELECT COUNT(*) as c FROM tasks WHERE user_id = ? AND completed = 0', [$uid]
            )['c'];

            $month = date('Y-m');
            $income = (float) (Database::fetch(
                'SELECT COALESCE(SUM(amount), 0) as s FROM finance_records WHERE user_id = ? AND type=\'income\' AND ' . $yearMonthExpr . ' = ?',
                [$uid, $month]
            )['s'] ?? 0);
            $expenses = (float) (Database::fetch(
                'SELECT COALESCE(SUM(amount), 0) as s FROM finance_records WHERE user_id = ? AND type=\'expense\' AND ' . $yearMonthExpr . ' = ?',
                [$uid, $month]
            )['s'] ?? 0);

            $weakPasswords = Database::fetchAll(
                "SELECT COUNT(*) as c FROM passwords WHERE user_id = ? AND strength IN ('weak', 'fair')",
                [$uid]
            );
            $weakCount = (int) ($weakPasswords[0]['c'] ?? 0);

            $allPasswords = Database::fetchAll(
                'SELECT password, created_at FROM passwords WHERE user_id = ?',
                [$uid]
            );
            $plainValues = array_map(fn($p) => Crypto::decrypt($p['password'] ?? ''), $allPasswords);
            $nonEmptyValues = array_values(array_filter($plainValues, static fn($v) => is_string($v) && $v !== ''));
            $duplicateCount = count($nonEmptyValues) - count(array_unique($nonEmptyValues));
            $stalePwCount = 0;
            foreach ($allPasswords as $row) {
                $age = daysSince((string) ($row['created_at'] ?? ''));
                if ($age !== null && $age >= 180) {
                    $stalePwCount++;
                }
            }

            $docCandidates = Database::fetchAll(
                "SELECT title, expiry_date FROM documents WHERE user_id = ? AND expiry_date IS NOT NULL AND expiry_date != '' ORDER BY expiry_date ASC",
                [$uid]
            );
            $expiringDocs = [];
            foreach ($docCandidates as $doc) {
                $days = daysUntil((string) ($doc['expiry_date'] ?? ''));
                if ($days !== null && $days >= 0 && $days <= 60) {
                    $expiringDocs[] = $doc;
                }
                if (count($expiringDocs) >= 5) {
                    break;
                }
            }

            $sessions = Auth::loginSessions($uid, 12);
            $ips = [];
            foreach ($sessions as $s) {
                $ip = trim((string) ($s['ip_address'] ?? ''));
                if ($ip !== '') {
                    $ips[$ip] = true;
                }
            }
            $multiIpRisk = count($ips) > 1;

            $smartAlerts = [];
            if ($duplicateCount > 0) {
                $smartAlerts[] = [
                    'severity' => 'high',
                    'title' => 'Duplicate Passwords',
                    'message' => $duplicateCount . ' duplicate password(s) detected. Replace with unique passwords.',
                    'url' => '/passwords',
                ];
            }
            if ($stalePwCount > 0) {
                $smartAlerts[] = [
                    'severity' => 'medium',
                    'title' => 'Password Rotation Due',
                    'message' => $stalePwCount . ' password(s) are older than 180 days.',
                    'url' => '/passwords',
                ];
            }
            if (count($expiringDocs) > 0) {
                $smartAlerts[] = [
                    'severity' => 'medium',
                    'title' => 'Document Expiry Reminder',
                    'message' => count($expiringDocs) . ' document(s) expire within 60 days.',
                    'url' => '/documents',
                ];
            }
            if ($multiIpRisk) {
                $smartAlerts[] = [
                    'severity' => 'low',
                    'title' => 'Multiple Login IPs Found',
                    'message' => 'Recent sessions include multiple IP addresses. Review devices for safety.',
                    'url' => '/profile',
                ];
            }

            $recentPasswords = Database::fetchAll(
                'SELECT id, title, username, category, created_at FROM passwords WHERE user_id = ? ORDER BY created_at DESC LIMIT 5',
                [$uid]
            );
            foreach ($recentPasswords as &$pw) {
                $pw['username'] = $pw['username'] ? (Crypto::decrypt($pw['username']) ?? '') : '';
            }
            unset($pw);

            $recentTasks = Database::fetchAll(
                'SELECT id, title, priority, due_date, completed FROM tasks WHERE user_id = ? ORDER BY created_at DESC LIMIT 5',
                [$uid]
            );

            return [
                'passwordCount' => $passwordCount,
                'documentCount' => $documentCount,
                'taskCount' => $taskCount,
                'income' => $income,
                'expenses' => $expenses,
                'weakCount' => $weakCount,
                'duplicateCount' => $duplicateCount,
                'stalePwCount' => $stalePwCount,
                'expiringDocs' => $expiringDocs,
                'smartAlerts' => $smartAlerts,
                'recentPasswords' => $recentPasswords,
                'recentTasks' => $recentTasks,
            ];
        });

        $passwordCount = (int) ($data['passwordCount'] ?? 0);
        $documentCount = (int) ($data['documentCount'] ?? 0);
        $taskCount = (int) ($data['taskCount'] ?? 0);
        $income = (float) ($data['income'] ?? 0);
        $expenses = (float) ($data['expenses'] ?? 0);
        $weakCount = (int) ($data['weakCount'] ?? 0);
        $duplicateCount = (int) ($data['duplicateCount'] ?? 0);
        $stalePwCount = (int) ($data['stalePwCount'] ?? 0);
        $expiringDocs = is_array($data['expiringDocs'] ?? null) ? $data['expiringDocs'] : [];
        $smartAlerts = is_array($data['smartAlerts'] ?? null) ? $data['smartAlerts'] : [];
        $recentPasswords = is_array($data['recentPasswords'] ?? null) ? $data['recentPasswords'] : [];
        $recentTasks = is_array($data['recentTasks'] ?? null) ? $data['recentTasks'] : [];

        sendDailySmartAlertDigest($uid, $smartAlerts);

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
            'recentPasswords', 'recentTasks', 'score', 'pageTitle',
            'duplicateCount', 'stalePwCount', 'smartAlerts'
        ));
    }
}
