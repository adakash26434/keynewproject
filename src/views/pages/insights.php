<?php
$scoreColor = $score >= 80 ? '#22c55e' : ($score >= 50 ? '#f59e0b' : '#ef4444');
$scoreLabel = $score >= 80 ? 'Excellent' : ($score >= 50 ? 'Fair' : 'Needs Attention');
$circumference = 2 * M_PI * 56;
$filled = ($score / 100) * $circumference;
?>
<div class="space-y-6">

  <!-- Header -->
  <div>
    <h2 class="text-xl font-bold text-slate-900">Security & Insights</h2>
    <p class="text-sm text-slate-500 mt-0.5">Your vault health, security posture, and financial overview.</p>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

    <!-- Security Score -->
    <div class="card p-6 flex flex-col items-center justify-center">
      <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-4">Security Score</p>
      <div class="relative inline-flex items-center justify-center mb-3">
        <svg width="148" height="148" style="transform:rotate(-90deg)">
          <circle cx="74" cy="74" r="56" fill="none" stroke="#e5e7eb" stroke-width="12"/>
          <circle cx="74" cy="74" r="56" fill="none"
            stroke="<?= $scoreColor ?>" stroke-width="12"
            stroke-dasharray="<?= round($filled,2) ?> <?= round($circumference,2) ?>"
            stroke-linecap="round"/>
        </svg>
        <div class="absolute flex flex-col items-center">
          <span class="text-4xl font-black" style="color:<?= $scoreColor ?>"><?= $score ?></span>
          <span class="text-[10px] text-slate-400 font-semibold uppercase tracking-widest">/ 100</span>
        </div>
      </div>
      <p class="font-bold text-sm" style="color:<?= $scoreColor ?>"><?= $scoreLabel ?></p>
    </div>

    <!-- Metric Bars -->
    <div class="card p-6 space-y-4 lg:col-span-2">
      <p class="text-xs font-bold uppercase tracking-widest text-slate-400 mb-2">Breakdown</p>

      <?php
      $metrics = [
        ['label'=>'Strong Passwords', 'value'=>$strongPw, 'max'=>max(1,$totalPw), 'color'=>'bg-green-500'],
        ['label'=>'Weak Passwords',   'value'=>$weakPw,   'max'=>max(1,$totalPw), 'color'=>'bg-red-500'],
        ['label'=>'Valid Documents',  'value'=>$totalDocs - $expiringDocs, 'max'=>max(1,$totalDocs), 'color'=>'bg-blue-500'],
        ['label'=>'Tasks Completed',  'value'=>$doneTasks, 'max'=>max(1,$totalTasks), 'color'=>'bg-purple-500'],
      ];
      foreach ($metrics as $m):
        $pct = $m['max'] > 0 ? round(($m['value']/$m['max'])*100) : 0;
      ?>
      <div>
        <div class="flex justify-between mb-1">
          <span class="text-xs text-slate-500"><?= e($m['label']) ?></span>
          <span class="text-xs font-bold text-slate-900"><?= $m['value'] ?><span class="text-slate-400 font-normal">/<?= $m['max'] ?></span></span>
        </div>
        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
          <div class="h-full rounded-full <?= $m['color'] ?>" style="width:<?= $pct ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="grid grid-cols-3 gap-3 pt-2">
        <div class="text-center p-3 bg-slate-50 rounded-xl">
          <p class="text-lg font-black text-slate-900"><?= $totalPw ?></p>
          <p class="text-[11px] text-slate-500">Passwords</p>
        </div>
        <div class="text-center p-3 bg-slate-50 rounded-xl">
          <p class="text-lg font-black <?= $duplicates>0?'text-red-600':'text-slate-900' ?>"><?= $duplicates ?></p>
          <p class="text-[11px] text-slate-500">Duplicates</p>
        </div>
        <div class="text-center p-3 bg-slate-50 rounded-xl">
          <p class="text-lg font-black <?= $expiringDocs>0?'text-amber-600':'text-slate-900' ?>"><?= $expiringDocs ?></p>
          <p class="text-[11px] text-slate-500">Expiring Docs</p>
        </div>
      </div>
    </div>
  </div>

  <!-- Password Health Center -->
  <div class="card p-6">
    <div class="flex items-start justify-between flex-wrap gap-3 mb-4">
      <div>
        <h3 class="font-semibold text-slate-900">Password Health Center</h3>
        <p class="text-xs text-slate-500 mt-0.5">Fix critical password issues with one-click actions.</p>
      </div>
      <a href="/passwords" class="text-sm text-blue-600 hover:underline font-medium">Open Password Vault</a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-5 gap-3">
      <a href="/passwords?strength=weak" class="rounded-xl border border-red-200 bg-red-50 p-4 hover:bg-red-100 transition-colors">
        <p class="text-[11px] uppercase tracking-wide text-red-700 font-semibold">Weak</p>
        <p class="text-2xl font-black text-red-700 mt-1"><?= (int) $weakPw ?></p>
        <p class="text-xs text-red-700/80 mt-1">Upgrade weak passwords</p>
      </a>

      <a href="/passwords" class="rounded-xl border border-amber-200 bg-amber-50 p-4 hover:bg-amber-100 transition-colors">
        <p class="text-[11px] uppercase tracking-wide text-amber-700 font-semibold">Reused</p>
        <p class="text-2xl font-black text-amber-700 mt-1"><?= (int) $duplicates ?></p>
        <p class="text-xs text-amber-700/80 mt-1">Replace duplicate passwords</p>
      </a>

      <a href="/passwords" class="rounded-xl border border-orange-200 bg-orange-50 p-4 hover:bg-orange-100 transition-colors">
        <p class="text-[11px] uppercase tracking-wide text-orange-700 font-semibold">Common Risk</p>
        <p class="text-2xl font-black text-orange-700 mt-1"><?= (int) ($commonRiskPw ?? 0) ?></p>
        <p class="text-xs text-orange-700/80 mt-1">Looks like breached patterns</p>
      </a>

      <a href="/passwords" class="rounded-xl border border-blue-200 bg-blue-50 p-4 hover:bg-blue-100 transition-colors">
        <p class="text-[11px] uppercase tracking-wide text-blue-700 font-semibold">Older Than 180d</p>
        <p class="text-2xl font-black text-blue-700 mt-1"><?= (int) ($stalePw ?? 0) ?></p>
        <p class="text-xs text-blue-700/80 mt-1">Rotate old passwords</p>
      </a>

      <a href="/passwords" class="rounded-xl border border-purple-200 bg-purple-50 p-4 hover:bg-purple-100 transition-colors">
        <p class="text-[11px] uppercase tracking-wide text-purple-700 font-semibold">Breached</p>
        <p class="text-2xl font-black text-purple-700 mt-1"><?= (int) ($breachedPw ?? 0) ?></p>
        <p class="text-xs text-purple-700/80 mt-1">Found in breach datasets</p>
      </a>
    </div>

    <div class="mt-4 flex flex-wrap gap-2">
      <a href="/passwords?strength=weak" class="btn-secondary text-xs py-1.5 px-3">Fix Weak Passwords</a>
      <a href="/passwords" class="btn-secondary text-xs py-1.5 px-3">Review Reused Passwords</a>
      <a href="/passwords" class="btn-secondary text-xs py-1.5 px-3">Rotate Old Passwords</a>
      <a href="/passwords" class="btn-secondary text-xs py-1.5 px-3">Check Breached Passwords</a>
    </div>

    <?php if (!breachCheckEnabled()): ?>
    <p class="text-xs text-slate-500 mt-3">Breach monitoring is disabled. Set BREACH_CHECK_ENABLED=true in environment to enable live breach checks.</p>
    <?php elseif (($breachChecksSkipped ?? 0) > 0): ?>
    <p class="text-xs text-slate-500 mt-3">Breach scan was partially completed due to API/network limits. Results will improve automatically with cache refresh.</p>
    <?php endif; ?>
  </div>

  <!-- Alerts -->
  <div class="card p-6">
    <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
      <svg class="w-4 h-4 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
      Recommendations
    </h3>
    <div class="space-y-3">
      <?php foreach ($alerts as $alert):
        $sev = $alert['severity'];
        $cfg = [
          'high'   => ['bg'=>'bg-red-50',    'border'=>'border-red-200',   'badge'=>'bg-red-100 text-red-700',    'dot'=>'bg-red-500'],
          'medium' => ['bg'=>'bg-amber-50',  'border'=>'border-amber-200', 'badge'=>'bg-amber-100 text-amber-700','dot'=>'bg-amber-500'],
          'low'    => ['bg'=>'bg-blue-50',   'border'=>'border-blue-200',  'badge'=>'bg-blue-100 text-blue-700',  'dot'=>'bg-blue-400'],
        ][$sev] ?? ['bg'=>'bg-slate-50','border'=>'border-slate-200','badge'=>'bg-slate-100 text-slate-600','dot'=>'bg-slate-400'];
      ?>
      <div class="flex items-start gap-3 p-4 rounded-xl border <?= $cfg['bg'] ?> <?= $cfg['border'] ?>">
        <div class="w-2 h-2 rounded-full mt-1.5 flex-shrink-0 <?= $cfg['dot'] ?>"></div>
        <div class="flex-1 min-w-0">
          <div class="flex flex-wrap items-center gap-2 mb-0.5">
            <p class="text-sm font-bold text-slate-900"><?= e($alert['title']) ?></p>
            <span class="text-[10px] px-2 py-0.5 rounded-full font-semibold <?= $cfg['badge'] ?>"><?= strtoupper($sev) ?></span>
          </div>
          <p class="text-xs text-slate-600 leading-relaxed"><?= e($alert['message']) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- Finance Snapshot -->
  <div class="card p-6">
    <h3 class="font-semibold text-slate-900 mb-4">Finance Snapshot — <?= date('F Y') ?></h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      <?php
      $fCards = [
        ['label'=>'Income',       'value'=>formatNPR($totalIncome),   'color'=>'text-green-600',  'bg'=>'bg-green-50'],
        ['label'=>'Expenses',     'value'=>formatNPR($totalExpenses), 'color'=>'text-red-600',    'bg'=>'bg-red-50'],
        ['label'=>'Net Savings',  'value'=>formatNPR($savings),       'color'=>$savings>=0?'text-blue-600':'text-red-600', 'bg'=>'bg-blue-50'],
        ['label'=>'Savings Rate', 'value'=>round($savingsRate,1).'%', 'color'=>$savingsRate>=20?'text-green-600':($savingsRate>=10?'text-amber-600':'text-red-600'), 'bg'=>'bg-slate-50'],
      ];
      foreach ($fCards as $fc):
      ?>
      <div class="<?= $fc['bg'] ?> rounded-xl p-4 text-center">
        <p class="text-xs text-slate-500 mb-1"><?= $fc['label'] ?></p>
        <p class="text-lg font-black <?= $fc['color'] ?>"><?= e($fc['value']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <div class="mt-4 text-right">
      <a href="/finance/analytics" class="text-sm text-blue-600 hover:underline font-medium">View full analytics →</a>
    </div>
  </div>

  <!-- Security Tips -->
  <div class="card p-6">
    <h3 class="font-semibold text-slate-900 mb-4 flex items-center gap-2">
      <svg class="w-4 h-4 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
      Security Tips for Nepal
    </h3>
    <?php
    $tips = [
      ['title'=>'eSewa & Khalti Safety','color'=>'bg-green-100 text-green-700','tips'=>['Download apps only from official Google Play or App Store.','Never share your PIN or OTP — not even with bank staff.','Enable fingerprint/screen lock on wallet apps.','Always verify URL: esewa.com.np / khalti.com']],
      ['title'=>'Online Banking','color'=>'bg-blue-100 text-blue-700','tips'=>['NRB-registered banks will NEVER ask for passwords via email/phone.','Use ConnectIPS only at connectips.com — check the SSL padlock.','Activate transaction SMS alerts on all your accounts.','Never use internet banking on shared/public computers.']],
      ['title'=>'Password Best Practices','color'=>'bg-purple-100 text-purple-700','tips'=>['Use a unique password for every service.','Strong password = 12+ chars, mixed case, numbers, symbols.','Use Key Wallet\'s built-in password generator.','Change passwords immediately if you suspect a breach.']],
      ['title'=>'Phishing & Scam Awareness','color'=>'bg-red-100 text-red-700','tips'=> ['"तपाईंले Rs.50,000 जित्नुभयो" — always a scam. Ignore it.','Verify URLs carefully before entering any personal info.','Nepal Telecom / Ncell will never ask for passwords via SMS.','Call the official helpline if in doubt.']],
      ['title'=>'Two-Factor Authentication','color'=>'bg-amber-100 text-amber-700','tips'=>['Store your Google Authenticator recovery codes offline.','Never screenshot or share your 2FA QR code.','Use 2FA on your email account too — it is the master key.','Report a lost 2FA device to support immediately.']],
    ];
    ?>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4" x-data="{}">
      <?php foreach ($tips as $tip): ?>
      <div x-data="{ open: false }" class="border border-slate-200 rounded-xl overflow-hidden">
        <button @click="open=!open" class="w-full flex items-center justify-between p-4 text-left">
          <span class="text-sm font-semibold <?= $tip['color'] ?> px-2.5 py-0.5 rounded-full"><?= e($tip['title']) ?></span>
          <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open?'rotate-180':''" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <div x-show="open" x-collapse class="px-4 pb-4">
          <ul class="space-y-1.5">
            <?php foreach ($tip['tips'] as $t): ?>
            <li class="flex items-start gap-2 text-xs text-slate-600">
              <svg class="w-3.5 h-3.5 text-green-500 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
              <?= e($t) ?>
            </li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</div>
