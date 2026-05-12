<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_login('cashier');
$user = current_user();
$rows = fetch_all('SELECT * FROM sales WHERE cashier_id = ? ORDER BY id DESC LIMIT 20', [$user['id']]);
layout_header('My Sales History');
?>
<div class="card">
    <table><tr><th>Receipt</th><th>Time</th><th>Payment</th><th>Total</th></tr>
    <?php foreach ($rows as $r): ?><tr><td><?= e($r['receipt_number']) ?></td><td><?= e($r['created_at']) ?></td><td><?= e($r['payment_method']) ?></td><td>R <?= number_format((float)$r['total_amount'],2) ?></td></tr><?php endforeach; ?>
    </table>
</div>
<?php layout_footer(); ?>
