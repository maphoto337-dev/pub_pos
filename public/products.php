<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner']);

if (isPost()) {
    $stmt = $pdo->prepare('INSERT INTO products (name, barcode, cost_price, selling_price, stock_qty, low_stock_alert) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
      trim($_POST['name']), trim($_POST['barcode']), (float)$_POST['cost_price'], (float)$_POST['selling_price'], (float)$_POST['stock_qty'], (float)$_POST['low_stock_alert']
    ]);
    auditLog($pdo, (int)$_SESSION['user']['id'], 'ADD_PRODUCT', trim($_POST['name']));
}
$products = $pdo->query('SELECT * FROM products ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Products</title><link rel="stylesheet" href="style.css"></head><body>
<div class="header"><div class="brand"><img src="../assets/logo.png"><span>Product Management</span></div><div><a style="color:#fff" href="owner_dashboard.php">Dashboard</a></div></div>
<div class="container grid grid-2">
<div class="card"><h2>Add Product</h2><form method="post">
<input name="name" placeholder="Product name" required><br><br>
<input name="barcode" placeholder="Barcode"><br><br>
<input name="cost_price" type="number" step="0.01" placeholder="Cost price" required><br><br>
<input name="selling_price" type="number" step="0.01" placeholder="Selling price" required><br><br>
<input name="stock_qty" type="number" step="0.01" placeholder="Stock qty" required><br><br>
<input name="low_stock_alert" type="number" step="0.01" placeholder="Low stock alert" required><br><br>
<button>Add Product</button></form></div>
<div class="card"><h2>Product List</h2><table class="table"><tr><th>Name</th><th>Stock</th><th>Sell</th></tr><?php foreach($products as $p): ?><tr><td><?= htmlspecialchars($p['name']) ?></td><td><?= (float)$p['stock_qty'] ?></td><td><?= money((float)$p['selling_price']) ?></td></tr><?php endforeach; ?></table></div>
</div></body></html>
