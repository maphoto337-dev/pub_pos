<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_login('cashier');
$user = current_user();

$openShift = fetch_one("SELECT * FROM shifts WHERE cashier_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1", [$user['id']]);
if (is_post()) {
    if (($_POST['action'] ?? '') === 'open' && !$openShift) {
        exec_stmt("INSERT INTO shifts (cashier_id, opening_float, opened_at, status) VALUES (?, ?, ?, 'open')", [$user['id'], (float)($_POST['opening_float'] ?? 0), db_now()]);
        flash('Shift opened successfully.');
    }
    if (($_POST['action'] ?? '') === 'close' && $openShift) {
        exec_stmt("UPDATE shifts SET actual_cash = ?, closed_at = ?, status = 'closed' WHERE id = ?", [(float)($_POST['actual_cash'] ?? 0), db_now(), $openShift['id']]);
        flash('Shift closed successfully.');
    }
    redirect_to('/cashier/shift.php');
}
$openShift = fetch_one("SELECT * FROM shifts WHERE cashier_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1", [$user['id']]);
$history = fetch_all('SELECT * FROM shifts WHERE cashier_id = ? ORDER BY id DESC LIMIT 10', [$user['id']]);
layout_header('Shift Management');
?>
<?php if ($msg = flash()): ?><div class="flash"><?= e($msg) ?></div><?php endif; ?>
<div class="grid grid-2">
    <div class="card">
        <?php if (!$openShift): ?>
            <h3>Open shift</h3>
            <form method="post" class="grid">
                <input type="hidden" name="action" value="open">
                <div><label>Opening float</label><input type="number" step="0.01" name="opening_float" required></div>
                <button type="submit">Open shift</button>
            </form>
        <?php else: ?>
            <h3>Close shift</h3>
            <p>Opened at: <?= e($openShift['opened_at']) ?></p>
            <p>Opening float: R <?= number_format((float)$openShift['opening_float'],2) ?></p>
            <form method="post" class="grid">
                <input type="hidden" name="action" value="close">
                <div><label>Actual cash in drawer</label><input type="number" step="0.01" name="actual_cash" required></div>
                <button type="submit">Close shift</button>
            </form>
        <?php endif; ?>
    </div>
    <div class="card">
        <h3>Shift history</h3>
        <table><tr><th>Opened</th><th>Status</th><th>Opening float</th></tr>
        <?php foreach ($history as $r): ?><tr><td><?= e($r['opened_at']) ?></td><td><?= e($r['status']) ?></td><td>R <?= number_format((float)$r['opening_float'],2) ?></td></tr><?php endforeach; ?>
        </table>
    </div>
</div>
<?php layout_footer(); ?>
