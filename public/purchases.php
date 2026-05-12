<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['owner']);

if (isPost()) {
    $productId = (int)$_POST['product_id'];
    $supplier = trim($_POST['supplier_name']);
    $qty = (float)$_POST['qty'];
    $unitCost = (float)$_POST['unit_cost'];
    $pdo->beginTransaction();
    try {
      $supplierStmt = $pdo->prepare('INSERT INTO suppliers (name) VALUES (?)');
      $supplierStmt->execute([$supplier]);
      $supplierId = (int)$pdo->lastInsertId();
      $purchaseStmt = $pdo->prepare('INSERT INTO purchases (supplier_id, product_id, qty, unit_cost, total_cost) VALUES (?, ?, ?, ?, ?)');
      $purchaseStmt->execute([$supplierId, $productId, $qty, $unitCost, $qty * $unitCost]);
      $stockStmt = $pdo->prepare('UPDATE products SET stock_qty = stock_qty + ?, cost_price = ? WHERE id = ?');
      $stockStmt->execute([$qty, $unitCost, $productId]);
      auditLog($pdo, (int)$_SESSION['user']['id'], 'RECORD_PURCHASE', 'Product ID ' . $productId . ', qty ' . $qty);
      $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); die($e->getMessage()); }
}
$products = fetchProducts($pdo);
$purchases = $pdo->query('SELECT p.*, pr.name product_name, s.name supplier_name FROM purchases p JOIN products pr ON p.product_id=pr.id JOIN suppliers s ON p.supplier_id=s.id ORDER BY p.id DESC LIMIT 20')->fetchAll();
?>
<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Purchases</title><link rel="stylesheet" href="style.css"></head><body>
<div class="header"><div class="brand"><img src="../assets/logo.png"><span>Purchases & Suppliers</span></div><div><a style="color:#fff" href="owner_dashboard.php">Dashboard</a></div></div>
<div class="container grid grid-2">
<div class="card"><h2>Record New Stock Arrival</h2><form method="post">
<select name="product_id"><?php foreach($products as $p): ?><option value="<?= (int)$p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option><?php endforeach; ?></select><br><br>
<input name="supplier_name" placeholder="Supplier name" required><br><br>
<input name="qty" type="number" step="0.01" placeholder="Quantity" required><br><br>
<input name="unit_cost" type="number" step="0.01" placeholder="Unit cost" required><br><br>
<button>Save Purchase</button></form></div>
<div class="card"><h2>Recent Purchases</h2><table class="table"><tr><th>Product</th><th>Supplier</th><th>Qty</th><th>Total</th></tr><?php foreach($purchases as $r): ?><tr><td><?= htmlspecialchars($r['product_name']) ?></td><td><?= htmlspecialchars($r['supplier_name']) ?></td><td><?= (float)$r['qty'] ?></td><td><?= money((float)$r['total_cost']) ?></td></tr><?php endforeach; ?></table></div>
</div></body></html>
