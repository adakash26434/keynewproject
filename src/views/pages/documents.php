<?php
$statusClasses = ['expired'=>'bg-red-100 text-red-700','expiring-soon'=>'bg-amber-100 text-amber-700','valid'=>'bg-green-100 text-green-700','none'=>'bg-slate-100 text-slate-600'];
$statusLabels  = ['expired'=>'Expired','expiring-soon'=>'Expiring soon','valid'=>'Valid','none'=>'No expiry'];
?>
<div x-data="docsPage()" class="space-y-4">
  <!-- Header -->
  <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
    <form method="GET" action="/documents" class="flex gap-2 flex-1 max-w-md">
      <div class="relative flex-1">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" name="q" value="<?= e($search) ?>" placeholder="Search documents..." class="w-full pl-9 pr-3 py-2 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white">
      </div>
      <select name="type" class="text-sm border border-slate-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 bg-white text-slate-700">
        <option value="">All types</option>
        <?php foreach ($types as $t): ?><option value="<?= e($t) ?>" <?= $type === $t ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
      </select>
      <button type="submit" class="btn-secondary">Filter</button>
    </form>
    <button @click="openAdd()" class="btn-primary whitespace-nowrap">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Add Document
    </button>
  </div>

  <?php if (empty($documents)): ?>
  <div class="card p-12 text-center">
    <div class="w-14 h-14 bg-violet-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
      <svg class="w-7 h-7 text-violet-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
    </div>
    <p class="text-slate-700 font-semibold mb-1">No documents yet</p>
    <p class="text-sm text-slate-500 mb-4">Store your important documents securely</p>
    <button @click="openAdd()" class="btn-primary mx-auto">Add your first document</button>
  </div>
  <?php else: ?>
  <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
    <?php foreach ($documents as $doc): ?>
    <?php
      $status = expiryStatus($doc['expiry_date']);
      $sc = $statusClasses[$status];
      $sl = $statusLabels[$status];
      $days = daysUntil($doc['expiry_date']);
    ?>
    <div class="card p-5 hover:shadow-md transition-shadow">
      <div class="flex items-start justify-between mb-3">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 bg-violet-50 rounded-xl flex items-center justify-center flex-shrink-0">
            <svg class="w-5 h-5 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
          </div>
          <div>
            <p class="font-semibold text-slate-900 text-sm"><?= e($doc['title']) ?></p>
            <p class="text-xs text-slate-500"><?= e($doc['type']) ?></p>
          </div>
        </div>
        <span class="badge <?= $sc ?>"><?= $sl ?></span>
      </div>

      <?php if ($doc['number_display']): ?>
      <div class="text-xs text-slate-500 mb-2">
        <span class="font-medium text-slate-700">Number:</span>
        <span class="font-mono ml-1"><?= e($doc['number_display']) ?></span>
      </div>
      <?php endif; ?>
      <?php if ($doc['issued_by']): ?>
      <div class="text-xs text-slate-500 mb-2">
        <span class="font-medium text-slate-700">Issued by:</span> <?= e($doc['issued_by']) ?>
      </div>
      <?php endif; ?>

      <div class="grid grid-cols-2 gap-2 text-xs text-slate-500 mb-4">
        <?php if ($doc['issue_date']): ?>
        <div><span class="font-medium text-slate-700">Issued:</span><br><?= formatDate($doc['issue_date']) ?></div>
        <?php endif; ?>
        <?php if ($doc['expiry_date']): ?>
        <div>
          <span class="font-medium text-slate-700">Expires:</span><br>
          <span class="<?= $status === 'expired' ? 'text-red-600 font-semibold' : ($status === 'expiring-soon' ? 'text-amber-600 font-semibold' : '') ?>">
            <?= formatDate($doc['expiry_date']) ?>
            <?php if ($days !== null && $days >= 0): ?><span class="text-slate-400">(<?= $days ?>d)</span><?php endif; ?>
          </span>
        </div>
        <?php endif; ?>
      </div>

      <div class="flex items-center gap-2 pt-3 border-t border-slate-100">
        <button @click="openEdit(<?= htmlspecialchars(json_encode($doc), ENT_QUOTES) ?>)" class="btn-secondary flex-1 text-xs py-1.5">Edit</button>
        <button @click="confirmDelete(<?= $doc['id'] ?>, '<?= e($doc['title']) ?>')" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>

  <!-- Add / Edit Modal -->
  <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div @click="showModal=false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg" @click.stop>
      <div class="flex items-center justify-between p-6 border-b border-slate-100">
        <h3 class="font-bold text-slate-900" x-text="editId ? 'Edit Document' : 'Add Document'"></h3>
        <button @click="showModal=false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <form :action="editId ? '/documents/' + editId + '/update' : '/documents'" method="POST" class="p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrfToken()) ?>">
        <div class="grid grid-cols-2 gap-4">
          <div class="col-span-2">
            <label class="form-label">Title *</label>
            <input type="text" name="title" required :value="form.title" class="form-input" placeholder="e.g. My Passport">
          </div>
          <div>
            <label class="form-label">Document Type *</label>
            <select name="type" class="form-input">
              <?php foreach ($types as $t): ?><option value="<?= e($t) ?>"><?= e($t) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Document Number</label>
            <input type="text" name="number" :value="form.number_display" class="form-input font-mono" placeholder="Encrypted securely">
          </div>
          <div>
            <label class="form-label">Issued By</label>
            <input type="text" name="issued_by" :value="form.issued_by" class="form-input" placeholder="e.g. Dept of Passports">
          </div>
          <div>
            <label class="form-label">Issue Date</label>
            <input type="date" name="issue_date" :value="form.issue_date" class="form-input">
          </div>
          <div>
            <label class="form-label">Expiry Date</label>
            <input type="date" name="expiry_date" :value="form.expiry_date" class="form-input">
          </div>
          <div class="col-span-2">
            <label class="form-label">Notes</label>
            <textarea name="notes" rows="2" class="form-input" placeholder="Optional notes..." x-text="form.notes"></textarea>
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showModal=false" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary" x-text="editId ? 'Update' : 'Save Document'">Save Document</button>
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
      <h3 class="font-bold text-slate-900 mb-1">Delete document?</h3>
      <p class="text-sm text-slate-500 mb-5"><strong x-text="deleteTitle" class="text-slate-700"></strong> will be permanently deleted.</p>
      <form :action="'/documents/' + deleteId + '/delete'" method="POST" class="flex gap-3">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrfToken()) ?>">
        <button type="button" @click="showDelete=false" class="btn-secondary flex-1">Cancel</button>
        <button type="submit" class="btn-danger flex-1">Delete</button>
      </form>
    </div>
  </div>
</div>
<script>
function docsPage() {
  return {
    showModal: false, showDelete: false,
    editId: null, deleteId: null, deleteTitle: '',
    form: {},
    openAdd() { this.editId = null; this.form = {}; this.showModal = true; },
    openEdit(doc) {
      this.editId = doc.id;
      this.form = { title: doc.title, type: doc.type, number_display: doc.number_display || '', issued_by: doc.issued_by || '', issue_date: doc.issue_date || '', expiry_date: doc.expiry_date || '', notes: doc.notes || '' };
      this.showModal = true;
      this.$nextTick(() => {
        const sel = document.querySelector('[name="type"]');
        if (sel) sel.value = doc.type || '';
      });
    },
    confirmDelete(id, title) { this.deleteId = id; this.deleteTitle = title; this.showDelete = true; }
  };
}
</script>
