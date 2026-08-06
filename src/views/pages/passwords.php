<?php
$strengthColors = ['weak'=>'bg-red-100 text-red-700','fair'=>'bg-amber-100 text-amber-700','good'=>'bg-yellow-100 text-yellow-700','strong'=>'bg-green-100 text-green-700','very-strong'=>'bg-emerald-100 text-emerald-700'];
$catColors = ['Digital Wallet'=>'bg-green-100 text-green-700','Banking'=>'bg-blue-100 text-blue-700','Telecom'=>'bg-purple-100 text-purple-700','Social'=>'bg-pink-100 text-pink-700','Work'=>'bg-orange-100 text-orange-700','Email'=>'bg-sky-100 text-sky-700','Shopping'=>'bg-rose-100 text-rose-700','Government'=>'bg-teal-100 text-teal-700'];
?>
<div x-data="passwordsPage()" class="space-y-4">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
    <form method="GET" action="/passwords" class="flex gap-2 flex-1 max-w-md">
      <div class="relative flex-1">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search passwords..." class="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
      </div>
      <select name="category" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-slate-700">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?><option value="<?= e($c['category']) ?>" <?= $cat === $c['category'] ? 'selected' : '' ?>><?= e($c['category']) ?></option><?php endforeach; ?>
      </select>
      <button type="submit" class="btn-secondary">Filter</button>
    </form>
    <button @click="openAdd()" class="btn-primary whitespace-nowrap">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Add Password
    </button>
  </div>

  <!-- Password List -->
  <?php if (empty($passwords)): ?>
  <div class="card p-12 text-center">
    <div class="w-14 h-14 bg-blue-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
      <svg class="w-7 h-7 text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
    </div>
    <p class="text-slate-700 font-semibold mb-1">No passwords yet</p>
    <p class="text-sm text-slate-500 mb-4">Start saving your credentials securely</p>
    <button @click="openAdd()" class="btn-primary mx-auto">Add your first password</button>
  </div>
  <?php else: ?>
  <div class="card overflow-hidden">
    <table class="w-full text-sm">
      <thead class="bg-slate-50 border-b border-slate-200">
        <tr>
          <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">Title</th>
          <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3 hidden md:table-cell">Username</th>
          <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3 hidden lg:table-cell">Category</th>
          <th class="text-left text-xs font-semibold text-slate-500 uppercase tracking-wide px-4 py-3">Strength</th>
          <th class="text-right px-4 py-3"></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        <?php foreach ($passwords as $pw): ?>
        <?php
          $sc = $strengthColors[$pw['strength']] ?? 'bg-slate-100 text-slate-600';
          $cc = $catColors[$pw['category']] ?? 'bg-slate-100 text-slate-600';
        ?>
        <tr class="hover:bg-slate-50 transition-colors">
          <td class="px-4 py-3">
            <div class="flex items-center gap-3">
              <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center font-bold text-slate-600 text-sm flex-shrink-0">
                <?= strtoupper(substr($pw['title'], 0, 1)) ?>
              </div>
              <div>
                <p class="font-medium text-slate-900"><?= e($pw['title']) ?></p>
                <?php if ($pw['url']): ?><p class="text-xs text-slate-400 truncate max-w-[140px]"><?= e($pw['url']) ?></p><?php endif; ?>
              </div>
            </div>
          </td>
          <td class="px-4 py-3 hidden md:table-cell text-slate-600 text-sm"><?= e($pw['username']) ?: '<span class="text-slate-300">—</span>' ?></td>
          <td class="px-4 py-3 hidden lg:table-cell"><span class="badge <?= $cc ?>"><?= e($pw['category']) ?></span></td>
          <td class="px-4 py-3"><span class="badge <?= $sc ?>"><?= ucfirst($pw['strength']) ?></span></td>
          <td class="px-4 py-3">
            <div class="flex items-center justify-end gap-1">
              <button @click="copyPassword(<?= $pw['id'] ?>)" title="Copy password" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
              </button>
              <button @click="openEdit(<?= htmlspecialchars(json_encode($pw), ENT_QUOTES) ?>)" title="Edit" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
              </button>
              <button @click="confirmDelete(<?= $pw['id'] ?>, '<?= e($pw['title']) ?>')" title="Delete" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
              </button>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php if (($totalPages ?? 1) > 1): ?>
  <div class="flex items-center justify-between text-sm text-slate-500 px-1">
    <span>Showing page <?= (int) $page ?> of <?= (int) $totalPages ?> (<?= (int) $total ?> total)</span>
    <div class="flex items-center gap-2">
      <?php $baseQuery = 'q=' . urlencode($search) . '&category=' . urlencode($cat) . '&per_page=' . (int) ($perPage ?? 40); ?>
      <?php if ($page > 1): ?>
      <a href="/passwords?<?= $baseQuery ?>&page=<?= $page - 1 ?>" class="btn-secondary text-xs py-1.5 px-3">Prev</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
      <a href="/passwords?<?= $baseQuery ?>&page=<?= $page + 1 ?>" class="btn-secondary text-xs py-1.5 px-3">Next</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Add / Edit Modal -->
  <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div @click="showModal=false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg" @click.stop>
      <div class="flex items-center justify-between p-6 border-b border-slate-100">
        <h3 class="font-bold text-slate-900" x-text="editId ? 'Edit Password' : 'Add Password'"></h3>
        <button @click="showModal=false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <form :action="editId ? '/passwords/' + editId + '/update' : '/passwords'" method="POST" class="p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrfToken()) ?>">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="form-label">Title *</label>
            <input type="text" name="title" required :value="form.title" class="form-input" placeholder="e.g. eSewa, Gmail">
          </div>
          <div>
            <label class="form-label">Username / Email</label>
            <input type="text" name="username" :value="form.username" class="form-input" placeholder="your@email.com">
          </div>
          <div>
            <label class="form-label">Password</label>
            <div class="relative" x-data="{ show: false }">
              <input :type="show ? 'text' : 'password'" name="password" :value="form.password" class="form-input pr-10" placeholder="••••••••">
              <button type="button" @click="show=!show" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                <svg x-show="!show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                <svg x-show="show" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/></svg>
              </button>
            </div>
          </div>
          <div>
            <label class="form-label">Website URL</label>
            <input type="text" name="url" :value="form.url" class="form-input" placeholder="https://esewa.com.np">
          </div>
          <div>
            <label class="form-label">Category</label>
            <select name="category" class="form-input">
              <?php foreach (['Digital Wallet','Banking','Telecom','Social','Email','Shopping','Work','Government','Entertainment','Other'] as $c): ?>
              <option value="<?= $c ?>"><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-span-2">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="2" class="form-input" placeholder="Optional notes..." x-text="form.notes"></textarea>
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showModal=false" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary" x-text="editId ? 'Update' : 'Save Password'">Save Password</button>
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
      <h3 class="font-bold text-slate-900 mb-1">Delete password?</h3>
      <p class="text-sm text-slate-500 mb-5"><strong x-text="deleteTitle" class="text-slate-700"></strong> will be permanently deleted.</p>
      <form :action="'/passwords/' + deleteId + '/delete'" method="POST" class="flex gap-3">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrfToken()) ?>">
        <button type="button" @click="showDelete=false" class="btn-secondary flex-1">Cancel</button>
        <button type="submit" class="btn-danger flex-1">Delete</button>
      </form>
    </div>
  </div>

  <!-- Copy toast -->
  <div x-show="copied" x-cloak x-transition class="fixed bottom-6 right-6 z-50 bg-slate-900 text-white text-sm font-medium px-4 py-2.5 rounded-xl shadow-lg flex items-center gap-2">
    <svg class="w-4 h-4 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
    Password copied!
  </div>
</div>

<script>
function passwordsPage() {
  return {
    showModal: false, showDelete: false, copied: false,
    editId: null, deleteId: null, deleteTitle: '',
    form: { title: '', username: '', password: '', url: '', category: '', notes: '' },
    openAdd() {
      this.editId = null;
      this.form = { title: '', username: '', password: '', url: '', category: '', notes: '' };
      this.showModal = true;
    },
    openEdit(pw) {
      this.editId = pw.id;
      this.form = { title: pw.title, username: pw.username || '', password: '', url: pw.url || '', category: pw.category, notes: pw.notes || '' };
      this.$nextTick(() => {
        fetch('/passwords/' + pw.id + '/reveal')
          .then(r => r.json())
          .then(d => { if (d.password) this.form.password = d.password; if (d.username) this.form.username = d.username; });
      });
      this.showModal = true;
    },
    confirmDelete(id, title) { this.deleteId = id; this.deleteTitle = title; this.showDelete = true; },
    copyPassword(id) {
      fetch('/passwords/' + id + '/reveal')
        .then(r => r.json())
        .then(d => {
          if (d.password) {
            navigator.clipboard.writeText(d.password).then(() => {
              this.copied = true;
              setTimeout(() => this.copied = false, 2500);
            });
          }
        });
    }
  };
}
</script>
