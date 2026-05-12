<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_login('cashier');
$user = current_user();
if (!isset($_SESSION['cart'])) { $_SESSION['cart'] = []; }

if (is_post()) {
    $action = $_POST['action'] ?? '';
    if ($action === 'add') {
        $productId = (int)($_POST['product_id'] ?? 0);
        $product = fetch_one('SELECT * FROM products WHERE id = ? AND active = 1', [$productId]);
        if ($product && (int)$product['stock_qty'] > 0) {
            $_SESSION['cart'][$productId] = ($_SESSION['cart'][$productId] ?? 0) + 1;
            flash('Item added to sale.');
        }
    }
    if ($action === 'scan') {
        $barcode = trim($_POST['barcode'] ?? '');
        $product = fetch_one('SELECT * FROM products WHERE barcode = ? AND active = 1', [$barcode]);
        if ($product && (int)$product['stock_qty'] > 0) {
            $_SESSION['cart'][(int)$product['id']] = ($_SESSION['cart'][(int)$product['id']] ?? 0) + 1;
            flash('Barcode scanned and item added.');
        } else {
            flash('Barcode not found or stock unavailable.');
        }
    }
    if ($action === 'remove') {
        $productId = (int)($_POST['product_id'] ?? 0);
        unset($_SESSION['cart'][$productId]);
    }
    if ($action === 'checkout' && !empty($_SESSION['cart'])) {
        global $pdo;
        $paymentMethod = trim($_POST['payment_method'] ?? 'cash');
        $cartProducts = [];
        $total = 0;
        foreach ($_SESSION['cart'] as $productId => $qty) {
            $p = fetch_one('SELECT * FROM products WHERE id = ?', [$productId]);
            if ($p && $qty <= (int)$p['stock_qty']) {
                $p['qty'] = $qty;
                $p['line_total'] = $qty * (float)$p['selling_price'];
                $total += $p['line_total'];
                $cartProducts[] = $p;
            }
        }
        if ($cartProducts) {
            $pdo->beginTransaction();
            $receiptNumber = 'RCP-' . date('YmdHis');
            exec_stmt('INSERT INTO sales (receipt_number, cashier_id, total_amount, payment_method, created_at) VALUES (?, ?, ?, ?, ?)', [$receiptNumber, $user['id'], $total, $paymentMethod, db_now()]);
            $saleId = (int)$pdo->lastInsertId();
            foreach ($cartProducts as $p) {
                exec_stmt('INSERT INTO sale_items (sale_id, product_id, quantity, unit_price, total_price) VALUES (?, ?, ?, ?, ?)', [$saleId, $p['id'], $p['qty'], $p['selling_price'], $p['line_total']]);
                update_product_stock((int)$p['id'], -((int)$p['qty']));
                add_stock_movement((int)$p['id'], 'sale', 0, (int)$p['qty'], 'Receipt ' . $receiptNumber, $user['id']);
            }
            $pdo->commit();
            $_SESSION['last_receipt'] = $receiptNumber;
            $_SESSION['cart'] = [];
            flash('Sale completed successfully. Receipt: ' . $receiptNumber);
        }
    }
    redirect_to('/cashier/sell.php');
}
$products = fetch_all('SELECT * FROM products WHERE active = 1 ORDER BY name ASC');
$cartRows = [];
$total = 0;
foreach ($_SESSION['cart'] as $productId => $qty) {
    $p = fetch_one('SELECT * FROM products WHERE id = ?', [$productId]);
    if ($p) {
        $p['qty'] = $qty;
        $p['line_total'] = $qty * (float)$p['selling_price'];
        $total += $p['line_total'];
        $cartRows[] = $p;
    }
}
layout_header('Cashier Sell Screen');
?>
<?php if ($msg = flash()): ?><div class="flash"><?= e($msg) ?></div><?php endif; ?>
<div class="sell-layout">
    <div class="card">
        <h3>Quick sale</h3>
        <form method="post" class="grid" style="margin-bottom:14px;">
            <input type="hidden" name="action" value="scan">
            <label>Scan product barcode</label>
            <input name="barcode" placeholder="Scan barcode here">
            <button type="submit">Add by barcode</button>
        </form>
        <div class="products-grid">
            <?php foreach ($products as $p): ?>
                <form method="post">
                    <input type="hidden" name="action" value="add">
                    <input type="hidden" name="product_id" value="<?= (int)$p['id'] ?>">
                    <button class="product-btn" type="submit">
                        <strong><?= e($p['name']) ?></strong><br>
                        <span class="muted"><?= e($p['category']) ?></span><br>
                        <span>R <?= number_format((float)$p['selling_price'], 2) ?></span><br>
                        <span class="muted">Stock: <?= (int)$p['stock_qty'] ?></span>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    </div>
    <div class="card">
        <h3>Current basket</h3>
        <table>
            <tr><th>Item</th><th>Qty</th><th>Total</th><th></th></tr>
            <?php foreach ($cartRows as $row): ?>
                <tr>
                    <td><?= e($row['name']) ?></td>
                    <td><?= (int)$row['qty'] ?></td>
                    <td>R <?= number_format((float)$row['line_total'], 2) ?></td>
                    <td>
                        <form method="post">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="product_id" value="<?= (int)$row['id'] ?>">
                            <button class="button-danger" type="submit">X</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <p class="right"><strong>Total: R <?= number_format($total, 2) ?></strong></p>
        <form method="post" class="grid">
            <input type="hidden" name="action" value="checkout">
            <label>Payment method</label>
            <select name="payment_method"><option value="cash">Cash</option><option value="card">Card</option><option value="eft">EFT</option></select>
            <button type="submit">Complete sale</button>
        </form>
        <p class="muted">Open tabs are disabled. Every basket must be paid and closed immediately.</p>
    </div>
</div>
<?php layout_footer(); ?>
