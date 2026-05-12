<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_login('manager');
$user = current_user();

if (is_post()) {
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $notes = trim($_POST['notes'] ?? '');
    $product = fetch_one('SELECT stock_qty FROM products WHERE id = ?', [$productId]);
    if ($productId > 0 && $qty > 0 && $qty <= (int)$product['stock_qty']) {
        exec_stmt('INSERT INTO damages (product_id, quantity, reason, notes, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?)', [$productId, $qty, $reason, $notes, $user['id'], db_now()]);
        update_product_stock($productId, -$qty);
        add_stock_movement($productId, 'damage', 0, $qty, $reason . ' ' . $notes, $user['id']);
        flash('Damage captured and stock deducted.');
        redirect_to('/manager/damages.php');
    }
    flash('Could not save damage. Check available stock and fields.');
}
$products = fetch_all('SELECT id, name, stock_qty FROM products ORDER BY name ASC');
$rows = fetch_all('SELECT d.*, p.name FROM damages d JOIN products p ON p.id=d.product_id ORDER BY d.id DESC LIMIT 20');
layout_header('Damages and Wastage');
?>
<?php if ($msg = flash()): ?><div class="flash"><?= e($msg) ?></div><?php endif; ?>
<div class="grid grid-2">
    <div class="card">
        <h3>Record damage</h3>
        <form method="post" class="grid">
            <div><label>Product</label><select name="product_id" required><?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?> (stock <?= (int)$p['stock_qty'] ?>)</option><?php endforeach; ?></select></div>
            <div><label>Quantity damaged</label><input name="quantity" type="number" required></div>
            <div><label>Reason</label><select name="reason"><option>Broken bottle</option><option>Leakage</option><option>Expired</option><option>Missing stock</option><option>Staff error</option></select></div>
            <div><label>Notes</label><textarea name="notes"></textarea></div>
            <button type="submit">Save damage</button>
        </form>
    </div>
    <div class="card">
        <h3>Recent damages</h3>
        <table>
            <tr><th>Date</th><th>Product</th><th>Qty</th><th>Reason</th></tr>
            <?php foreach ($rows as $r): ?>
                <tr><td><?= e($r['created_at']) ?></td><td><?= e($r['name']) ?></td><td><?= (int)$r['quantity'] ?></td><td><?= e($r['reason']) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php layout_footer(); ?>
