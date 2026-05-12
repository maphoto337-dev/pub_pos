<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner']);
$daily = $pdo->query("SELECT DATE(created_at) day, SUM(total_amount) turnover, SUM(total_amount-total_cost) profit FROM sales WHERE status='completed' GROUP BY DATE(created_at) ORDER BY day DESC LIMIT 14")->fetchAll();
$variances = $pdo->query("SELECT s.*, u.full_name FROM shifts s JOIN users u ON s.cashier_id=u.id ORDER BY s.id DESC LIMIT 15")->fetchAll();
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Reports</title><link rel="stylesheet" href="style.css"></head><body>
<div class="header"><div class="brand"><img src="../assets/logo.png"><span>Reports</span></div><div><a style="color:#fff" href="owner_dashboard.php">Dashboard</a></div></div>
<div class="container grid grid-2">
<div class="card"><h2>Daily Turnover & Profit</h2><table class="table"><tr><th>Date</th><th>Turnover</th><th>Profit</th></tr><?php foreach($daily as $d): ?><tr><td><?= htmlspecialchars($d['day']) ?></td><td><?= money((float)$d['turnover']) ?></td><td><?= money((float)$d['profit']) ?></td></tr><?php endforeach; ?></table></div>
<div class="card"><h2>Shift Variances</h2><table class="table"><tr><th>Cashier</th><th>Open</th><th>Close</th><th>Variance</th></tr><?php foreach($variances as $v): ?><tr><td><?= htmlspecialchars($v['full_name']) ?></td><td><?= money((float)$v['opening_cash']) ?></td><td><?= money((float)$v['closing_cash']) ?></td><td><?= money((float)$v['variance_amount']) ?></td></tr><?php endforeach; ?></table></div>
</div></body></html>
