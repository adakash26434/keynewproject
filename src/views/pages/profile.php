<div class="max-w-2xl space-y-6">
  <!-- Profile Info -->
  <div class="card p-6">
    <h3 class="font-semibold text-slate-900 mb-5 flex items-center gap-2">
      <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
      Profile Information
    </h3>
    <?php $success = getFlash('success'); $error = getFlash('error'); ?>
    <?php if ($success): ?><div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm mb-4"><?= e($success) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm mb-4"><?= e($error) ?></div><?php endif; ?>

    <!-- Avatar -->
    <div class="flex items-center gap-4 mb-6">
      <div class="w-16 h-16 rounded-2xl bg-blue-600 flex items-center justify-center text-white text-2xl font-bold">
        <?= strtoupper(substr($user['name'], 0, 1)) ?>
      </div>
      <div>
        <p class="font-semibold text-slate-900 text-lg"><?= e($user['name']) ?></p>
        <p class="text-sm text-slate-500"><?= e($user['email']) ?></p>
        <span class="badge bg-green-100 text-green-700 mt-1">
          <svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
          2FA Active
        </span>
      </div>
    </div>

    <form method="POST" action="/profile" x-data="{ loading: false }" @submit="loading=true">
      <?= csrf() ?>
      <div class="space-y-4">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2 sm:col-span-1">
            <label class="form-label">Full Name *</label>
            <input type="text" name="name" required value="<?= e($user['name']) ?>" class="form-input">
          </div>
          <div class="col-span-2 sm:col-span-1">
            <label class="form-label">Phone</label>
            <input type="tel" name="phone" value="<?= e($user['phone'] ?? '') ?>" class="form-input" placeholder="+977 98XXXXXXXX">
          </div>
          <div class="col-span-2">
            <label class="form-label">Location</label>
            <input type="text" name="location" value="<?= e($user['location'] ?? '') ?>" class="form-input" placeholder="e.g. Kathmandu, Nepal">
          </div>
          <div class="col-span-2">
            <label class="form-label">Bio</label>
            <textarea name="bio" rows="3" class="form-input" placeholder="A short bio about yourself..."><?= e($user['bio'] ?? '') ?></textarea>
          </div>
        </div>
        <div class="flex justify-end pt-2">
          <button type="submit" :disabled="loading" class="btn-primary">
            <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
            <span x-text="loading ? 'Saving...' : 'Save Changes'">Save Changes</span>
          </button>
        </div>
      </div>
    </form>
  </div>

  <!-- Change Password -->
  <div class="card p-6" x-data="{ open: false }">
    <button @click="open = !open" class="w-full flex items-center justify-between text-left">
      <h3 class="font-semibold text-slate-900 flex items-center gap-2">
        <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Change Password
      </h3>
      <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
    </button>

    <div x-show="open" x-collapse class="mt-5">
      <form method="POST" action="/profile/password" x-data="{ loading: false }" @submit="loading=true">
        <?= csrf() ?>
        <div class="space-y-4">
          <div>
            <label class="form-label">Current Password</label>
            <input type="password" name="current_password" required class="form-input" placeholder="Your current password">
          </div>
          <div>
            <label class="form-label">New Password</label>
            <input type="password" name="new_password" required class="form-input" placeholder="Min. 8 characters">
          </div>
          <div>
            <label class="form-label">Confirm New Password</label>
            <input type="password" name="confirm_password" required class="form-input" placeholder="Repeat new password">
          </div>
          <div class="flex justify-end">
            <button type="submit" :disabled="loading" class="btn-primary">
              <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              <span x-text="loading ? 'Updating...' : 'Update Password'">Update Password</span>
            </button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Security Info -->
  <div class="card p-6">
    <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
      <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/></svg>
      Security
    </h3>
    <div class="space-y-3">
      <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-green-100 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-slate-900">Google Authenticator 2FA</p>
            <p class="text-xs text-slate-500">Enabled and active</p>
          </div>
        </div>
        <span class="badge bg-green-100 text-green-700">Active</span>
      </div>
      <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-slate-900">AES-256-GCM Encryption</p>
            <p class="text-xs text-slate-500">All sensitive data encrypted at rest</p>
          </div>
        </div>
        <span class="badge bg-blue-100 text-blue-700">Enabled</span>
      </div>
      <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl">
        <div class="flex items-center gap-3">
          <div class="w-8 h-8 bg-slate-100 rounded-lg flex items-center justify-center">
            <svg class="w-4 h-4 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
          </div>
          <div>
            <p class="text-sm font-medium text-slate-900">Member since</p>
            <p class="text-xs text-slate-500"><?= formatDate($user['created_at']) ?></p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Recent Sessions -->
  <div class="card p-6">
    <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
      <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
      Recent Sessions
    </h3>

    <?php if (empty($sessions)): ?>
      <p class="text-sm text-slate-500">No recent session data available yet.</p>
    <?php else: ?>
      <div class="space-y-2">
        <?php foreach ($sessions as $s): ?>
          <?php
            $isCurrent = ((int) ($s['id'] ?? 0) === (int) ($currentSessionId ?? 0));
            $ua = trim((string) ($s['user_agent'] ?? 'Unknown device'));
            $ip = trim((string) ($s['ip_address'] ?? 'Unknown IP'));
            $lastActiveRaw = (string) ($s['last_active'] ?? '');
            $lastActiveText = $lastActiveRaw;
            try {
              $lastActiveText = (new DateTime($lastActiveRaw))->format('M d, Y h:i A');
            } catch (Exception) {
            }
          ?>
          <div class="p-3 bg-slate-50 rounded-xl border border-slate-100">
            <div class="flex items-center justify-between gap-2">
              <p class="text-sm font-medium text-slate-900 truncate"><?= e($ua) ?></p>
              <?php if ($isCurrent): ?>
                <span class="badge bg-green-100 text-green-700">Current</span>
              <?php endif; ?>
            </div>
            <p class="text-xs text-slate-500 mt-1">IP: <?= e($ip) ?></p>
            <p class="text-xs text-slate-500">Last active: <?= e($lastActiveText) ?></p>
            <?php if (!$isCurrent): ?>
              <form method="POST" action="/profile/sessions/<?= (int) $s['id'] ?>/revoke" class="mt-2">
                <?= csrf() ?>
                <button type="submit" class="text-xs font-medium text-red-600 hover:text-red-700">Sign out this device</button>
              </form>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Backup & Restore -->
  <div class="card p-6">
    <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
      <svg class="w-4 h-4 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7a2 2 0 012-2h3.28a1 1 0 00.948-.684l.498-1.438A1 1 0 0110.674 2h2.652a1 1 0 01.948.684l.498 1.438A1 1 0 0015.72 5H19a2 2 0 012 2v3m-1 4v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-3m-10 0H4m8 0v6m0-6l-2.5 2.5M12 14l2.5 2.5"/></svg>
      Backup & Restore
    </h3>
    <p class="text-sm text-slate-500 mb-4">Export an encrypted backup of your vault data or restore from a previously exported backup file.</p>

    <div class="grid sm:grid-cols-2 gap-3">
      <form method="POST" action="/profile/backup/export" class="p-3 bg-slate-50 rounded-xl border border-slate-100">
        <?= csrf() ?>
        <p class="text-sm font-medium text-slate-900 mb-1">Export Backup</p>
        <p class="text-xs text-slate-500 mb-3">Downloads encrypted `.kwb` file of your current data.</p>
        <button type="submit" class="btn-secondary w-full justify-center">Download Backup</button>
      </form>

      <form method="POST" action="/profile/backup/import" enctype="multipart/form-data" class="p-3 bg-amber-50 rounded-xl border border-amber-100" onsubmit="return confirm('Restore will replace your current vault data. Continue?');">
        <?= csrf() ?>
        <p class="text-sm font-medium text-slate-900 mb-1">Restore Backup</p>
        <p class="text-xs text-slate-500 mb-3">Import previously exported `.kwb` file. Existing data will be replaced.</p>
        <input type="file" name="backup_file" accept=".kwb,.txt,application/octet-stream" required class="block w-full text-xs text-slate-700 mb-3">
        <button type="submit" class="btn-danger w-full justify-center">Restore Now</button>
      </form>
    </div>
  </div>

  <!-- Danger Zone -->
  <div class="card p-6 border-red-200">
    <h3 class="font-semibold text-red-700 mb-3 flex items-center gap-2">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      Danger Zone
    </h3>
    <p class="text-sm text-slate-600 mb-4">This will sign out every active session, including this device.</p>
    <form method="POST" action="/logout-all">
      <?= csrf() ?>
      <button type="submit" class="btn-danger">Sign Out of All Sessions</button>
    </form>
  </div>
</div>
