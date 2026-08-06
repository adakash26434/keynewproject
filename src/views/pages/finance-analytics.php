<div class="space-y-6">
  <!-- Header + month nav -->
  <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
    <div>
      <h2 class="text-xl font-bold text-slate-900">Finance Analytics</h2>
      <p class="text-sm text-slate-500 mt-0.5">Detailed breakdown of your financial health.</p>
    </div>
    <div class="flex items-center gap-1 bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
      <?php
        $prevM = $month == 1 ? 12 : $month - 1;
        $prevY = $month == 1 ? $year - 1 : $year;
        $nextM = $month == 12 ? 1 : $month + 1;
        $nextY = $month == 12 ? $year + 1 : $year;
        $isCurrentMonth = ($month == (int)date('n') && $year == (int)date('Y'));
      ?>
      <a href="/finance/analytics?year=<?= $prevY ?>&month=<?= $prevM ?>" class="p-1.5 rounded-lg hover:bg-slate-100 text-slate-600 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
      </a>
      <div class="px-3 min-w-[120px] text-center">
        <p class="text-sm font-bold text-slate-900"><?= $monthNames[$month] ?></p>
        <p class="text-xs text-slate-500"><?= $year ?></p>
      </div>
      <a href="<?= $isCurrentMonth ? '#' : "/finance/analytics?year=$nextY&month=$nextM" ?>"
        class="p-1.5 rounded-lg transition-colors <?= $isCurrentMonth ? 'text-slate-300 cursor-not-allowed' : 'hover:bg-slate-100 text-slate-600' ?>">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
      </a>
    </div>
  </div>

  <!-- Stat cards -->
  <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
    <?php
    $statCards = [
      ['label'=>'Total Income',   'value'=>formatNPR($income),         'sub'=>'Earned this month',    'color'=>'text-green-700','bg'=>'bg-green-100',
       'icon'=>'M13 7h8m0 0v8m0-8l-8 8-4-4-6 6'],
      ['label'=>'Total Expenses', 'value'=>formatNPR($expenses),       'sub'=>'Spent this month',     'color'=>'text-red-600',  'bg'=>'bg-red-100',
       'icon'=>'M13 17H5m0 0V9m0 8l8-8 4 4 6-6'],
      ['label'=>'Net Savings',    'value'=>formatNPR($savings),        'sub'=>$savings>=0?'Money saved':'Deficit this month', 'color'=>$savings>=0?'text-blue-700':'text-red-600','bg'=>$savings>=0?'bg-blue-100':'bg-red-100',
       'icon'=>'M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z'],
      ['label'=>'Savings Rate',   'value'=>round($savingsRate,1).'%',  'sub'=>$savingsRate>=20?'Excellent!':($savingsRate>=10?'Aim for 20%':'Save more'),
       'color'=>$savingsRate>=20?'text-green-700':($savingsRate>=10?'text-amber-700':'text-red-600'),
       'bg'=>$savingsRate>=20?'bg-green-100':($savingsRate>=10?'bg-amber-100':'bg-red-100'),
       'icon'=>'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z'],
    ];
    foreach ($statCards as $sc):
    ?>
    <div class="card p-5">
      <div class="flex items-center justify-between mb-3">
        <p class="text-[11px] font-bold uppercase tracking-wide text-slate-400"><?= $sc['label'] ?></p>
        <div class="w-9 h-9 <?= $sc['bg'] ?> rounded-xl flex items-center justify-center">
          <svg class="w-4.5 h-4.5 <?= $sc['color'] ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $sc['icon'] ?>"/></svg>
        </div>
      </div>
      <p class="text-xl font-black <?= $sc['color'] ?>"><?= e($sc['value']) ?></p>
      <p class="text-xs text-slate-400 mt-1"><?= e($sc['sub']) ?></p>
    </div>
    <?php endforeach; ?>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    <!-- Category Breakdown (pie-style bars) -->
    <div class="card p-6">
      <h3 class="font-semibold text-slate-900 mb-4">Expenses by Category</h3>
      <?php if (empty($byCategory)): ?>
      <div class="text-center py-12 text-slate-400">
        <svg class="w-12 h-12 mx-auto mb-3 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
        <p class="text-sm">No expense data for this month</p>
      </div>
      <?php else:
        $palette = ['#0078D4','#22c55e','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#ec4899','#f97316','#84cc16','#64748b'];
        $totalExp = array_sum($byCategory);
        $i = 0;
        foreach ($byCategory as $cat => $amt):
          $pct = $totalExp > 0 ? ($amt / $totalExp) * 100 : 0;
          $color = $palette[$i % count($palette)];
          $i++;
      ?>
      <div class="mb-3">
        <div class="flex justify-between mb-1">
          <span class="text-xs text-slate-600 flex items-center gap-1.5">
            <span class="w-2.5 h-2.5 rounded-full inline-block" style="background:<?= $color ?>"></span>
            <?= e($cat) ?>
          </span>
          <span class="text-xs font-semibold text-slate-900"><?= formatNPR($amt) ?> <span class="text-slate-400">(<?= round($pct,1) ?>%)</span></span>
        </div>
        <div class="h-2 bg-slate-100 rounded-full overflow-hidden">
          <div class="h-full rounded-full" style="width:<?= round($pct,1) ?>%;background:<?= $color ?>"></div>
        </div>
      </div>
      <?php endforeach; ?>
      <?php endif; ?>
    </div>

    <!-- 6-month trend -->
    <div class="card p-6">
      <h3 class="font-semibold text-slate-900 mb-4">6-Month Trend</h3>
      <?php
      $maxVal = 1;
      foreach ($barData as $bd) $maxVal = max($maxVal, $bd['income'], $bd['expense']);
      ?>
      <div class="space-y-3">
        <?php foreach ($barData as $bd): ?>
        <div>
          <div class="flex justify-between mb-1 text-xs">
            <span class="text-slate-500 font-medium w-14"><?= e($bd['month']) ?></span>
            <div class="flex gap-3">
              <span class="text-green-600 font-semibold"><?= formatNPR($bd['income']) ?></span>
              <span class="text-red-500 font-semibold"><?= formatNPR($bd['expense']) ?></span>
            </div>
          </div>
          <div class="flex gap-1 h-4">
            <div class="h-full bg-green-400 rounded-sm" style="width:<?= round(($bd['income']/$maxVal)*100,1) ?>%;min-width:<?= $bd['income']>0?'4px':'0' ?>"></div>
            <div class="h-full bg-red-400 rounded-sm"   style="width:<?= round(($bd['expense']/$maxVal)*100,1) ?>%;min-width:<?= $bd['expense']>0?'4px':'0' ?>"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="flex gap-4 mt-4 text-xs text-slate-500">
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-green-400 rounded-sm"></span>Income</span>
        <span class="flex items-center gap-1.5"><span class="w-3 h-3 bg-red-400 rounded-sm"></span>Expenses</span>
      </div>
    </div>
  </div>

  <!-- Quick links -->
  <div class="flex gap-3">
    <a href="/finance?year=<?= $year ?>&month=<?= $month ?>" class="btn-secondary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
      Back to Finance
    </a>
    <a href="/insights" class="btn-secondary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
      Security Insights
    </a>
  </div>
</div>
