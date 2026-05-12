<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_login('manager');

$salesToday = fetch_one('SELECT COALESCE(SUM(total_amount),0) AS total FROM sales WHERE DATE(created_at) = CURDATE()');
$damagesToday = fetch_one('SELECT COALESCE(SUM(quantity),0) AS total FROM damages WHERE DATE(created_at) = CURDATE()');
$lowStock = fetch_all('SELECT * FROM products WHERE stock_qty <= min_stock ORDER BY stock_qty ASC LIMIT 8');
$recentSales = fetch_all('SELECT s.*, u.full_name FROM sales s JOIN users u ON u.id=s.cashier_id ORDER BY s.id DESC LIMIT 5');

layout_header('Manager Dashboard');
?>
<div class="stat-row">
    <div class="card"><div class="muted">Sales today</div><div class="kpi">R <?= number_format((float)$salesToday['total'], 2) ?></div></div>
    <div class="card"><div class="muted">Damaged units today</div><div class="kpi"><?= (int)$damagesToday['total'] ?></div></div>
    <div class="card"><div class="muted">Products on system</div><div class="kpi"><?= count(fetch_all('SELECT id FROM products')) ?></div></div>
</div>
<div class="grid grid-2">
    <div class="card">
        <h3>Low stock alerts</h3>
        <table>
            <tr><th>Product</th><th>Qty</th><th>Minimum</th></tr>
            <?php foreach ($lowStock as $row): ?>
                <tr><td><?= e($row['name']) ?></td><td><?= (int)$row['stock_qty'] ?></td><td><?= (int)$row['min_stock'] ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
    <div class="card">
        <h3>Recent sales</h3>
        <table>
            <tr><th>Receipt</th><th>Cashier</th><th>Total</th></tr>
            <?php foreach ($recentSales as $row): ?>
                <tr><td><?= e($row['receipt_number']) ?></td><td><?= e($row['full_name']) ?></td><td>R <?= number_format((float)$row['total_amount'], 2) ?></td></tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php layout_footer(); ?>
