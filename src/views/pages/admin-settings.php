<div class="max-w-4xl">
  <div class="card p-6 mb-6">
    <h2 class="text-xl font-bold text-slate-900">Superadmin Site Settings</h2>
    <p class="text-sm text-slate-500 mt-1">Manage branding, onboarding, and project-owner controls.</p>
  </div>

  <div class="card p-6 mb-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-lg font-semibold text-slate-900">User Signup Overview</h3>
      <span class="text-xs text-slate-500">Aggregate only, no private user data</span>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
      <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
        <p class="text-xs text-slate-500">Total users</p>
        <p class="text-2xl font-bold text-slate-900 mt-1"><?= (int) ($metrics['total_users'] ?? 0) ?></p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
        <p class="text-xs text-slate-500">Signups today</p>
        <p class="text-2xl font-bold text-slate-900 mt-1"><?= (int) ($metrics['today_signups'] ?? 0) ?></p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
        <p class="text-xs text-slate-500">Signups this week</p>
        <p class="text-2xl font-bold text-slate-900 mt-1"><?= (int) ($metrics['week_signups'] ?? 0) ?></p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
        <p class="text-xs text-slate-500">Signups this month</p>
        <p class="text-2xl font-bold text-slate-900 mt-1"><?= (int) ($metrics['month_signups'] ?? 0) ?></p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
        <p class="text-xs text-slate-500">2FA enabled users</p>
        <p class="text-2xl font-bold text-slate-900 mt-1"><?= (int) ($metrics['verified_2fa'] ?? 0) ?></p>
      </div>
      <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
        <p class="text-xs text-slate-500">Superadmins</p>
        <p class="text-2xl font-bold text-slate-900 mt-1"><?= (int) ($metrics['superadmins'] ?? 0) ?></p>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-5">
      <div class="lg:col-span-2 rounded-xl border border-slate-200 p-4">
        <div class="flex items-center justify-between mb-3">
          <p class="text-sm font-semibold text-slate-900">7-Day Signup Trend</p>
          <p class="text-xs text-slate-500">Recent growth only</p>
        </div>
        <?php
          $trend = is_array($metrics['daily_trend'] ?? null) ? $metrics['daily_trend'] : [];
          $trendMax = (int) ($metrics['daily_trend_max'] ?? 0);
        ?>
        <div class="grid grid-cols-7 gap-2 items-end h-28">
          <?php foreach ($trend as $point): ?>
          <?php
            $count = (int) ($point['count'] ?? 0);
            $height = $trendMax > 0 ? max(8, (int) round(($count / $trendMax) * 88)) : 8;
          ?>
          <div class="flex flex-col items-center justify-end gap-1">
            <span class="text-[10px] text-slate-500"><?= $count ?></span>
            <div class="w-full max-w-[26px] rounded-md bg-blue-500/85" style="height: <?= $height ?>px"></div>
            <span class="text-[10px] text-slate-400"><?= e((string) ($point['label'] ?? '')) ?></span>
          </div>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="rounded-xl border border-slate-200 p-4 bg-slate-50 space-y-2">
        <p class="text-sm font-semibold text-slate-900">System Health Snapshot</p>
        <p class="text-xs text-slate-600">2FA adoption: <span class="font-semibold text-slate-900"><?= (int) ($metrics['two_fa_rate'] ?? 0) ?>%</span></p>
        <p class="text-xs text-slate-600">Superadmin ratio: <span class="font-semibold text-slate-900"><?= (int) ($metrics['superadmin_rate'] ?? 0) ?>%</span></p>
        <p class="text-xs text-slate-600">Signup status:
          <?php if (!empty($metrics['signup_open'])): ?>
          <span class="font-semibold text-emerald-700">Open</span>
          <?php else: ?>
          <span class="font-semibold text-amber-700">Closed</span>
          <?php endif; ?>
        </p>
      </div>
    </div>
  </div>

  <form method="POST" action="/admin/settings" class="card p-6 space-y-6">
    <?= csrf() ?>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="form-label">Site Name</label>
        <input type="text" name="site_name" maxlength="80" required class="form-input"
          value="<?= e((string) ($settings['site_name'] ?? APP_NAME)) ?>"
          placeholder="Aakash Key Vault">
      </div>
      <div>
        <label class="form-label">Support Email</label>
        <input type="email" name="support_email" maxlength="120" class="form-input"
          value="<?= e((string) ($settings['support_email'] ?? '')) ?>"
          placeholder="support@example.com">
      </div>
    </div>

    <div>
      <label class="form-label">Tagline</label>
      <input type="text" name="site_tagline" maxlength="140" class="form-input"
        value="<?= e((string) ($settings['site_tagline'] ?? '')) ?>"
        placeholder="Your secure digital vault">
    </div>

    <div>
      <label class="form-label">Logo URL</label>
      <input type="url" name="logo_url" class="form-input"
        value="<?= e((string) ($settings['logo_url'] ?? '')) ?>"
        placeholder="https://cdn.example.com/logo.png">
      <p class="text-xs text-slate-500 mt-1">Use a square PNG/SVG URL for best results.</p>
      <?php if (!empty($settings['logo_url'])): ?>
      <div class="mt-3 inline-flex items-center gap-3 px-3 py-2 rounded-lg border border-slate-200 bg-slate-50">
        <img src="<?= e((string) $settings['logo_url']) ?>" alt="Current logo" class="w-10 h-10 rounded-md object-cover border border-slate-200">
        <span class="text-xs text-slate-600">Current logo preview</span>
      </div>
      <?php endif; ?>
    </div>

    <div>
      <label class="form-label">Maintenance Notice (optional)</label>
      <textarea name="maintenance_notice" rows="3" maxlength="300" class="form-input"
        placeholder="Planned maintenance tonight 11:00 PM - 11:15 PM."><?= e((string) ($settings['maintenance_notice'] ?? '')) ?></textarea>
      <p class="text-xs text-slate-500 mt-1">This shows globally at the top bar to all users.</p>
    </div>

    <div class="rounded-lg border border-slate-200 p-4 bg-slate-50">
      <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
        <input type="checkbox" name="allow_signup" value="1" <?= (($settings['allow_signup'] ?? '1') !== '0') ? 'checked' : '' ?>>
        Allow new user signup
      </label>
      <p class="text-xs text-slate-500 mt-1">Turn off to make the system invite-only while owner accounts continue to work.</p>

      <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700 mt-4">
        <input type="checkbox" name="allow_share" value="1" <?= (($settings['allow_share'] ?? '1') !== '0') ? 'checked' : '' ?>>
        Show topbar share button
      </label>
      <p class="text-xs text-slate-500 mt-1">If off, share action is hidden for all users.</p>
    </div>

    <div class="flex items-center justify-between pt-2">
      <a href="/dashboard" class="btn-secondary">Back to Dashboard</a>
      <button type="submit" class="btn-primary">Save Site Settings</button>
    </div>
  </form>
</div>
