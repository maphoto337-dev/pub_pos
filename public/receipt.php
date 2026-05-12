<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireLogin();
$saleId = (int)($_GET['sale_id'] ?? 0);
$stmt = $pdo->prepare('SELECT s.*, u.full_name FROM sales s JOIN users u ON s.cashier_id=u.id WHERE s.id=?');
$stmt->execute([$saleId]);
$sale = $stmt->fetch();
$itemStmt = $pdo->prepare('SELECT si.*, p.name FROM sale_items si JOIN products p ON si.product_id=p.id WHERE sale_id=?');
$itemStmt->execute([$saleId]);
$items = $itemStmt->fetchAll();
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Receipt</title><link rel="stylesheet" href="style.css"></head>
<body onload="window.print()">
<div class="container" style="max-width:480px;">
<div class="card">
<div class="brand" style="justify-content:center; color:#262b49;"><img src="../assets/logo.png"><span>PUB POS</span></div>
<h2 style="text-align:center;">Receipt</h2>
<p><strong>Receipt:</strong> <?= htmlspecialchars($sale['receipt_no'] ?? '') ?><br>
<strong>Cashier:</strong> <?= htmlspecialchars($sale['full_name'] ?? '') ?><br>
<strong>Date:</strong> <?= htmlspecialchars($sale['created_at'] ?? '') ?></p>
<table class="table">
<?php foreach ($items as $item): ?>
<tr><td><?= htmlspecialchars($item['name']) ?> x <?= (int)$item['qty'] ?></td><td class="right"><?= money((float)$item['line_total']) ?></td></tr>
<?php endforeach; ?>
</table>
<p class="right"><strong>Total:</strong> <?= money((float)($sale['total_amount'] ?? 0)) ?></p>
<p class="right"><strong>Paid by:</strong> <?= htmlspecialchars($sale['payment_method'] ?? '') ?></p>
<p style="text-align:center;">Thank you for visiting.</p>
</div></div></body></html>
