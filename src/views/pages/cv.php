<?php
$color = e($cv['template_color'] ?? '#0078D4');
$exp   = $cv['experience'] ?? [];
$edu   = $cv['education']  ?? [];
$skArr = $cv['skills']     ?? [];
$lang  = $cv['languages']  ?? [];
?>

<div class="space-y-6" x-data="{
  activeTab: 'editor',
  experience: <?= json_encode($exp) ?>,
  education: <?= json_encode($edu) ?>,
  skills: <?= json_encode(implode(', ', $skArr)) ?>,
  languages: <?= json_encode(implode(', ', $lang)) ?>,
  addExp() { this.experience.push({title:'',company:'',period:'',description:''}); },
  removeExp(i) { this.experience.splice(i,1); },
  addEdu() { this.education.push({degree:'',institution:'',year:'',grade:''}); },
  removeEdu(i) { this.education.splice(i,1); },
}">

  <!-- Tabs -->
  <div class="flex items-center gap-2">
    <button @click="activeTab='editor'"   :class="activeTab==='editor'   ? 'btn-primary' : 'btn-secondary'">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
      Edit CV
    </button>
    <button @click="activeTab='preview'" :class="activeTab==='preview' ? 'btn-primary' : 'btn-secondary'">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
      Preview
    </button>
    <button onclick="window.print()" class="btn-secondary ml-auto">
      <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
      Print / PDF
    </button>
  </div>

  <!-- EDITOR TAB -->
  <div x-show="activeTab==='editor'">
    <?php $s = getFlash('success'); $err = getFlash('error'); ?>
    <?php if ($s): ?><div class="bg-green-50 border border-green-200 text-green-700 rounded-lg px-4 py-3 text-sm mb-4"><?= e($s) ?></div><?php endif; ?>
    <?php if ($err): ?><div class="bg-red-50 border border-red-200 text-red-700 rounded-lg px-4 py-3 text-sm mb-4"><?= e($err) ?></div><?php endif; ?>

    <form method="POST" action="/cv" id="cvForm">
      <?= csrf() ?>
      <!-- Hidden JSON fields -->
      <input type="hidden" name="experience" x-bind:value="JSON.stringify(experience)">
      <input type="hidden" name="education"  x-bind:value="JSON.stringify(education)">

      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Personal Info -->
        <div class="card p-6 space-y-4">
          <h3 class="font-semibold text-slate-900">Personal Information</h3>
          <div class="grid grid-cols-2 gap-3">
            <div class="col-span-2">
              <label class="form-label">Full Name</label>
              <input type="text" name="full_name" class="form-input" value="<?= e($cv['full_name'] ?? '') ?>" placeholder="Your full name">
            </div>
            <div class="col-span-2">
              <label class="form-label">Job Title / Role</label>
              <input type="text" name="job_title" class="form-input" value="<?= e($cv['job_title'] ?? '') ?>" placeholder="e.g. Software Engineer">
            </div>
            <div>
              <label class="form-label">Email</label>
              <input type="email" name="email" class="form-input" value="<?= e($cv['email'] ?? $user['email'] ?? '') ?>">
            </div>
            <div>
              <label class="form-label">Phone</label>
              <input type="text" name="phone" class="form-input" value="<?= e($cv['phone'] ?? $user['phone'] ?? '') ?>">
            </div>
            <div class="col-span-2">
              <label class="form-label">Address</label>
              <input type="text" name="address" class="form-input" value="<?= e($cv['address'] ?? '') ?>" placeholder="City, Country">
            </div>
            <div>
              <label class="form-label">Website</label>
              <input type="url" name="website" class="form-input" value="<?= e($cv['website'] ?? '') ?>" placeholder="https://yoursite.com">
            </div>
            <div>
              <label class="form-label">LinkedIn</label>
              <input type="url" name="linkedin" class="form-input" value="<?= e($cv['linkedin'] ?? '') ?>" placeholder="linkedin.com/in/you">
            </div>
            <div class="col-span-2">
              <label class="form-label">Professional Summary</label>
              <textarea name="summary" rows="3" class="form-input" placeholder="Brief professional bio..."><?= e($cv['summary'] ?? '') ?></textarea>
            </div>
            <div class="col-span-2">
              <label class="form-label">Template Color</label>
              <div class="flex items-center gap-3">
                <input type="color" name="template_color" value="<?= $color ?>" class="h-9 w-16 rounded-lg border border-slate-300 cursor-pointer p-0.5">
                <span class="text-xs text-slate-500">Accent color for your CV</span>
              </div>
            </div>
          </div>
        </div>

        <!-- Skills & Languages -->
        <div class="space-y-4">
          <div class="card p-6 space-y-3">
            <h3 class="font-semibold text-slate-900">Skills</h3>
            <p class="text-xs text-slate-400">Enter skills separated by commas</p>
            <textarea name="skills" x-model="skills" rows="3" class="form-input" placeholder="PHP, JavaScript, MySQL, Git, ..."></textarea>
          </div>
          <div class="card p-6 space-y-3">
            <h3 class="font-semibold text-slate-900">Languages</h3>
            <p class="text-xs text-slate-400">Enter languages separated by commas</p>
            <textarea name="languages" x-model="languages" rows="2" class="form-input" placeholder="Nepali, English, Hindi ..."></textarea>
          </div>
        </div>
      </div>

      <!-- Experience -->
      <div class="card p-6 mt-6 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-slate-900">Work Experience</h3>
          <button type="button" @click="addExp()" class="btn-secondary text-xs py-1.5">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Entry
          </button>
        </div>
        <template x-for="(e, i) in experience" :key="i">
          <div class="grid grid-cols-2 gap-3 p-4 bg-slate-50 rounded-xl relative">
            <button type="button" @click="removeExp(i)" class="absolute top-3 right-3 text-slate-400 hover:text-red-500">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div><label class="form-label">Job Title</label><input type="text" x-model="e.title" class="form-input" placeholder="Software Engineer"></div>
            <div><label class="form-label">Company</label><input type="text" x-model="e.company" class="form-input" placeholder="Company Name"></div>
            <div><label class="form-label">Period</label><input type="text" x-model="e.period" class="form-input" placeholder="2022 – Present"></div>
            <div class="col-span-2"><label class="form-label">Description</label><textarea x-model="e.description" rows="2" class="form-input" placeholder="Key responsibilities and achievements..."></textarea></div>
          </div>
        </template>
        <p x-show="experience.length===0" class="text-sm text-slate-400 text-center py-4">No entries yet — click "Add Entry"</p>
      </div>

      <!-- Education -->
      <div class="card p-6 mt-4 space-y-4">
        <div class="flex items-center justify-between">
          <h3 class="font-semibold text-slate-900">Education</h3>
          <button type="button" @click="addEdu()" class="btn-secondary text-xs py-1.5">
            <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add Entry
          </button>
        </div>
        <template x-for="(e, i) in education" :key="i">
          <div class="grid grid-cols-2 gap-3 p-4 bg-slate-50 rounded-xl relative">
            <button type="button" @click="removeEdu(i)" class="absolute top-3 right-3 text-slate-400 hover:text-red-500">
              <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
            <div><label class="form-label">Degree / Program</label><input type="text" x-model="e.degree" class="form-input" placeholder="B.Sc. Computer Science"></div>
            <div><label class="form-label">Institution</label><input type="text" x-model="e.institution" class="form-input" placeholder="Tribhuvan University"></div>
            <div><label class="form-label">Year</label><input type="text" x-model="e.year" class="form-input" placeholder="2018 – 2022"></div>
            <div><label class="form-label">Grade / GPA</label><input type="text" x-model="e.grade" class="form-input" placeholder="3.8 GPA / Distinction"></div>
          </div>
        </template>
        <p x-show="education.length===0" class="text-sm text-slate-400 text-center py-4">No entries yet — click "Add Entry"</p>
      </div>

      <div class="flex justify-end mt-6">
        <button type="submit" class="btn-primary px-6">
          <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
          Save CV
        </button>
      </div>
    </form>
  </div>

  <!-- PREVIEW TAB -->
  <div x-show="activeTab==='preview'" x-cloak>
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden max-w-3xl mx-auto print:shadow-none print:rounded-none" id="cv-preview">
      <!-- Header -->
      <div class="p-8 text-white" style="background:<?= $color ?>">
        <h1 class="text-3xl font-black"><?= e($cv['full_name'] ?? $user['name'] ?? 'Your Name') ?></h1>
        <p class="text-lg opacity-90 mt-1"><?= e($cv['job_title'] ?? 'Your Title') ?></p>
        <div class="flex flex-wrap gap-x-4 gap-y-1 mt-3 text-sm opacity-80">
          <?php if (!empty($cv['email'])): ?><span>✉ <?= e($cv['email']) ?></span><?php endif; ?>
          <?php if (!empty($cv['phone'])): ?><span>☎ <?= e($cv['phone']) ?></span><?php endif; ?>
          <?php if (!empty($cv['address'])): ?><span>📍 <?= e($cv['address']) ?></span><?php endif; ?>
          <?php if (!empty($cv['website'])): ?><span>🌐 <?= e($cv['website']) ?></span><?php endif; ?>
          <?php if (!empty($cv['linkedin'])): ?><span>in <?= e($cv['linkedin']) ?></span><?php endif; ?>
        </div>
      </div>

      <div class="p-8 grid grid-cols-3 gap-8">
        <!-- Left Column -->
        <div class="col-span-1 space-y-6">
          <?php if (!empty($skArr)): ?>
          <div>
            <h2 class="text-xs font-black uppercase tracking-widest mb-3" style="color:<?= $color ?>">Skills</h2>
            <div class="flex flex-wrap gap-1.5">
              <?php foreach ($skArr as $sk): ?>
              <span class="text-xs px-2 py-0.5 rounded-full font-medium bg-slate-100 text-slate-700"><?= e($sk) ?></span>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
          <?php if (!empty($lang)): ?>
          <div>
            <h2 class="text-xs font-black uppercase tracking-widest mb-3" style="color:<?= $color ?>">Languages</h2>
            <ul class="space-y-1">
              <?php foreach ($lang as $l): ?>
              <li class="text-sm text-slate-700"><?= e($l) ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>
        </div>

        <!-- Right Column -->
        <div class="col-span-2 space-y-6">
          <?php if (!empty($cv['summary'])): ?>
          <div>
            <h2 class="text-xs font-black uppercase tracking-widest mb-2" style="color:<?= $color ?>">Summary</h2>
            <p class="text-sm text-slate-700 leading-relaxed"><?= e($cv['summary']) ?></p>
          </div>
          <?php endif; ?>

          <?php if (!empty($exp)): ?>
          <div>
            <h2 class="text-xs font-black uppercase tracking-widest mb-3" style="color:<?= $color ?>">Experience</h2>
            <div class="space-y-4">
              <?php foreach ($exp as $e): ?>
              <div class="border-l-2 pl-4" style="border-color:<?= $color ?>">
                <p class="font-bold text-slate-900 text-sm"><?= e($e['title'] ?? '') ?></p>
                <p class="text-xs text-slate-500"><?= e($e['company'] ?? '') ?> <?= !empty($e['period'])?'· '.e($e['period']):'' ?></p>
                <?php if (!empty($e['description'])): ?>
                <p class="text-xs text-slate-600 mt-1 leading-relaxed"><?= e($e['description']) ?></p>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>

          <?php if (!empty($edu)): ?>
          <div>
            <h2 class="text-xs font-black uppercase tracking-widest mb-3" style="color:<?= $color ?>">Education</h2>
            <div class="space-y-3">
              <?php foreach ($edu as $e): ?>
              <div class="border-l-2 pl-4" style="border-color:<?= $color ?>">
                <p class="font-bold text-slate-900 text-sm"><?= e($e['degree'] ?? '') ?></p>
                <p class="text-xs text-slate-500"><?= e($e['institution'] ?? '') ?> <?= !empty($e['year'])?'· '.e($e['year']):'' ?></p>
                <?php if (!empty($e['grade'])): ?>
                <p class="text-xs text-slate-400"><?= e($e['grade']) ?></p>
                <?php endif; ?>
              </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="text-center mt-4">
      <button onclick="window.print()" class="btn-primary">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
        Print / Export PDF
      </button>
    </div>
  </div>
</div>

<style>
@media print {
  .sidebar-link, aside, header, .btn-primary, .btn-secondary, [x-show="activeTab==='editor'"], nav { display: none !important; }
  body { background: white; }
  #cv-preview { page-break-inside: avoid; }
}
</style>
