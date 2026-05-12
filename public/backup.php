<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner']);
$message = '';
if (isPost()) {
    $filename = 'pub_pos_backup_' . date('Ymd_His') . '.sql';
    $path = __DIR__ . '/../storage/backups/' . $filename;
    $message = 'Local backup instruction prepared: ' . $filename . '. Use the README to connect automatic uploads to Backblaze B2.';
    auditLog($pdo, (int)$_SESSION['user']['id'], 'BACKUP_TRIGGER', $filename);
    file_put_contents($path, '-- Placeholder backup file. Run mysqldump in XAMPP shell as explained in README.');
}
$files = glob(__DIR__ . '/../storage/backups/*');
rsort($files);
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Backups</title><link rel="stylesheet" href="style.css"></head><body>
<div class="header"><div class="brand"><img src="../assets/logo.png"><span>Backups</span></div><div><a style="color:#fff" href="owner_dashboard.php">Dashboard</a></div></div>
<div class="container grid grid-2">
<div class="card"><h2>Manual Backup</h2><?php if($message): ?><div class="notice success"><?= htmlspecialchars($message) ?></div><?php endif; ?><form method="post"><button>Create Manual Backup File</button></form><p class="muted" style="margin-top:10px;">For live database exports and Backblaze B2 sync, follow README instructions.</p></div>
<div class="card"><h2>Backup Files</h2><table class="table"><tr><th>File</th></tr><?php foreach($files as $f): ?><tr><td><?= htmlspecialchars(basename($f)) ?></td></tr><?php endforeach; ?></table></div>
</div></body></html>
