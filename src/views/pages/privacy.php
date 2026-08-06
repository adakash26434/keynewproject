<div class="max-w-2xl">
  <div class="card p-8 space-y-6">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Privacy Policy</h2>
      <p class="text-sm text-slate-400 mt-1">Last updated: <?= date('F j, Y') ?></p>
    </div>
    <?php
    $sections = [
      ['title'=>'What We Collect','body'=>'We collect only what you enter: your name, email, passwords, documents, finance records, and tasks. All sensitive data is encrypted at rest using AES-256-GCM before being stored in the database.'],
      ['title'=>'How We Protect Your Data','body'=>'Passwords and document numbers are encrypted with AES-256-GCM using a server-side key. Your master password is hashed with bcrypt (cost factor 12) and never stored in plain text. Two-factor authentication (TOTP) is required for all accounts.'],
      ['title'=>'Data Storage','body'=>'All data is stored locally in a SQLite database on your server. We do not send your data to any third-party service. If you are using the self-hosted version, your data never leaves your own server.'],
      ['title'=>'Session Security','body'=>'Sessions expire after 8 hours of inactivity. CSRF tokens are validated on all form submissions. Cookies are marked HttpOnly and SameSite=Lax.'],
      ['title'=>'Your Rights','body'=>'You can delete your account and all associated data at any time. You can export your data by contacting the administrator. We do not share your data with third parties.'],
      ['title'=>'Contact','body'=>'For privacy concerns, please contact the system administrator of your Aakash Key Vault installation.'],
    ];
    foreach ($sections as $s):
    ?>
    <div>
      <h3 class="font-semibold text-slate-900 mb-1"><?= e($s['title']) ?></h3>
      <p class="text-sm text-slate-600 leading-relaxed"><?= e($s['body']) ?></p>
    </div>
    <?php endforeach; ?>
    <div class="pt-2">
      <a href="/dashboard" class="btn-secondary">← Back to Dashboard</a>
    </div>
  </div>
</div>
