<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['cashier', 'owner']);

if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
$message = '';
$currentShift = currentShift($pdo, (int)$_SESSION['user']['id']);
$products = fetchProducts($pdo);

if (isPost()) {
    $action = $_POST['action'] ?? '';

    if ($action === 'open_shift') {
        $openingCash = (float)($_POST['opening_cash'] ?? 0);
        if (!$currentShift) {
            $stmt = $pdo->prepare('INSERT INTO shifts (cashier_id, opening_cash, status) VALUES (?, ?, "open")');
            $stmt->execute([$_SESSION['user']['id'], $openingCash]);
            auditLog($pdo, (int)$_SESSION['user']['id'], 'OPEN_SHIFT', 'Opening cash: ' . $openingCash);
            $message = 'Shift opened successfully.';
            $currentShift = currentShift($pdo, (int)$_SESSION['user']['id']);
        }
    }

    if ($action === 'add_to_cart' && $currentShift) {
        $productId = (int)$_POST['product_id'];
        foreach ($products as $product) {
            if ((int)$product['id'] === $productId) {
                if (!isset($_SESSION['cart'][$productId])) {
                    $_SESSION['cart'][$productId] = ['name' => $product['name'], 'price' => (float)$product['selling_price'], 'cost' => (float)$product['cost_price'], 'qty' => 0, 'barcode' => $product['barcode']];
                }
                $_SESSION['cart'][$productId]['qty'] += 1;
                $message = $product['name'] . ' added to cart.';
                break;
            }
        }
    }

    if ($action === 'update_qty') {
        $productId = (int)$_POST['product_id'];
        $qty = max(0, (int)$_POST['qty']);
        if ($qty === 0) unset($_SESSION['cart'][$productId]);
        elseif (isset($_SESSION['cart'][$productId])) $_SESSION['cart'][$productId]['qty'] = $qty;
    }

    if ($action === 'hold_sale') {
        $payload = json_encode($_SESSION['cart']);
        $stmt = $pdo->prepare('INSERT INTO held_sales (cashier_id, cart_data) VALUES (?, ?)');
        $stmt->execute([$_SESSION['user']['id'], $payload]);
        auditLog($pdo, (int)$_SESSION['user']['id'], 'HOLD_SALE', 'Sale placed on hold');
        $_SESSION['cart'] = [];
        $message = 'Sale placed on hold.';
    }

    if ($action === 'resume_sale') {
        $heldId = (int)$_POST['held_id'];
        $stmt = $pdo->prepare('SELECT * FROM held_sales WHERE id=? AND cashier_id=?');
        $stmt->execute([$heldId, $_SESSION['user']['id']]);
        $held = $stmt->fetch();
        if ($held) {
            $_SESSION['cart'] = json_decode($held['cart_data'], true) ?: [];
            $pdo->prepare('DELETE FROM held_sales WHERE id=?')->execute([$heldId]);
            $message = 'Held sale resumed.';
        }
    }

    if ($action === 'complete_sale' && $currentShift && !empty($_SESSION['cart'])) {
        $paymentMethod = $_POST['payment_method'] ?? 'cash';
        $amountTendered = (float)($_POST['amount_tendered'] ?? 0);
        $discount = (float)($_POST['discount'] ?? 0);
        $subtotal = 0; $totalCost = 0;
        foreach ($_SESSION['cart'] as $id => $item) {
            $subtotal += $item['price'] * $item['qty'];
            $totalCost += $item['cost'] * $item['qty'];
        }
        $total = max(0, $subtotal - $discount);
        $change = max(0, $amountTendered - $total);
        $receiptNo = 'PUB-' . date('YmdHis');

        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO sales (receipt_no, cashier_id, shift_id, subtotal, discount_amount, total_amount, total_cost, amount_tendered, change_due, payment_method, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, "completed")');
            $stmt->execute([$receiptNo, $_SESSION['user']['id'], $currentShift['id'], $subtotal, $discount, $total, $totalCost, $amountTendered, $change, $paymentMethod]);
            $saleId = (int)$pdo->lastInsertId();

            $itemStmt = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, qty, unit_price, unit_cost, line_total) VALUES (?, ?, ?, ?, ?, ?)');
            $stockStmt = $pdo->prepare('UPDATE products SET stock_qty = stock_qty - ? WHERE id = ?');
            foreach ($_SESSION['cart'] as $productId => $item) {
                $lineTotal = $item['qty'] * $item['price'];
                $itemStmt->execute([$saleId, $productId, $item['qty'], $item['price'], $item['cost'], $lineTotal]);
                $stockStmt->execute([$item['qty'], $productId]);
            }
            auditLog($pdo, (int)$_SESSION['user']['id'], 'COMPLETE_SALE', 'Receipt ' . $receiptNo);
            $pdo->commit();
            $_SESSION['cart'] = [];
            header('Location: receipt.php?sale_id=' . $saleId);
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            $message = 'Sale failed: ' . $e->getMessage();
        }
    }
}

$heldSales = $pdo->prepare('SELECT * FROM held_sales WHERE cashier_id=? ORDER BY id DESC');
$heldSales->execute([$_SESSION['user']['id']]);
$heldSales = $heldSales->fetchAll();
$subtotal = 0;
foreach ($_SESSION['cart'] as $item) $subtotal += $item['price'] * $item['qty'];
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Cashier POS</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="header">
  <div class="brand"><img src="../assets/logo.png" alt="Logo"><span>PUB POS Cashier</span></div>
  <div><?= htmlspecialchars($_SESSION['user']['name']) ?> | <a style="color:#fff" href="logout.php">Logout</a></div>
</div>
<div class="container">
  <?php if ($message): ?><div class="notice success"><?= htmlspecialchars($message) ?></div><?php endif; ?>

  <?php if (!$currentShift): ?>
    <div class="card" style="max-width:480px;">
      <h2>Open Shift</h2>
      <p class="muted">Enter opening cash before trading starts.</p>
      <form method="post">
        <input type="hidden" name="action" value="open_shift">
        <label>Opening Cash</label>
        <input type="number" step="0.01" name="opening_cash" required>
        <br><br>
        <button class="green">Open Shift</button>
      </form>
    </div>
  <?php else: ?>
  <div class="grid grid-2">
    <div class="card">
      <h2>Fast POS Selling Screen</h2>
      <div class="products">
        <?php foreach ($products as $product): ?>
          <div class="product">
            <h4><?= htmlspecialchars($product['name']) ?></h4>
            <div class="muted">Barcode: <?= htmlspecialchars($product['barcode']) ?></div>
            <div><strong><?= money((float)$product['selling_price']) ?></strong></div>
            <div class="muted">Stock: <?= (float)$product['stock_qty'] ?></div>
            <form method="post" style="margin-top:10px;">
              <input type="hidden" name="action" value="add_to_cart">
              <input type="hidden" name="product_id" value="<?= (int)$product['id'] ?>">
              <button type="submit">Add</button>
            </form>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card">
      <h2>Current Cart</h2>
      <?php if (empty($_SESSION['cart'])): ?>
        <p class="muted">No items added yet.</p>
      <?php endif; ?>
      <?php foreach ($_SESSION['cart'] as $productId => $item): ?>
        <div class="cart-row">
          <div>
            <strong><?= htmlspecialchars($item['name']) ?></strong><br>
            <span class="muted"><?= money((float)$item['price']) ?> each</span>
          </div>
          <div class="right">
            <form method="post" style="display:flex; gap:8px; align-items:center;">
              <input type="hidden" name="action" value="update_qty">
              <input type="hidden" name="product_id" value="<?= (int)$productId ?>">
              <input type="number" name="qty" value="<?= (int)$item['qty'] ?>" min="0" style="width:80px;">
              <button>Update</button>
            </form>
          </div>
        </div>
      <?php endforeach; ?>
      <h3 class="right">Subtotal: <?= money((float)$subtotal) ?></h3>
      <form method="post">
        <input type="hidden" name="action" value="complete_sale">
        <label>Discount</label>
        <input type="number" step="0.01" name="discount" value="0">
        <br><br>
        <label>Payment Method</label>
        <select name="payment_method">
          <option value="cash">Cash</option>
          <option value="card">Card</option>
          <option value="eft">EFT</option>
          <option value="split">Split Payment</option>
        </select>
        <br><br>
        <label>Amount Tendered</label>
        <input type="number" step="0.01" name="amount_tendered" value="0">
        <br><br>
        <div class="actions">
          <button class="green">Complete Sale</button>
        </div>
      </form>
      <br>
      <form method="post">
        <input type="hidden" name="action" value="hold_sale">
        <button type="submit" class="secondary">Put Sale On Hold</button>
      </form>
      <hr style="margin:18px 0; border:none; border-top:1px solid #e5e7eb;">
      <h3>Held Sales</h3>
      <?php foreach ($heldSales as $held): ?>
        <form method="post" style="margin-bottom:10px;">
          <input type="hidden" name="action" value="resume_sale">
          <input type="hidden" name="held_id" value="<?= (int)$held['id'] ?>">
          <button type="submit">Resume Held Sale #<?= (int)$held['id'] ?></button>
        </form>
      <?php endforeach; ?>
      <hr style="margin:18px 0; border:none; border-top:1px solid #e5e7eb;">
      <h3>Calculator</h3>
      <input id="calc_display" readonly value="0">
      <div class="products" style="margin-top:10px;">
        <?php foreach (['7','8','9','/','4','5','6','*','1','2','3','-','0','.','=','+'] as $key): ?>
          <button type="button" onclick="calcPress('<?= $key ?>')"><?= $key ?></button>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
  <?php endif; ?>
</div>
<script>
let calc = '';
function calcPress(key){
  const display = document.getElementById('calc_display');
  if(key === '='){ try { calc = String(eval(calc || '0')); } catch(e){ calc = 'Error'; } }
  else { calc = calc === 'Error' ? '' : calc; calc += key; }
  display.value = calc || '0';
}
</script>
</body>
</html>
