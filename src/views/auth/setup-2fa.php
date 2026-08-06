<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Setup 2FA — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
<style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
</head>
<body class="bg-[#F3F5F7] min-h-full flex items-center justify-center py-12 px-4">
<div class="w-full max-w-md">
  <div class="flex flex-col items-center mb-8">
    <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-blue-200">
      <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
      </svg>
    </div>
    <h1 class="text-2xl font-bold text-slate-900">Set Up Two-Factor Auth</h1>
    <p class="text-sm text-slate-500 mt-1">Required for your account security</p>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
    <?php $error = getFlash('error'); if ($error): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm mb-5"><?= e($error) ?></div>
    <?php endif; ?>

    <div class="space-y-5">
      <!-- Step 1 -->
      <div class="flex gap-3">
        <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">1</div>
        <div>
          <p class="text-sm font-semibold text-slate-900">Install Google Authenticator</p>
          <p class="text-xs text-slate-500 mt-0.5">Download from the App Store or Google Play</p>
        </div>
      </div>

      <!-- Step 2: QR Code -->
      <div class="flex gap-3">
        <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">2</div>
        <div class="flex-1">
          <p class="text-sm font-semibold text-slate-900 mb-3">Scan this QR code</p>
          <div class="flex justify-center">
            <div class="bg-white p-3 rounded-xl border-2 border-slate-200">
              <canvas id="qrcode" class="w-40 h-40"></canvas>
            </div>
          </div>
          <details class="mt-3">
            <summary class="text-xs text-slate-500 cursor-pointer hover:text-slate-700">Can't scan? Enter manually</summary>
            <div class="mt-2 bg-slate-50 rounded-lg p-3">
              <p class="text-xs text-slate-500 mb-1">Secret key (TOTP):</p>
              <code class="text-xs font-mono text-slate-800 break-all select-all"><?= e($secret) ?></code>
            </div>
          </details>
        </div>
      </div>

      <!-- Step 3: Verify -->
      <div class="flex gap-3">
        <div class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold flex-shrink-0 mt-0.5">3</div>
        <div class="flex-1">
          <p class="text-sm font-semibold text-slate-900 mb-3">Enter the 6-digit code</p>
          <form method="POST" action="/setup-2fa" x-data="{ loading: false }" @submit="loading = true">
            <?= csrf() ?>
            <input type="text" name="code" required inputmode="numeric" maxlength="6" pattern="[0-9]{6}"
              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-center text-slate-900 tracking-widest text-lg font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              placeholder="000000" autofocus>
            <button type="submit" :disabled="loading"
              class="w-full mt-3 py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-sm transition-colors disabled:opacity-60 flex items-center justify-center gap-2">
              <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
              <span x-text="loading ? 'Verifying...' : 'Verify & Enable 2FA'">Verify & Enable 2FA</span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
QRCode.toCanvas(document.getElementById('qrcode'), <?= json_encode($qrUri) ?>, {
  width: 160, margin: 1,
  color: { dark: '#0f172a', light: '#ffffff' }
}, function(err) { if (err) console.error(err); });
</script>
</body>
</html>
