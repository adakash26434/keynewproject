<div class="max-w-2xl">
  <div class="card p-8 space-y-6">
    <div>
      <h2 class="text-2xl font-bold text-slate-900">Terms of Service</h2>
      <p class="text-sm text-slate-400 mt-1">Last updated: <?= date('F j, Y') ?></p>
    </div>
    <?php
    $sections = [
      ['title'=>'Acceptance of Terms','body'=>'By using Aakash Key Vault, you agree to these terms. If you do not agree, please do not use the application.'],
      ['title'=>'Description of Service','body'=>'Aakash Key Vault is a secure, self-hosted digital vault for storing passwords, documents, finance records, and tasks. The service is provided as-is.'],
      ['title'=>'User Responsibilities','body'=>'You are responsible for maintaining the confidentiality of your master password and 2FA recovery codes. You are responsible for all activity that occurs under your account.'],
      ['title'=>'Acceptable Use','body'=>'You may not use this application for any illegal purpose. You may not attempt to reverse-engineer, exploit, or circumvent security measures. You may not store data that violates applicable laws.'],
      ['title'=>'Data Ownership','body'=>'You own all data you enter into the application. We do not claim any rights over your data. You are responsible for backing up your data.'],
      ['title'=>'Security Disclaimer','body'=>'While we implement industry-standard security measures (AES-256-GCM, bcrypt, TOTP 2FA), no system is 100% secure. You use this application at your own risk. Keep your server software updated.'],
      ['title'=>'Limitation of Liability','body'=>'The developers of Aakash Key Vault shall not be liable for any data loss, security breach, or damages arising from the use of this application, to the maximum extent permitted by applicable law.'],
      ['title'=>'Modifications','body'=>'We may update these terms from time to time. Continued use of the application after changes constitutes acceptance of the new terms.'],
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
