<?php
class AdminController
{
    public static function siteSettings(): void
    {
        Auth::requireSuperAdmin();
        $settings = siteSettings();
        $metrics = self::userMetrics();
        $pageTitle = 'Admin Settings';
        view('pages/admin-settings', compact('settings', 'metrics', 'pageTitle'));
    }

    public static function updateSiteSettings(): void
    {
        Auth::requireSuperAdmin();
        Auth::verifyCsrf();

        $siteName = trim((string) input('site_name', ''));
        $siteTagline = trim((string) input('site_tagline', ''));
        $logoUrl = trim((string) input('logo_url', ''));
        $supportEmail = strtolower(trim((string) input('support_email', '')));
        $allowSignup = input('allow_signup', '0') === '1' ? '1' : '0';
        $allowShare = input('allow_share', '0') === '1' ? '1' : '0';
        $maintenanceNotice = trim((string) input('maintenance_notice', ''));

        if ($siteName === '') {
            flash('error', 'Site name is required.');
            redirect('/admin/settings');
        }

        if (strlen($siteName) > 80 || strlen($siteTagline) > 140 || strlen($maintenanceNotice) > 300) {
            flash('error', 'One or more fields exceed allowed length.');
            redirect('/admin/settings');
        }

        if ($logoUrl !== '' && !filter_var($logoUrl, FILTER_VALIDATE_URL)) {
            flash('error', 'Logo URL must be a valid URL.');
            redirect('/admin/settings');
        }

        if ($supportEmail !== '' && !filter_var($supportEmail, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Support email is invalid.');
            redirect('/admin/settings');
        }

        setSiteSetting('site_name', $siteName);
        setSiteSetting('site_tagline', $siteTagline);
        setSiteSetting('logo_url', $logoUrl);
        setSiteSetting('support_email', $supportEmail);
        setSiteSetting('allow_signup', $allowSignup);
        setSiteSetting('allow_share', $allowShare);
        setSiteSetting('maintenance_notice', $maintenanceNotice);

        flash('success', 'Site settings updated successfully.');
        redirect('/admin/settings');
    }

    private static function userMetrics(): array
    {
        $todayStart = (new DateTimeImmutable('today'))->format('Y-m-d H:i:s');
        $weekStart = (new DateTimeImmutable('monday this week'))->setTime(0, 0, 0)->format('Y-m-d H:i:s');
        $monthStart = (new DateTimeImmutable('first day of this month'))->setTime(0, 0, 0)->format('Y-m-d H:i:s');

        $totalUsers = (int) (Database::fetch('SELECT COUNT(*) AS c FROM users')['c'] ?? 0);
        $todaySignups = (int) (Database::fetch('SELECT COUNT(*) AS c FROM users WHERE created_at >= ?', [$todayStart])['c'] ?? 0);
        $weekSignups = (int) (Database::fetch('SELECT COUNT(*) AS c FROM users WHERE created_at >= ?', [$weekStart])['c'] ?? 0);
        $monthSignups = (int) (Database::fetch('SELECT COUNT(*) AS c FROM users WHERE created_at >= ?', [$monthStart])['c'] ?? 0);
        $verified2fa = (int) (Database::fetch('SELECT COUNT(*) AS c FROM users WHERE totp_verified = 1')['c'] ?? 0);
        $superAdmins = (int) (Database::fetch('SELECT COUNT(*) AS c FROM users WHERE is_superadmin = 1')['c'] ?? 0);

        $dailyTrend = [];
        for ($i = 6; $i >= 0; $i--) {
            $start = (new DateTimeImmutable('today'))->modify('-' . $i . ' days')->setTime(0, 0, 0);
            $end = $start->modify('+1 day');
            $count = (int) (Database::fetch(
                'SELECT COUNT(*) AS c FROM users WHERE created_at >= ? AND created_at < ?',
                [$start->format('Y-m-d H:i:s'), $end->format('Y-m-d H:i:s')]
            )['c'] ?? 0);

            $dailyTrend[] = [
                'label' => $start->format('D'),
                'date' => $start->format('Y-m-d'),
                'count' => $count,
            ];
        }

        $trendCounts = array_map(static fn (array $row): int => (int) ($row['count'] ?? 0), $dailyTrend);
        $maxDaily = !empty($trendCounts) ? max($trendCounts) : 0;
        $twoFaRate = $totalUsers > 0 ? (int) round(($verified2fa / $totalUsers) * 100) : 0;
        $superAdminRate = $totalUsers > 0 ? (int) round(($superAdmins / $totalUsers) * 100) : 0;

        return [
            'total_users' => $totalUsers,
            'today_signups' => $todaySignups,
            'week_signups' => $weekSignups,
            'month_signups' => $monthSignups,
            'verified_2fa' => $verified2fa,
            'superadmins' => $superAdmins,
            'two_fa_rate' => $twoFaRate,
            'superadmin_rate' => $superAdminRate,
            'signup_open' => isSignupAllowed(),
            'daily_trend' => $dailyTrend,
            'daily_trend_max' => $maxDaily,
        ];
    }
}
