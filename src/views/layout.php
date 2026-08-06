<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="theme-color" content="#0f172a">
<?php
  $siteName = siteSetting('site_name', APP_NAME);
  $siteTagline = siteSetting('site_tagline', 'Your secure digital vault');
  $siteLogoUrl = siteSetting('logo_url', '');
  $maintenanceNotice = siteSetting('maintenance_notice', '');
  $allowShare = isShareEnabled();
?>
<title><?= e($pageTitle ?? 'Dashboard') ?> — <?= e($siteName) ?></title>
<link rel="manifest" href="/manifest.webmanifest">
<link rel="stylesheet" href="/assets/global-theme.css?v=2">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.tailwindcss.com"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
<script defer src="https://unpkg.com/lucide@0.468.0/dist/umd/lucide.min.js"></script>
<script>
tailwind.config = {
  theme: {
    extend: {
      fontFamily: { sans: ['Plus Jakarta Sans', 'sans-serif'] },
      colors: {
        primary: { DEFAULT: '#0078D4', 50: '#EFF6FF', 100: '#DBEAFE', 500: '#0078D4', 600: '#0069BA', 700: '#005A9E' },
      }
    }
  }
}
</script>
</head>
<body class="theme-app bg-slate-100 h-full"
  x-data="{
    sidebarOpen: false,
    isMobile: window.matchMedia('(max-width: 1023px)').matches,
    searchOpen: false,
    searchQuery: '',
    searchResults: [],
    searchError: '',
    searchLoading: false,
    pwaPromptEvent: null,
    showInstallPrompt: false,
    shareMessage: '',
    init() {
      this.sidebarOpen = !this.isMobile;
      window.addEventListener('resize', () => {
        this.isMobile = window.matchMedia('(max-width: 1023px)').matches;
        if (!this.isMobile) this.sidebarOpen = true;
      });

      window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        this.pwaPromptEvent = e;
        this.showInstallPrompt = true;
      });

      window.addEventListener('appinstalled', () => {
        this.showInstallPrompt = false;
        this.pwaPromptEvent = null;
      });

      if ('serviceWorker' in navigator) {
        window.addEventListener('load', () => {
          navigator.serviceWorker.register('/sw.js').catch(() => null);
        });
      }

      window.addEventListener('load', () => {
        if (window.lucide && typeof window.lucide.createIcons === 'function') {
          window.lucide.createIcons();
        }
      });
    },
    async installPwa() {
      if (!this.pwaPromptEvent) return;
      this.pwaPromptEvent.prompt();
      const choice = await this.pwaPromptEvent.userChoice;
      if (choice && choice.outcome) {
        this.showInstallPrompt = false;
      }
      this.pwaPromptEvent = null;
    },
    async shareCurrentPage() {
      const payload = {
        title: document.title,
        text: 'Check this page on ' + <?= json_encode($siteName) ?>,
        url: window.location.href,
      };

      try {
        if (navigator.share) {
          await navigator.share(payload);
          this.shareMessage = 'Shared successfully';
        } else if (navigator.clipboard && navigator.clipboard.writeText) {
          await navigator.clipboard.writeText(window.location.href);
          this.shareMessage = 'Link copied to clipboard';
        } else {
          this.shareMessage = 'Sharing is not supported on this browser';
        }
      } catch (e) {
        this.shareMessage = 'Share cancelled';
      }

      setTimeout(() => { this.shareMessage = ''; }, 2200);
    },
    async doSearch() {
      if (this.searchQuery.length < 2) {
        this.searchResults = [];
        this.searchError = '';
        return;
      }
      this.searchLoading = true;
      this.searchError = '';
      try {
        const r = await fetch('/search?q=' + encodeURIComponent(this.searchQuery));
        if (!r.ok) {
          this.searchResults = [];
          this.searchError = r.status === 429 ? 'Too many requests. Please wait a moment.' : 'Search is temporarily unavailable.';
          return;
        }
        const d = await r.json();
        this.searchResults = d.results || [];
      } catch (e) {
        this.searchResults = [];
        this.searchError = 'Search is temporarily unavailable.';
      } finally { this.searchLoading = false; }
    }
  }"
  @keydown.window.prevent.ctrl.k="searchOpen=true"
  @keydown.window.prevent.meta.k="searchOpen=true"
>

<!-- Global Search Modal -->
<div x-show="searchOpen" x-cloak class="fixed inset-0 z-50 flex items-start justify-center pt-20 px-4"
  @keydown.escape.window="searchOpen=false; searchQuery=''; searchResults=[]">
  <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="searchOpen=false; searchQuery=''; searchResults=[]"></div>
  <div class="relative w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden border border-slate-200 z-10">
    <div class="flex items-center gap-3 px-4 py-3 border-b border-slate-100">
      <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
      <input type="text" x-model="searchQuery" @input.debounce.300ms="doSearch()"
        placeholder="Search passwords, documents, tasks…" autofocus
        class="flex-1 text-sm text-slate-900 placeholder-slate-400 outline-none bg-transparent">
      <kbd class="text-[10px] font-medium text-slate-400 bg-slate-100 rounded px-1.5 py-0.5">ESC</kbd>
    </div>
    <!-- Results -->
    <div x-show="searchResults.length > 0" class="py-2 max-h-72 overflow-y-auto">
      <template x-for="r in searchResults" :key="r.type+r.id">
        <a :href="r.url" @click="searchOpen=false; searchQuery=''; searchResults=[]"
          class="flex items-center gap-3 px-4 py-2.5 hover:bg-slate-50 transition-colors cursor-pointer">
          <div class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0"
            :class="r.type==='password'?'bg-blue-100':r.type==='document'?'bg-purple-100':'bg-green-100'">
            <svg class="w-4 h-4" :class="r.type==='password'?'text-blue-600':r.type==='document'?'text-purple-600':'text-green-600'" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <template x-if="r.icon==='key'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/></template>
              <template x-if="r.icon==='file'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></template>
              <template x-if="r.icon==='check'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></template>
            </svg>
          </div>
          <div class="min-w-0">
            <p class="text-sm font-medium text-slate-900 truncate" x-text="r.title"></p>
            <p class="text-xs text-slate-500 truncate" x-text="r.subtitle"></p>
          </div>
          <span class="ml-auto text-[10px] font-semibold uppercase tracking-wide px-2 py-0.5 rounded-full"
            :class="r.type==='password'?'bg-blue-100 text-blue-700':r.type==='document'?'bg-purple-100 text-purple-700':'bg-green-100 text-green-700'"
            x-text="r.type"></span>
        </a>
      </template>
    </div>
    <div x-show="searchQuery.length >= 2 && searchResults.length === 0 && !searchLoading" class="px-4 py-6 text-center text-sm text-slate-400">
      No results for "<span x-text="searchQuery"></span>"
    </div>
    <div x-show="searchLoading" class="px-4 py-4 text-center">
      <svg class="w-5 h-5 animate-spin text-slate-400 mx-auto" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
    </div>
    <div x-show="searchQuery.length < 2" class="px-4 py-3 flex flex-wrap gap-2">
      <a href="/passwords" @click="searchOpen=false" class="text-xs text-slate-500 hover:text-blue-600 flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-slate-50">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Passwords
      </a>
      <a href="/documents" @click="searchOpen=false" class="text-xs text-slate-500 hover:text-blue-600 flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-slate-50">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Documents
      </a>
      <a href="/tasks" @click="searchOpen=false" class="text-xs text-slate-500 hover:text-blue-600 flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-slate-50">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        Tasks
      </a>
      <a href="/insights" @click="searchOpen=false" class="text-xs text-slate-500 hover:text-blue-600 flex items-center gap-1 px-2 py-1 rounded-lg hover:bg-slate-50">
        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        Insights
      </a>
    </div>
  </div>
</div>

<div class="flex h-screen overflow-hidden">
  <div x-show="isMobile && sidebarOpen" x-cloak class="fixed inset-0 z-30 bg-slate-900/40" @click="sidebarOpen=false"></div>

  <!-- Sidebar -->
  <aside
    class="fixed inset-y-0 left-0 z-40 w-60 bg-white border-r border-slate-200 flex flex-col transform transition-transform duration-200 ease-out lg:static lg:translate-x-0 lg:z-auto"
    :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
  >
    <!-- Logo -->
    <div class="h-16 flex items-center px-5 border-b border-slate-100">
      <div class="flex items-center gap-2.5">
        <?php if ($siteLogoUrl !== ''): ?>
        <img src="<?= e($siteLogoUrl) ?>" alt="<?= e($siteName) ?> logo" class="w-8 h-8 rounded-lg object-cover border border-slate-200 bg-white">
        <?php else: ?>
        <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
          <svg class="w-4.5 h-4.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z"/>
          </svg>
        </div>
        <?php endif; ?>
        <div class="min-w-0">
          <span class="font-bold text-slate-900 text-sm block truncate"><?= e($siteName) ?></span>
          <?php if ($siteTagline !== ''): ?>
          <span class="text-[10px] text-slate-400 block truncate leading-tight"><?= e($siteTagline) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 px-3 py-4 overflow-y-auto space-y-0.5">
      <p class="sidebar-section">Overview</p>
      <a href="/dashboard" class="sidebar-link <?= (rtrim($_SERVER['REQUEST_URI'],'/')==='/dashboard'?'active':'') ?>">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
        Dashboard
      </a>
      <a href="/insights" class="sidebar-link <?= (str_starts_with($_SERVER['REQUEST_URI'],'/insights')?'active':'') ?>">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
        Insights
      </a>

      <p class="sidebar-section">Vault</p>
      <a href="/passwords" class="sidebar-link <?= (str_starts_with($_SERVER['REQUEST_URI'],'/password')?'active':'') ?>">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
        Passwords
      </a>
      <a href="/documents" class="sidebar-link <?= (str_starts_with($_SERVER['REQUEST_URI'],'/document')?'active':'') ?>">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
        Documents
      </a>

      <p class="sidebar-section">Finance</p>
      <a href="/finance" class="sidebar-link <?= ($_SERVER['REQUEST_URI']==='/finance'||str_starts_with($_SERVER['REQUEST_URI'],'/finance?')?'active':'') ?>">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        Finance
      </a>
      <a href="/finance/analytics" class="sidebar-link <?= (str_starts_with($_SERVER['REQUEST_URI'],'/finance/analytics')?'active':'') ?>">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
        Analytics
      </a>

      <p class="sidebar-section">Productivity</p>
      <a href="/tasks" class="sidebar-link <?= (str_starts_with($_SERVER['REQUEST_URI'],'/tasks')?'active':'') ?>">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        Tasks
      </a>
      <a href="/cv" class="sidebar-link <?= (str_starts_with($_SERVER['REQUEST_URI'],'/cv')?'active':'') ?>">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        CV Builder
      </a>

      <?php if (Auth::isSuperAdmin()): ?>
      <p class="sidebar-section">Administration</p>
      <a href="/admin/settings" class="sidebar-link <?= (str_starts_with($_SERVER['REQUEST_URI'],'/admin/settings')?'active':'') ?>">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        Site Settings
      </a>
      <?php endif; ?>
    </nav>

    <!-- Bottom: Profile & Logout -->
    <div class="px-3 py-3 border-t border-slate-100">
      <a href="/profile" class="sidebar-link <?= (str_starts_with($_SERVER['REQUEST_URI'],'/profile')?'active':'') ?>">
        <div class="w-7 h-7 rounded-full bg-blue-100 flex items-center justify-center text-blue-700 font-bold text-xs flex-shrink-0">
          <?= strtoupper(substr(Auth::user()['name'] ?? 'U', 0, 1)) ?>
        </div>
        <span class="truncate text-xs"><?= e(Auth::user()['name'] ?? '') ?></span>
      </a>
      <form action="/logout" method="POST" class="mt-0.5">
        <?= csrf() ?>
        <button type="submit" class="sidebar-link text-red-500 hover:text-red-700 hover:bg-red-50 w-full text-left">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
          Sign Out
        </button>
      </form>
    </div>
  </aside>

  <!-- Main area -->
  <div class="flex-1 flex flex-col overflow-hidden lg:ml-0">
    <!-- Top bar -->
    <header class="h-16 bg-white border-b border-slate-200 flex items-center px-6 gap-4 flex-shrink-0">
      <button @click="sidebarOpen = !sidebarOpen" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-500" aria-label="Toggle navigation">
        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
      </button>
      <h1 class="text-base font-bold text-slate-900"><?= e($pageTitle ?? '') ?></h1>
      <div class="ml-auto flex items-center gap-2">
        <?php if ($maintenanceNotice !== ''): ?>
        <span class="hidden xl:inline-flex items-center px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 text-xs font-medium border border-amber-200">
          <?= e($maintenanceNotice) ?>
        </span>
        <?php endif; ?>
        <?php if ($allowShare): ?>
        <button @click="shareCurrentPage()"
          class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
          <i data-lucide="share-2" class="icon-16"></i>
          Share
        </button>
        <?php endif; ?>
        <button x-show="showInstallPrompt" x-cloak @click="installPwa()"
          class="hidden sm:inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 hover:bg-emerald-100 rounded-lg transition-colors">
          Install App
        </button>
        <!-- Search button -->
        <button @click="searchOpen=true"
          class="flex items-center gap-2 px-3 py-1.5 text-sm text-slate-400 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
          <span class="hidden sm:inline text-xs">Search</span>
          <kbd class="hidden sm:inline text-[10px] font-medium bg-white rounded px-1 border border-slate-200">⌘K</kbd>
        </button>
        <span class="text-xs text-slate-400 hidden md:block"><?= date('D, M j Y') ?></span>
      </div>
    </header>

    <!-- Quick actions -->
    <div class="px-6 pt-4 pb-1">
      <div class="flex flex-wrap gap-2">
        <a href="/passwords" class="btn-secondary text-xs px-3 py-1.5">+ Password</a>
        <a href="/documents" class="btn-secondary text-xs px-3 py-1.5">+ Document</a>
        <a href="/tasks" class="btn-secondary text-xs px-3 py-1.5">+ Task</a>
        <a href="/finance" class="btn-secondary text-xs px-3 py-1.5">+ Finance Entry</a>
      </div>
    </div>

    <!-- Flash messages -->
    <?php $success = getFlash('success'); $error = getFlash('error'); ?>
    <?php if ($success || $error): ?>
    <div class="px-6 pt-4">
      <?php if ($success): ?>
      <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-lg px-4 py-3 text-sm">
        <svg class="w-4 h-4 text-green-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
        <?= e($success) ?>
        <button @click="show=false" class="ml-auto text-green-600 hover:text-green-800 font-bold">&times;</button>
      </div>
      <?php endif; ?>
      <?php if ($error): ?>
      <div x-data="{ show: true }" x-show="show" x-transition class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-lg px-4 py-3 text-sm">
        <svg class="w-4 h-4 text-red-600 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-7V7a1 1 0 112 0v4a1 1 0 11-2 0zm0 4a1 1 0 112 0 1 1 0 01-2 0z" clip-rule="evenodd"/></svg>
        <?= e($error) ?>
        <button @click="show=false" class="ml-auto text-red-600 hover:text-red-800 font-bold">&times;</button>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div x-show="searchError && searchOpen" x-cloak class="fixed top-4 right-4 z-[60] bg-amber-50 border border-amber-200 text-amber-800 px-3 py-2 rounded-lg text-xs shadow">
      <span x-text="searchError"></span>
    </div>

    <div x-show="shareMessage" x-cloak x-transition class="fixed top-4 right-4 z-[60] bg-slate-900 text-white px-3 py-2 rounded-lg text-xs shadow">
      <span x-text="shareMessage"></span>
    </div>

    <!-- Page content -->
    <main class="flex-1 overflow-y-auto p-6">
      <?= $pageContent ?>
    </main>
  </div>
</div>
</body>
</html>
