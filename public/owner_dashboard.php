<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner']);

$salesToday = $pdo->query("SELECT COALESCE(SUM(total_amount),0) total FROM sales WHERE DATE(created_at)=CURDATE() AND status='completed'")->fetch()['total'];
$profitToday = $pdo->query("SELECT COALESCE(SUM(total_amount-total_cost),0) total FROM sales WHERE DATE(created_at)=CURDATE() AND status='completed'")->fetch()['total'];
$openShifts = $pdo->query("SELECT COUNT(*) c FROM shifts WHERE status='open'")->fetch()['c'];
$lowStock = lowStockItems($pdo);
$recentSales = $pdo->query("SELECT s.receipt_no, s.total_amount, s.payment_method, s.created_at, u.full_name FROM sales s JOIN users u ON s.cashier_id=u.id ORDER BY s.id DESC LIMIT 8")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Owner Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="header">
  <div class="brand"><img src="../assets/logo.png" alt="Logo"><span>PUB POS Owner</span></div>
  <div><?= htmlspecialchars($_SESSION['user']['name']) ?> | <a style="color:#fff" href="logout.php">Logout</a></div>
</div>
<div class="container">
  <div class="nav">
    <a href="owner_dashboard.php">Dashboard</a>
    <a href="products.php">Products</a>
    <a href="purchases.php">Purchases</a>
    <a href="reports.php">Reports</a>
    <a href="backup.php">Backups</a>
  </div>

  <div class="grid grid-3">
    <div class="card"><div class="muted">Today's Turnover</div><div class="stats"><?= money((float)$salesToday) ?></div></div>
    <div class="card"><div class="muted">Today's Profit</div><div class="stats"><?= money((float)$profitToday) ?></div></div>
    <div class="card"><div class="muted">Open Shifts</div><div class="stats"><?= (int)$openShifts ?></div></div>
  </div>

  <div class="grid grid-2" style="margin-top:16px;">
    <div class="card">
      <h3>Recent Sales</h3>
      <table class="table">
        <tr><th>Receipt</th><th>Cashier</th><th>Payment</th><th>Total</th><th>Time</th></tr>
        <?php foreach ($recentSales as $sale): ?>
        <tr>
          <td><?= htmlspecialchars($sale['receipt_no']) ?></td>
          <td><?= htmlspecialchars($sale['full_name']) ?></td>
          <td><?= htmlspecialchars($sale['payment_method']) ?></td>
          <td><?= money((float)$sale['total_amount']) ?></td>
          <td><?= htmlspecialchars($sale['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
    <div class="card">
      <h3>Low Stock Alerts</h3>
      <table class="table">
        <tr><th>Product</th><th>Stock</th><th>Alert Level</th></tr>
        <?php foreach ($lowStock as $item): ?>
        <tr>
          <td><?= htmlspecialchars($item['name']) ?></td>
          <td><?= (float)$item['stock_qty'] ?></td>
          <td><?= (float)$item['low_stock_alert'] ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </div>
</div>
</body>
</html>
