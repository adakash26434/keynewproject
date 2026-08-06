<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Verify 2FA — <?= e(siteSetting('site_name', APP_NAME)) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-[#F3F5F7] min-h-full flex items-center justify-center py-12 px-4">
<div class="w-full max-w-sm">
  <div class="flex flex-col items-center mb-8">
    <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-blue-200">
      <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 1.5H8.25A2.25 2.25 0 006 3.75v16.5a2.25 2.25 0 002.25 2.25h7.5A2.25 2.25 0 0018 20.25V3.75a2.25 2.25 0 00-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 8.25h3m-3 3.75h3m-3 3.75h3"/>
      </svg>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Two-Factor Auth</h1>
    <p class="text-sm text-slate-500 mt-1">Open Google Authenticator to get your code</p>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
    <?php $error = getFlash('error'); if ($error): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm mb-5"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="/verify-2fa" x-data="{ loading: false, code: '' }" @submit="loading = true">
      <?= csrf() ?>
      <div class="space-y-5">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-3 text-center">Enter your 6-digit code</label>
          <input type="text" name="code" required inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
            x-model="code"
            class="w-full rounded-lg border border-slate-300 px-3 py-3 text-center text-2xl font-mono tracking-widest text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="000000" autofocus autocomplete="one-time-code">
        </div>
        <button type="submit" :disabled="loading || code.length < 6"
          class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-sm transition-colors disabled:opacity-60 disabled:cursor-not-allowed flex items-center justify-center gap-2">
          <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          <span x-text="loading ? 'Verifying...' : 'Verify'">Verify</span>
        </button>
      </div>
    </form>

    <p class="text-center text-xs text-slate-400 mt-6">
      Code refreshes every 30 seconds
    </p>
    <div class="text-center text-sm text-slate-500 mt-3">
      <form method="POST" action="/logout" class="inline">
        <?= csrf() ?>
        <button type="submit" class="text-slate-600 hover:underline">Sign in with a different account</button>
      </form>
    </div>
  </div>
</div>
</body>
</html>
