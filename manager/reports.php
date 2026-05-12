<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_login('manager');

$salesSummary = fetch_all('SELECT date(created_at) AS day, COUNT(*) AS txns, SUM(total_amount) AS total FROM sales GROUP BY date(created_at) ORDER BY day DESC LIMIT 14');
$topProducts = fetch_all('SELECT p.name, SUM(si.quantity) AS qty, SUM(si.total_price) AS revenue FROM sale_items si JOIN products p ON p.id=si.product_id GROUP BY p.id ORDER BY qty DESC LIMIT 10');
$stock = fetch_all('SELECT name, category, stock_qty, min_stock FROM products ORDER BY stock_qty ASC');
layout_header('Reports');
?>
<div class="grid">
    <div class="card">
        <h3>Daily sales</h3>
        <table><tr><th>Date</th><th>Transactions</th><th>Total</th></tr>
        <?php foreach ($salesSummary as $r): ?><tr><td><?= e($r['day']) ?></td><td><?= (int)$r['txns'] ?></td><td>R <?= number_format((float)$r['total'],2) ?></td></tr><?php endforeach; ?>
        </table>
    </div>
    <div class="grid grid-2">
        <div class="card">
            <h3>Top selling items</h3>
            <table><tr><th>Product</th><th>Qty sold</th><th>Revenue</th></tr>
            <?php foreach ($topProducts as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= (int)$r['qty'] ?></td><td>R <?= number_format((float)$r['revenue'],2) ?></td></tr><?php endforeach; ?>
            </table>
        </div>
        <div class="card">
            <h3>Stock status</h3>
            <table><tr><th>Product</th><th>Qty</th><th>Min</th></tr>
            <?php foreach ($stock as $r): ?><tr><td><?= e($r['name']) ?></td><td><?= (int)$r['stock_qty'] ?></td><td><?= (int)$r['min_stock'] ?></td></tr><?php endforeach; ?>
            </table>
        </div>
    </div>
</div>
<?php layout_footer(); ?>
