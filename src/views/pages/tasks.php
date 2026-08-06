<div x-data="tasksPage()" class="space-y-4">
  <!-- Filters + Add -->
  <div class="flex flex-col sm:flex-row gap-3 items-start sm:items-center justify-between">
    <div class="flex gap-2 flex-wrap">
      <a href="/tasks?filter=all<?= $priority ? '&priority='.$priority : '' ?><?= $category ? '&category='.urlencode($category) : '' ?>"
         class="text-sm px-3 py-1.5 rounded-lg font-medium transition-colors <?= $filter==='all'?'bg-blue-600 text-white':'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50' ?>">
        All (<?= $counts['all'] ?>)
      </a>
      <a href="/tasks?filter=active<?= $priority ? '&priority='.$priority : '' ?><?= $category ? '&category='.urlencode($category) : '' ?>"
         class="text-sm px-3 py-1.5 rounded-lg font-medium transition-colors <?= $filter==='active'?'bg-blue-600 text-white':'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50' ?>">
        Active (<?= $counts['active'] ?>)
      </a>
      <a href="/tasks?filter=done<?= $priority ? '&priority='.$priority : '' ?><?= $category ? '&category='.urlencode($category) : '' ?>"
         class="text-sm px-3 py-1.5 rounded-lg font-medium transition-colors <?= $filter==='done'?'bg-blue-600 text-white':'bg-white border border-slate-300 text-slate-600 hover:bg-slate-50' ?>">
        Done (<?= $counts['done'] ?>)
      </a>
      <select onchange="window.location='/tasks?filter=<?= $filter ?>&priority='+this.value+'<?= $category ? '&category='.urlencode($category) : '' ?>'" class="text-sm border border-slate-300 rounded-lg px-2 py-1.5 bg-white text-slate-600 focus:outline-none">
        <option value="">All priorities</option>
        <option value="high" <?= $priority==='high'?'selected':'' ?>>High</option>
        <option value="medium" <?= $priority==='medium'?'selected':'' ?>>Medium</option>
        <option value="low" <?= $priority==='low'?'selected':'' ?>>Low</option>
      </select>
    </div>
    <button @click="openAdd()" class="btn-primary whitespace-nowrap">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
      Add Task
    </button>
  </div>

  <?php if (empty($tasks)): ?>
  <div class="card p-12 text-center">
    <div class="w-14 h-14 bg-orange-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
      <svg class="w-7 h-7 text-orange-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
    </div>
    <p class="text-slate-700 font-semibold mb-1">No tasks found</p>
    <p class="text-sm text-slate-500 mb-4">Stay organized by tracking your tasks</p>
    <button @click="openAdd()" class="btn-primary mx-auto">Add your first task</button>
  </div>
  <?php else: ?>
  <div class="card overflow-hidden">
    <div class="divide-y divide-slate-100">
      <?php foreach ($tasks as $task): ?>
      <?php
        $priColors = ['high'=>'bg-red-100 text-red-700','medium'=>'bg-amber-100 text-amber-700','low'=>'bg-slate-100 text-slate-600'];
        $priColor = $priColors[$task['priority']] ?? 'bg-slate-100 text-slate-600';
        $days = daysUntil($task['due_date']);
        $dueCls = $days !== null && $days < 0 ? 'text-red-600' : ($days !== null && $days <= 3 ? 'text-amber-600' : 'text-slate-400');
      ?>
      <div class="flex items-center gap-4 px-5 py-3 hover:bg-slate-50 transition-colors group <?= $task['completed'] ? 'opacity-60' : '' ?>">
        <!-- Toggle -->
        <form action="/tasks/<?= $task['id'] ?>/toggle" method="POST" class="flex-shrink-0">
          <input type="hidden" name="_csrf" value="<?= e(Auth::csrfToken()) ?>">
          <button type="submit" class="w-5 h-5 rounded border-2 <?= $task['completed'] ? 'bg-green-500 border-green-500' : 'border-slate-300 hover:border-blue-400' ?> flex items-center justify-center transition-all">
            <?php if ($task['completed']): ?>
            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
            <?php endif; ?>
          </button>
        </form>

        <!-- Content -->
        <div class="flex-1 min-w-0">
          <div class="flex items-center gap-2 mb-0.5 flex-wrap">
            <p class="text-sm font-medium text-slate-900 <?= $task['completed'] ? 'line-through text-slate-400' : '' ?> truncate"><?= e($task['title']) ?></p>
            <span class="badge <?= $priColor ?>"><?= ucfirst($task['priority']) ?></span>
            <?php if ($task['category'] !== 'Personal'): ?>
            <span class="badge bg-slate-100 text-slate-600"><?= e($task['category']) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($task['description']): ?>
          <p class="text-xs text-slate-400 truncate"><?= e($task['description']) ?></p>
          <?php endif; ?>
          <?php if ($task['due_date']): ?>
          <p class="text-xs <?= $dueCls ?> mt-0.5">
            <?= $days !== null && $days < 0 ? 'Overdue by '.abs($days).'d' : ($days === 0 ? 'Due today' : ($days === 1 ? 'Due tomorrow' : 'Due '.formatDate($task['due_date']))) ?>
          </p>
          <?php endif; ?>
        </div>

        <!-- Actions -->
        <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition-opacity flex-shrink-0">
          <button @click="openEdit(<?= htmlspecialchars(json_encode($task), ENT_QUOTES) ?>)" class="p-1.5 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
          </button>
          <button @click="confirmDelete(<?= $task['id'] ?>, '<?= e($task['title']) ?>')" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors">
            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
          </button>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
  <?php if (($totalPages ?? 1) > 1): ?>
  <div class="flex items-center justify-between text-sm text-slate-500 px-1">
    <span>Showing page <?= (int) $page ?> of <?= (int) $totalPages ?> (<?= (int) $total ?> total)</span>
    <div class="flex items-center gap-2">
      <?php $baseQuery = 'filter=' . urlencode($filter) . '&priority=' . urlencode($priority) . '&category=' . urlencode($category) . '&per_page=' . (int) ($perPage ?? 40); ?>
      <?php if ($page > 1): ?>
      <a href="/tasks?<?= $baseQuery ?>&page=<?= $page - 1 ?>" class="btn-secondary text-xs py-1.5 px-3">Prev</a>
      <?php endif; ?>
      <?php if ($page < $totalPages): ?>
      <a href="/tasks?<?= $baseQuery ?>&page=<?= $page + 1 ?>" class="btn-secondary text-xs py-1.5 px-3">Next</a>
      <?php endif; ?>
    </div>
  </div>
  <?php endif; ?>
  <?php endif; ?>

  <!-- Add / Edit Modal -->
  <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
    <div @click="showModal=false" class="absolute inset-0 bg-black/40 backdrop-blur-sm"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md" @click.stop>
      <div class="flex items-center justify-between p-6 border-b border-slate-100">
        <h3 class="font-bold text-slate-900" x-text="editId ? 'Edit Task' : 'Add Task'"></h3>
        <button @click="showModal=false" class="p-1 text-slate-400 hover:text-slate-600 rounded-lg hover:bg-slate-100">
          <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
      </div>
      <form :action="editId ? '/tasks/' + editId + '/update' : '/tasks'" method="POST" class="p-6 space-y-4">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrfToken()) ?>">
        <div>
          <label class="form-label">Title *</label>
          <input type="text" name="title" required :value="form.title" class="form-input" placeholder="What needs to be done?">
        </div>
        <div>
          <label class="form-label">Description</label>
          <textarea name="description" rows="2" class="form-input" placeholder="Optional details..." x-text="form.description"></textarea>
        </div>
        <div class="grid grid-cols-3 gap-3">
          <div>
            <label class="form-label">Priority</label>
            <select name="priority" class="form-input" x-ref="priSel">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
            </select>
          </div>
          <div>
            <label class="form-label">Category</label>
            <select name="category" class="form-input" x-ref="catSel">
              <?php foreach ($taskCategories as $c): ?><option value="<?= e($c) ?>"><?= e($c) ?></option><?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="form-label">Due Date</label>
            <input type="date" name="due_date" :value="form.due_date" class="form-input">
          </div>
        </div>
        <div class="flex justify-end gap-3 pt-2">
          <button type="button" @click="showModal=false" class="btn-secondary">Cancel</button>
          <button type="submit" class="btn-primary" x-text="editId ? 'Update Task' : 'Add Task'">Add Task</button>
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
      <h3 class="font-bold text-slate-900 mb-1">Delete task?</h3>
      <p class="text-sm text-slate-500 mb-5"><strong x-text="deleteTitle"></strong></p>
      <form :action="'/tasks/' + deleteId + '/delete'" method="POST" class="flex gap-3">
        <input type="hidden" name="_csrf" value="<?= e(Auth::csrfToken()) ?>">
        <button type="button" @click="showDelete=false" class="btn-secondary flex-1">Cancel</button>
        <button type="submit" class="btn-danger flex-1">Delete</button>
      </form>
    </div>
  </div>
</div>
<script>
function tasksPage() {
  return {
    showModal: false, showDelete: false,
    editId: null, deleteId: null, deleteTitle: '',
    form: { title: '', description: '', priority: 'medium', category: 'Personal', due_date: '' },
    openAdd() {
      this.editId = null;
      this.form = { title: '', description: '', priority: 'medium', category: 'Personal', due_date: '' };
      this.showModal = true;
      this.$nextTick(() => { if (this.$refs.priSel) this.$refs.priSel.value = 'medium'; if (this.$refs.catSel) this.$refs.catSel.value = 'Personal'; });
    },
    openEdit(t) {
      this.editId = t.id;
      this.form = { title: t.title, description: t.description || '', priority: t.priority, category: t.category, due_date: t.due_date || '' };
      this.showModal = true;
      this.$nextTick(() => { if (this.$refs.priSel) this.$refs.priSel.value = t.priority; if (this.$refs.catSel) this.$refs.catSel.value = t.category; });
    },
    confirmDelete(id, title) { this.deleteId = id; this.deleteTitle = title; this.showDelete = true; }
  };
}
</script>
