<?php
$scoreColor = $score >= 80 ? 'text-green-600' : ($score >= 50 ? 'text-amber-600' : 'text-red-600');
$scoreBg    = $score >= 80 ? 'bg-green-50 border-green-200' : ($score >= 50 ? 'bg-amber-50 border-amber-200' : 'bg-red-50 border-red-200');
?>
<!-- Hero + Quick actions -->
<div class="card p-6 mb-6 bg-gradient-to-r from-slate-900 via-slate-800 to-blue-900 text-white border-0 shadow-lg">
  <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
    <div>
      <p class="text-xs uppercase tracking-[0.18em] text-blue-200 font-semibold">Today Overview</p>
      <h2 class="text-2xl font-bold mt-1">Welcome back, <?= e(explode(' ', Auth::user()['name'] ?? 'User')[0]) ?></h2>
      <p class="text-sm text-slate-200 mt-1">Secure your digital life, clear pending tasks, and keep documents up to date.</p>
    </div>
    <div class="grid grid-cols-2 gap-2 w-full lg:w-auto">
      <a href="/passwords" class="inline-flex items-center justify-center px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold transition-colors">Add Password</a>
      <a href="/documents" class="inline-flex items-center justify-center px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold transition-colors">Add Document</a>
      <a href="/tasks" class="inline-flex items-center justify-center px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold transition-colors">Create Task</a>
      <a href="/finance" class="inline-flex items-center justify-center px-3 py-2 rounded-lg bg-white/10 hover:bg-white/20 text-xs font-semibold transition-colors">Track Expense</a>
    </div>
  </div>
</div>

<?php if (!empty($smartAlerts)): ?>
<div class="card p-5 mb-6">
  <div class="flex items-center justify-between mb-3">
    <h3 class="text-sm font-semibold text-slate-900">Smart Alerts</h3>
    <a href="/insights" class="text-xs text-blue-600 hover:underline font-medium">Open insights</a>
  </div>
  <div class="space-y-2">
    <?php foreach ($smartAlerts as $a): ?>
    <?php
      $sev = (string) ($a['severity'] ?? 'low');
      $cls = $sev === 'high' ? 'bg-red-50 border-red-200 text-red-700' : ($sev === 'medium' ? 'bg-amber-50 border-amber-200 text-amber-700' : 'bg-blue-50 border-blue-200 text-blue-700');
    ?>
    <a href="<?= e((string) ($a['url'] ?? '/dashboard')) ?>" class="block border rounded-xl px-3 py-2.5 hover:shadow-sm transition-shadow <?= $cls ?>">
      <p class="text-sm font-semibold"><?= e((string) ($a['title'] ?? 'Alert')) ?></p>
      <p class="text-xs mt-0.5"><?= e((string) ($a['message'] ?? '')) ?></p>
    </a>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- Stats Grid -->
<div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="card p-5">
    <div class="flex items-center justify-between mb-3">
      <div class="w-9 h-9 bg-blue-50 rounded-lg flex items-center justify-center">
        <svg class="w-5 h-5 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      </div>
      <a href="/passwords" class="text-xs text-blue-600 hover:underline font-medium">View all</a>
    </div>
    <p class="text-2xl font-bold text-slate-900"><?= $passwordCount ?></p>
    <p class="text-xs text-slate-500 mt-0.5">Saved passwords</p>
  </div>

  <div class="card p-5">
    <div class="flex items-center justify-between mb-3">
      <div class="w-9 h-9 bg-violet-50 rounded-lg flex items-center justify-center">
        <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
      </div>
      <a href="/documents" class="text-xs text-blue-600 hover:underline font-medium">View all</a>
    </div>
    <p class="text-2xl font-bold text-slate-900"><?= $documentCount ?></p>
    <p class="text-xs text-slate-500 mt-0.5">Documents stored</p>
  </div>

  <div class="card p-5">
    <div class="flex items-center justify-between mb-3">
      <div class="w-9 h-9 bg-green-50 rounded-lg flex items-center justify-center">
        <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
      </div>
      <a href="/finance" class="text-xs text-blue-600 hover:underline font-medium">View</a>
    </div>
    <p class="text-2xl font-bold text-slate-900"><?= formatNPR($income - $expenses) ?></p>
    <p class="text-xs text-slate-500 mt-0.5">Balance this month</p>
  </div>

  <div class="card p-5">
    <div class="flex items-center justify-between mb-3">
      <div class="w-9 h-9 bg-orange-50 rounded-lg flex items-center justify-center">
        <svg class="w-5 h-5 text-orange-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
      </div>
      <a href="/tasks" class="text-xs text-blue-600 hover:underline font-medium">View all</a>
    </div>
    <p class="text-2xl font-bold text-slate-900"><?= $taskCount ?></p>
    <p class="text-xs text-slate-500 mt-0.5">Pending tasks</p>
  </div>
</div>

<!-- Security + Recent content -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mb-4">
  <!-- Security Score -->
  <div class="card p-6">
    <h3 class="text-sm font-semibold text-slate-900 mb-4">Security Score</h3>
    <div class="flex items-center justify-center mb-4">
      <div class="relative w-28 h-28">
        <?php $pct = $score; $circ = 2 * M_PI * 38; $offset = $circ * (1 - $pct / 100); ?>
        <svg class="w-full h-full -rotate-90" viewBox="0 0 100 100">
          <circle cx="50" cy="50" r="38" fill="none" stroke="#E2E8F0" stroke-width="8"/>
          <circle cx="50" cy="50" r="38" fill="none"
            stroke="<?= $score >= 80 ? '#22c55e' : ($score >= 50 ? '#f59e0b' : '#ef4444') ?>"
            stroke-width="8" stroke-linecap="round"
            stroke-dasharray="<?= round($circ, 2) ?>" stroke-dashoffset="<?= round($offset, 2) ?>"/>
        </svg>
        <div class="absolute inset-0 flex flex-col items-center justify-center">
          <span class="text-2xl font-bold <?= $scoreColor ?>"><?= $score ?></span>
          <span class="text-xs text-slate-500">/ 100</span>
        </div>
      </div>
    </div>
    <?php if ($weakCount > 0): ?>
    <div class="text-xs text-amber-700 bg-amber-50 rounded-lg p-2.5 flex items-start gap-2">
      <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
      <?= $weakCount ?> weak password<?= $weakCount > 1 ? 's' : '' ?> found
    </div>
    <?php endif; ?>
    <?php if (($duplicateCount ?? 0) > 0): ?>
    <div class="text-xs text-red-700 bg-red-50 rounded-lg p-2.5 flex items-start gap-2 mt-2">
      <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
      <?= (int) $duplicateCount ?> duplicate password<?= ((int) $duplicateCount) > 1 ? 's' : '' ?> detected
    </div>
    <?php endif; ?>
    <?php if (($stalePwCount ?? 0) > 0): ?>
    <div class="text-xs text-blue-700 bg-blue-50 rounded-lg p-2.5 flex items-start gap-2 mt-2">
      <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-11a1 1 0 10-2 0v4a1 1 0 102 0V7zm-1 8a1.25 1.25 0 100-2.5A1.25 1.25 0 0010 15z" clip-rule="evenodd"/></svg>
      <?= (int) $stalePwCount ?> password<?= ((int) $stalePwCount) > 1 ? 's are' : ' is' ?> older than 180 days
    </div>
    <?php endif; ?>
    <?php if (count($expiringDocs) > 0): ?>
    <div class="text-xs text-orange-700 bg-orange-50 rounded-lg p-2.5 flex items-start gap-2 mt-2">
      <svg class="w-4 h-4 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm-1-7V7a1 1 0 112 0v4a1 1 0 11-2 0zm0 4a1 1 0 112 0 1 1 0 01-2 0z" clip-rule="evenodd"/></svg>
      <?= count($expiringDocs) ?> document<?= count($expiringDocs) > 1 ? 's' : '' ?> expiring soon
    </div>
    <?php endif; ?>
    <?php if ($score === 100): ?>
    <div class="text-xs text-green-700 bg-green-50 rounded-lg p-2.5 flex items-center gap-2">
      <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
      Your vault is secure!
    </div>
    <?php endif; ?>
  </div>

  <!-- Recent Passwords -->
  <div class="card p-6 lg:col-span-2">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-semibold text-slate-900">Recent Passwords</h3>
      <a href="/passwords" class="text-xs text-blue-600 hover:underline font-medium">Manage vault</a>
    </div>
    <?php if (empty($recentPasswords)): ?>
    <div class="text-center py-8">
      <div class="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-2">
        <svg class="w-5 h-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
      </div>
      <p class="text-sm text-slate-500">No passwords yet</p>
      <a href="/passwords" class="text-xs text-blue-600 hover:underline mt-1 inline-block">Add your first password</a>
    </div>
    <?php else: ?>
    <div class="space-y-2">
      <?php foreach ($recentPasswords as $pw): ?>
      <?php
        $catColors = ['Digital Wallet'=>'bg-green-100 text-green-700','Banking'=>'bg-blue-100 text-blue-700','Telecom'=>'bg-purple-100 text-purple-700','Social'=>'bg-pink-100 text-pink-700','Work'=>'bg-orange-100 text-orange-700'];
        $catClass = $catColors[$pw['category']] ?? 'bg-slate-100 text-slate-600';
      ?>
      <div class="flex items-center gap-3 py-2 border-b border-slate-50 last:border-0">
        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-sm font-bold text-slate-600 flex-shrink-0">
          <?= strtoupper(substr($pw['title'], 0, 1)) ?>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-slate-900 truncate"><?= e($pw['title']) ?></p>
          <p class="text-xs text-slate-400 truncate"><?= e($pw['username']) ?></p>
        </div>
        <span class="badge <?= $catClass ?>"><?= e($pw['category']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Recent Tasks + Expiring Docs -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
  <!-- Recent Tasks -->
  <div class="card p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-semibold text-slate-900">Recent Tasks</h3>
      <a href="/tasks" class="text-xs text-blue-600 hover:underline font-medium">View all</a>
    </div>
    <?php if (empty($recentTasks)): ?>
    <div class="text-center py-6">
      <p class="text-sm text-slate-500">No tasks yet</p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
      <?php foreach ($recentTasks as $task): ?>
      <?php
        $priColor = $task['priority'] === 'high' ? 'bg-red-100 text-red-700' : ($task['priority'] === 'medium' ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-600');
      ?>
      <div class="flex items-center gap-3 py-2 border-b border-slate-50 last:border-0">
        <div class="w-4 h-4 rounded border-2 <?= $task['completed'] ? 'bg-green-500 border-green-500' : 'border-slate-300' ?> flex items-center justify-center flex-shrink-0">
          <?php if ($task['completed']): ?><svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg><?php endif; ?>
        </div>
        <p class="text-sm text-slate-900 flex-1 truncate <?= $task['completed'] ? 'line-through text-slate-400' : '' ?>"><?= e($task['title']) ?></p>
        <span class="badge <?= $priColor ?>"><?= ucfirst($task['priority']) ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Expiring Documents -->
  <div class="card p-6">
    <div class="flex items-center justify-between mb-4">
      <h3 class="text-sm font-semibold text-slate-900">Expiring Documents</h3>
      <a href="/documents" class="text-xs text-blue-600 hover:underline font-medium">View all</a>
    </div>
    <?php if (empty($expiringDocs)): ?>
    <div class="text-center py-6">
      <div class="w-10 h-10 bg-green-50 rounded-full flex items-center justify-center mx-auto mb-2">
        <svg class="w-5 h-5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
      </div>
      <p class="text-sm text-slate-500">All documents are valid</p>
    </div>
    <?php else: ?>
    <div class="space-y-2">
      <?php foreach ($expiringDocs as $doc): ?>
      <?php $days = daysUntil($doc['expiry_date']); ?>
      <div class="flex items-center gap-3 py-2 border-b border-slate-50 last:border-0">
        <div class="w-8 h-8 rounded-lg <?= $days <= 7 ? 'bg-red-100' : 'bg-amber-100' ?> flex items-center justify-center flex-shrink-0">
          <svg class="w-4 h-4 <?= $days <= 7 ? 'text-red-600' : 'text-amber-600' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div class="flex-1 min-w-0">
          <p class="text-sm font-medium text-slate-900 truncate"><?= e($doc['title']) ?></p>
          <p class="text-xs text-slate-500">Expires <?= formatDate($doc['expiry_date']) ?></p>
        </div>
        <span class="badge <?= $days <= 7 ? 'bg-red-100 text-red-700' : 'bg-amber-100 text-amber-700' ?>"><?= $days ?>d</span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</div>
