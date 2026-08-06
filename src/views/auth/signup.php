<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create Account — <?= APP_NAME ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<style>body { font-family: 'Plus Jakarta Sans', sans-serif; }</style>
<?php
$siteName = siteSetting('site_name', APP_NAME);
$siteTagline = siteSetting('site_tagline', 'Your secure digital vault');
$siteLogoUrl = siteSetting('logo_url', '');
?>
</head>
<body class="bg-[#F3F5F7] min-h-full flex items-center justify-center py-12 px-4">
<div class="w-full max-w-md">
  <div class="flex flex-col items-center mb-8">
    <?php if ($siteLogoUrl !== ''): ?>
    <img src="<?= e($siteLogoUrl) ?>" alt="<?= e($siteName) ?> logo" class="w-12 h-12 rounded-2xl object-cover mb-4 border border-slate-200 bg-white">
    <?php else: ?>
    <div class="w-12 h-12 bg-blue-600 rounded-2xl flex items-center justify-center mb-4 shadow-lg shadow-blue-200">
      <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
      </svg>
    </div>
    <?php endif; ?>
    <h1 class="text-2xl font-bold text-slate-900"><?= e($siteName) ?></h1>
    <p class="text-sm text-slate-500 mt-1"><?= e($siteTagline) ?></p>
  </div>

  <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-8">
    <?php $error = getFlash('error'); if ($error): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm mb-5"><?= e($error) ?></div>
    <?php endif; ?>

    <h2 class="text-xl font-bold text-slate-900 mb-1">Create your vault</h2>
    <p class="text-sm text-slate-500 mb-6">You'll set up Google Authenticator 2FA next</p>

    <form method="POST" action="/signup" x-data="{ loading: false, pwd: '', score: 0 }" @submit="loading = true">
      <?= csrf() ?>
      <div class="space-y-4">
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Full name</label>
          <input type="text" name="name" required value="<?= old('name') ?>"
            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="Ram Bahadur">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Email address</label>
          <input type="email" name="email" required value="<?= old('email') ?>"
            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="you@example.com">
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
          <div class="relative" x-data="{ show: false }">
            <input :type="show ? 'text' : 'password'" name="password" required x-model="pwd"
              @input="score = Math.min(5, [pwd.length>=12, /[A-Z]/.test(pwd), /[a-z]/.test(pwd), /[0-9]/.test(pwd), /[^a-zA-Z0-9]/.test(pwd)].filter(Boolean).length)"
              class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent pr-10"
              placeholder="Min. 8 characters">
            <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
              <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
              <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
            </button>
          </div>
          <!-- Strength bar -->
          <div x-show="pwd.length > 0" class="mt-2">
            <div class="flex gap-1 h-1.5">
              <template x-for="i in 5">
                <div :class="i <= score ? (score <= 1 ? 'bg-red-400' : score <= 2 ? 'bg-amber-400' : score <= 3 ? 'bg-yellow-400' : 'bg-green-500') : 'bg-slate-200'" class="flex-1 rounded-full transition-colors"></div>
              </template>
            </div>
            <p x-text="['', 'Very weak', 'Weak', 'Fair', 'Good', 'Strong'][score]" :class="score <= 1 ? 'text-red-500' : score <= 2 ? 'text-amber-500' : score <= 3 ? 'text-yellow-500' : 'text-green-600'" class="text-xs mt-1 font-medium"></p>
          </div>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
          <input type="password" name="password_confirm" required
            class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            placeholder="••••••••">
        </div>
        <button type="submit" :disabled="loading"
          class="w-full py-2.5 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg text-sm transition-colors disabled:opacity-60 flex items-center justify-center gap-2">
          <svg x-show="loading" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
          <span x-text="loading ? 'Creating vault...' : 'Create vault'">Create vault</span>
        </button>
      </div>
    </form>
    <p class="text-center text-sm text-slate-500 mt-6">
      Already have an account? <a href="/login" class="text-blue-600 font-semibold hover:underline">Sign in</a>
    </p>
  </div>
</div>
</body>
</html>
