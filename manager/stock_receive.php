<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_login('manager');
$user = current_user();

if (is_post()) {
    global $pdo;
    $supplier = trim($_POST['supplier_name'] ?? '');
    $invoice = trim($_POST['invoice_number'] ?? '');
    $method = trim($_POST['capture_method'] ?? 'manual');
    $productId = (int)($_POST['product_id'] ?? 0);
    $qty = (int)($_POST['quantity'] ?? 0);
    $unitCost = (float)($_POST['unit_cost'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');

    if ($productId > 0 && $qty > 0) {
        $pdo->beginTransaction();
        exec_stmt('INSERT INTO stock_receipts (supplier_name, invoice_number, capture_method, notes, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?)', [$supplier, $invoice, $method, $notes, $user['id'], db_now()]);
        $receiptId = (int)$pdo->lastInsertId();
        exec_stmt('INSERT INTO stock_receipt_items (stock_receipt_id, product_id, quantity, unit_cost) VALUES (?, ?, ?, ?)', [$receiptId, $productId, $qty, $unitCost]);
        update_product_stock($productId, $qty);
        add_stock_movement($productId, $method === 'slip_scan' ? 'slip_scan_receive' : 'manual_receive', $qty, 0, 'Supplier: ' . $supplier . ' Invoice: ' . $invoice, $user['id']);
        $pdo->commit();
        flash('Stock received and added successfully.');
        redirect_to('/manager/stock_receive.php');
    }
}
$products = fetch_all('SELECT id, name, barcode, stock_qty FROM products WHERE active = 1 ORDER BY name ASC');
$history = fetch_all('SELECT sr.*, p.name, sri.quantity FROM stock_receipts sr JOIN stock_receipt_items sri ON sri.stock_receipt_id = sr.id JOIN products p ON p.id = sri.product_id ORDER BY sr.id DESC LIMIT 10');
layout_header('Receive Stock');
?>
<?php if ($msg = flash()): ?><div class="flash"><?= e($msg) ?></div><?php endif; ?>
<div class="grid grid-2">
    <div class="card">
        <h3>Capture incoming stock</h3>
        <form method="post" class="grid">
            <div><label>Supplier name</label><input name="supplier_name"></div>
            <div><label>Invoice / slip number</label><input name="invoice_number"></div>
            <div><label>Capture method</label><select name="capture_method"><option value="manual">Manual entry</option><option value="slip_scan">Scanned from slip barcode / receipt</option></select></div>
            <div><label>Product</label><select name="product_id" required><?php foreach ($products as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['name']) ?> (<?= e($p['barcode']) ?>)</option><?php endforeach; ?></select></div>
            <div><label>Quantity received</label><input name="quantity" type="number" required></div>
            <div><label>Unit cost</label><input name="unit_cost" type="number" step="0.01" required></div>
            <div><label>Notes</label><textarea name="notes"></textarea></div>
            <button type="submit">Save stock receipt</button>
        </form>
    </div>
    <div class="card">
        <h3>Recent stock intakes</h3>
        <table>
            <tr><th>Date</th><th>Product</th><th>Qty</th><th>Method</th></tr>
            <?php foreach ($history as $h): ?>
            <tr><td><?= e($h['created_at']) ?></td><td><?= e($h['name']) ?></td><td><?= (int)$h['quantity'] ?></td><td><span class="badge"><?= e($h['capture_method']) ?></span></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php layout_footer(); ?>
