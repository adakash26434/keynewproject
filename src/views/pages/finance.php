<?php $prevMonth = $month == 1 ? 12 : $month - 1; $prevYear = $month == 1 ? $year - 1 : $year; $nextMonth = $month == 12 ? 1 : $month + 1; $nextYear = $month == 12 ? $year + 1 : $year; ?>
<div x-data="financePage()" class="space-y-4">
  <!-- Month nav -->
  <div class="flex items-center justify-between">
    <a href="/finance?year=<?= $prevYear ?>&month=<?= $prevMonth ?>" class="btn-secondary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
    </a>
    <h2 class="text-lg font-bold text-slate-900"><?= $monthNames[$month] ?> <?= $year ?></h2>
    <a href="/finance?year=<?= $nextYear ?>&month=<?= $nextMonth ?>" class="btn-secondary">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
    </a>
  </div>

  <!-- Summary cards -->
  <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
    <div class="card p-5">
      <div class="flex items-center gap-3 mb-2">
        <div class="w-9 h-9 bg-green-50 rounded-lg flex items-center justify-center">
          <svg class="w-5 h-5 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </div>
        <p class="text-sm text-slate-500 font-medium">Income</p>
      </div>
      <p class="text-2xl font-bold text-green-600"><?= formatNPR($income) ?></p>
    </div>
    <div class="card p-5">
      <div class="flex items-center gap-3 mb-2">
        <div class="w-9 h-9 bg-red-50 rounded-lg flex items-center justify-center">
          <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"/></svg>
        </div>
        <p class="text-sm text-slate-500 font-medium">Expenses</p>
      </div>
      <p class="text-2xl font-bold text-red-500"><?= formatNPR($expenses) ?></p>
    </div>
    <div class="card p-5">
      <div class="flex items-center gap-3 mb-2">
        <div class="w-9 h-9 <?= $balance >= 0 ? 'bg-blue-50' : 'bg-red-50' ?> rounded-lg flex items-center justify-center">
          <svg class="w-5 h-5 <?= $balance >= 0 ? 'text-blue-600' : 'text-red-500' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
        </div>
        <p class="text-sm text-slate-500 font-medium">Balance</p>
      </div>
      <p class="text-2xl font-bold <?= $balance >= 0 ? 'text-blue-600' : 'text-red-500' ?>"><?= formatNPR($balance) ?></p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
    <!-- Transactions -->
    <div class="card lg:col-span-2 overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-slate-100">
        <h3 class="font-semibold text-slate-900 text-sm">Transactions</h3>
        <button @click="openAdd()" class="btn-primary text-xs py-1.5 px-3">
          <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
          Add
        </button>
      </div>
      <?php if (empty($records)): ?>
      <div class="p-12 text-center">
        <div class="w-12 h-12 bg-green-50 rounded-2xl flex items-center justify-center mx-auto mb-3">
          <svg class="w-6 h-6 text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/></svg>
        </div>
        <p class="text-slate-700 font-semibold mb-1">No transactions this month</p>
        <p class="text-sm text-slate-500 mb-3">Start tracking your finances</p>
        <button @click="openAdd()" class="btn-primary mx-auto text-sm">Add transaction</button>
      </div>
      <?php else: ?>
      <div class="divide-y divide-slate-100">
        <?php foreach ($records as $r): ?>
        <div class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 transition-colors group">
          <div class="w-8 h-8 rounded-full <?= $r['type']==='income'?'bg-green-100':'bg-red-100' ?> flex items-center justify-center flex-shrink-0">
            <svg class="w-4 h-4 <?= $r['type']==='income'?'text-green-600':'text-red-500' ?>" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <?php if ($r['type']==='income'): ?><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/><?php else: ?><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/><?php endif; ?>
            </svg>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-slate-900 truncate"><?= e($r['description'] ?: $r['category']) ?></p>
            <p class="text-xs text-slate-400"><?= e($r['category']) ?> · <?= formatDate($r['record_date']) ?></p>
          </div>
          <div class="text-right">
            <p class="text-sm font-bold <?= $r['type']==='income'?'text-green-600':'text-red-500' ?>"><?= ($r['type']==='income'?'+':'-') . $r['amount_fmt'] ?></p>
          </div>
          <div class="flex gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click="openEdit(<?= htmlspecialchars(json_encode($r), ENT_QUOTES) ?>)" class="p-1 text-slate-400 hover:text-slate-600">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            </button>
            <button @click="confirmDelete(<?= $r['id'] ?>, '<?= e($r['description'] ?: $r['category']) ?>')" class="p-1 text-slate-400 hover:text-red-500">
              <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Category breakdown -->
    <div class="card p-5">
      <h3 class="font-semibold text-slate-900 text-sm mb-4">Expense Breakdown</h3>
      <?php if (empty($byCategory)): ?>
      <p class="text-sm text-slate-400 text-center py-8">No expenses yet</p>
      <?php else: ?>
      <?php $maxCat = max($byCategory) ?: 1; ?>
      <div class="space-y-3">
        <?php foreach ($byCategory as $cat => $amt): ?>
        <div>
          <div class="flex items-center justify-between mb-1">
            <span class="text-xs font-medium text-slate-600 truncate"><?= e($cat) ?></span>
            <span class="text-xs font-semibold text-slate-800 ml-2 flex-shrink-0"><?= formatNPR($amt) ?></span>
          </div>
          <div class="h-1.5 bg-slate-100 rounded-full">
            <div class="h-1.5 bg-blue-500 rounded-full" style="width: <?= round($amt/$maxCat*100) ?>%"></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php if ($income > 0 && $expenses > 0): ?>
      <div class="mt-4 pt-4 border-t border-slate-100">
        <p class="text-xs text-slate-500">Savings rate</p>
        <p class="text-lg font-bold <?= $balance>=0?'text-green-600':'text-red-500' ?>"><?= $income > 0 ? round(($balance/$income)*100, 1) : 0 ?>%</p>
      </div>
      <?php endif; ?>
      <?php endif; ?>
    </div>
  </div>

  <!-- Add / Edit Modal -->
  <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div @click="showModal=false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
      <div class="flex items-center justify-between p-6 border-b border-slate-100">
        <h3 class="font-bold text-slate-900" x-text="editId ? 'Edit Transaction' : 'Add Transaction'"></h3>
        <button @click="showModal=false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <form :action="editId ? '/finance/' + editId + '/update' : '/finance'" method="POST" class="p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrfToken()) ?>">
        <input type="hidden" name="year" value="<?= $year ?>">
        <input type="hidden" name="month" value="<?= $month ?>">
        <div>
          <label class="form-label">Type *</label>
          <div class="grid grid-cols-2 gap-2">
            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer" :class="form.type==='income'?'border-green-500 bg-green-50':'border-slate-300'">
              <input type="radio" name="type" value="income" x-model="form.type" class="hidden">
              <svg class="w-4 h-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
              <span class="text-sm font-medium text-slate-700">Income</span>
            </label>
            <label class="flex items-center gap-2 p-3 border rounded-lg cursor-pointer" :class="form.type==='expense'?'border-red-500 bg-red-50':'border-slate-300'">
              <input type="radio" name="type" value="expense" x-model="form.type" class="hidden">
              <svg class="w-4 h-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
              <span class="text-sm font-medium text-slate-700">Expense</span>
            </label>
          </div>
        </div>
        <div class="grid grid-cols-2 gap-4">
          <div>
            <label class="form-label">Amount (Rs.) *</label>
            <input type="number" name="amount" required step="0.01" min="0.01" :value="form.amount" class="form-input" placeholder="0.00">
          </div>
          <div>
            <label class="form-label">Date *</label>
            <input type="date" name="record_date" required :value="form.record_date" class="form-input">
          </div>
          <div class="col-span-2">
            <label class="form-label">Category *</label>
            <select name="category" class="form-input" x-ref="catSel">
              <?php foreach ($categories as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div class="col-span-2">
            <label class="form-label">Description</label>
            <input type="text" name="description" :value="form.description" class="form-input" placeholder="Optional note">
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showModal=false" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary" x-text="editId ? 'Update' : 'Save'">Save</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Delete confirm -->
  <div x-show="showDelete" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div @click="showDelete=false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm p-6 text-center" @click.stop>
      <div class="w-12 h-12 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-6 h-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
      </div>
      <h3 class="font-bold text-slate-900 mb-1">Delete transaction?</h3>
      <p class="text-sm text-slate-500 mb-5"><strong x-text="deleteTitle"></strong> will be removed.</p>
      <form :action="'/finance/' + deleteId + '/delete'" method="POST" class="flex gap-3">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrfToken()) ?>">
        <button type="button" @click="showDelete=false" class="btn-secondary flex-1">Cancel</button>
        <button type="submit" class="btn-danger flex-1">Delete</button>
      </form>
    </div>
  </div>
</div>
<script>
function financePage() {
  return {
    showModal: false, showDelete: false,
    editId: null, deleteId: null, deleteTitle: '',
    form: { type: 'expense', amount: '', record_date: '<?= date('Y-m-d') ?>', category: '', description: '' },
    openAdd() {
      this.editId = null;
      this.form = { type: 'expense', amount: '', record_date: '<?= date('Y-m-d') ?>', category: '', description: '' };
      this.showModal = true;
    },
    openEdit(r) {
      this.editId = r.id;
      this.form = { type: r.type, amount: r.amount, record_date: r.record_date, category: r.category, description: r.description || '' };
      this.showModal = true;
      this.$nextTick(() => {
        const sel = this.$refs.catSel;
        if (sel) sel.value = r.category;
      });
    },
    confirmDelete(id, title) { this.deleteId = id; this.deleteTitle = title; this.showDelete = true; }
  };
}
</script>
