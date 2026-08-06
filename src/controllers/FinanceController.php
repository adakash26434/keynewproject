<?php
class FinanceController
{
    public static function index(): void
    {
        Auth::requireAuth();
        $uid   = userId();
        $year  = (int) input('year', date('Y'));
        $month = (int) input('month', date('n'));
        $page  = max(1, (int) input('page', 1));
        $perPage = (int) input('per_page', 60);
        $perPage = max(20, min($perPage, 150));
        $offset = ($page - 1) * $perPage;

        $monthStr = sprintf('%04d-%02d', $year, $month);

        $total = (int) (Database::fetch(
            "SELECT COUNT(*) as c FROM finance_records WHERE user_id = ? AND strftime('%Y-%m', record_date) = ?",
            [$uid, $monthStr]
        )['c'] ?? 0);
        $totalPages = max(1, (int) ceil($total / $perPage));
        if ($page > $totalPages) {
            $page = $totalPages;
            $offset = ($page - 1) * $perPage;
        }

        $records = Database::fetchAll(
            "SELECT * FROM finance_records
             WHERE user_id = ? AND strftime('%Y-%m', record_date) = ?
             ORDER BY record_date DESC, created_at DESC
             LIMIT " . $perPage . " OFFSET " . $offset,
            [$uid, $monthStr]
        );

        $income   = 0.0;
        $expenses = 0.0;
        $byCategory = [];

        foreach ($records as &$r) {
            $amt = (float) $r['amount'];
            if ($r['type'] === 'income') {
                $income += $amt;
            } else {
                $expenses += $amt;
                $byCategory[$r['category']] = ($byCategory[$r['category']] ?? 0) + $amt;
            }
            $r['amount_fmt'] = formatNPR($amt);
        }
        unset($r);

        arsort($byCategory);
        $balance = $income - $expenses;

        $categories = [
            'Food & Dining', 'Transport', 'Shopping', 'Healthcare', 'Education',
            'Entertainment', 'Utilities', 'Rent', 'Salary', 'Business',
            'Investment', 'Savings', 'Other'
        ];

        $monthNames = ['', 'January', 'February', 'March', 'April', 'May', 'June',
                       'July', 'August', 'September', 'October', 'November', 'December'];

        $pageTitle = 'Finance Tracker';
        view('pages/finance', compact(
            'records', 'income', 'expenses', 'balance',
            'byCategory', 'categories', 'year', 'month', 'monthStr',
            'monthNames', 'pageTitle', 'page', 'perPage', 'total', 'totalPages'
        ));
    }

    public static function store(): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();

        $type        = sanitize(input('type', ''));
        $amount      = (float) input('amount', 0);
        $category    = sanitize(input('category', ''));
        $description = sanitize(input('description', ''));
        $record_date = sanitize(input('record_date', date('Y-m-d')));

        if (!in_array($type, ['income', 'expense']) || $amount <= 0 || !$category) {
            flash('error', 'Please fill all required fields.');
            redirect('/finance');
        }

        Database::execute(
            "INSERT INTO finance_records (user_id, type, amount, category, description, record_date) VALUES (?,?,?,?,?,?)",
            [$uid, $type, $amount, $category, $description, $record_date]
        );

        flash('success', ucfirst($type) . ' recorded successfully.');

        $y = (int) date('Y', strtotime($record_date));
        $m = (int) date('n', strtotime($record_date));
        redirect("/finance?year=$y&month=$m");
    }

    public static function update(array $params): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();
        $id  = (int) $params['id'];

        $row = Database::fetch('SELECT id FROM finance_records WHERE id = ? AND user_id = ?', [$id, $uid]);
        if (!$row) abort(404);

        $type        = sanitize(input('type', ''));
        $amount      = (float) input('amount', 0);
        $category    = sanitize(input('category', ''));
        $description = sanitize(input('description', ''));
        $record_date = sanitize(input('record_date', date('Y-m-d')));

        if (!in_array($type, ['income', 'expense'], true) || $amount <= 0 || !$category) {
            flash('error', 'Please fill all required fields.');
            redirect('/finance');
        }

        Database::execute(
            "UPDATE finance_records SET type=?, amount=?, category=?, description=?, record_date=?, updated_at=datetime('now') WHERE id=? AND user_id=?",
            [$type, $amount, $category, $description, $record_date, $id, $uid]
        );

        flash('success', 'Record updated.');
        $y = (int) date('Y', strtotime($record_date));
        $m = (int) date('n', strtotime($record_date));
        redirect("/finance?year=$y&month=$m");
    }

    public static function analytics(): void
    {
        Auth::requireAuth();
        $uid   = userId();
        $year  = (int) input('year', date('Y'));
        $month = (int) input('month', date('n'));
        $monthStr = sprintf('%04d-%02d', $year, $month);

        $records = Database::fetchAll(
            "SELECT type, amount, category FROM finance_records
             WHERE user_id = ? AND strftime('%Y-%m', record_date) = ?",
            [$uid, $monthStr]
        );

        $income = 0.0; $expenses = 0.0; $byCategory = [];
        foreach ($records as $r) {
            $amt = (float) $r['amount'];
            if ($r['type'] === 'income') {
                $income += $amt;
            } else {
                $expenses += $amt;
                $byCategory[$r['category']] = ($byCategory[$r['category']] ?? 0) + $amt;
            }
        }
        arsort($byCategory);
        $savings     = $income - $expenses;
        $savingsRate = $income > 0 ? ($savings / $income) * 100 : 0;

        // Last 6 months bar chart data
        $barData = [];
        for ($i = 5; $i >= 0; $i--) {
            $ts  = mktime(0,0,0, $month - $i, 1, $year);
            $ms  = date('Y-m', $ts);
            $mn  = date('M y', $ts);
            $recs = Database::fetchAll(
                "SELECT type, amount FROM finance_records
                 WHERE user_id = ? AND strftime('%Y-%m', record_date) = ?",
                [$uid, $ms]
            );
            $inc = array_sum(array_column(array_filter($recs, fn($r)=>$r['type']==='income'), 'amount'));
            $exp = array_sum(array_column(array_filter($recs, fn($r)=>$r['type']==='expense'), 'amount'));
            $barData[] = ['month'=>$mn, 'income'=>$inc, 'expense'=>$exp, 'savings'=>max(0,$inc-$exp)];
        }

        $monthNames = ['','January','February','March','April','May','June',
                       'July','August','September','October','November','December'];
        $pageTitle = 'Finance Analytics';
        view('pages/finance-analytics', compact(
            'income','expenses','savings','savingsRate',
            'byCategory','barData','year','month','monthNames','pageTitle'
        ));
    }

    public static function delete(array $params): void
    {
        Auth::requireAuth();
        Auth::verifyCsrf();
        $uid = userId();
        $id  = (int) $params['id'];

        $row = Database::fetch('SELECT record_date FROM finance_records WHERE id = ? AND user_id = ?', [$id, $uid]);
        if (!$row) abort(404);

        Database::execute('DELETE FROM finance_records WHERE id = ? AND user_id = ?', [$id, $uid]);
        flash('success', 'Record deleted.');

        $y = (int) date('Y', strtotime($row['record_date']));
        $m = (int) date('n', strtotime($row['record_date']));
        redirect("/finance?year=$y&month=$m");
    }
}
