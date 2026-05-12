<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_login('manager');

if (is_post()) {
    exec_stmt('INSERT INTO products (name, category, barcode, cost_price, selling_price, stock_qty, min_stock) VALUES (?, ?, ?, ?, ?, ?, ?)', [
        trim($_POST['name'] ?? ''),
        trim($_POST['category'] ?? ''),
        trim($_POST['barcode'] ?? ''),
        (float)($_POST['cost_price'] ?? 0),
        (float)($_POST['selling_price'] ?? 0),
        (int)($_POST['stock_qty'] ?? 0),
        (int)($_POST['min_stock'] ?? 0),
    ]);
    flash('Product added successfully.');
    redirect_to('/manager/products.php');
}
$products = fetch_all('SELECT * FROM products ORDER BY id DESC');
layout_header('Products');
?>
<?php if ($msg = flash()): ?><div class="flash"><?= e($msg) ?></div><?php endif; ?>
<div class="grid grid-2">
    <div class="card">
        <h3>Add product</h3>
        <form method="post" class="grid">
            <div><label>Name</label><input name="name" required></div>
            <div><label>Category</label><input name="category" required></div>
            <div><label>Barcode</label><input name="barcode"></div>
            <div><label>Cost price</label><input name="cost_price" type="number" step="0.01" required></div>
            <div><label>Selling price</label><input name="selling_price" type="number" step="0.01" required></div>
            <div><label>Opening stock</label><input name="stock_qty" type="number" required></div>
            <div><label>Minimum stock</label><input name="min_stock" type="number" value="5" required></div>
            <button type="submit">Save product</button>
        </form>
    </div>
    <div class="card">
        <h3>Products</h3>
        <table>
            <tr><th>Name</th><th>Category</th><th>Stock</th><th>Selling</th></tr>
            <?php foreach ($products as $p): ?>
            <tr>
                <td><?= e($p['name']) ?></td>
                <td><?= e($p['category']) ?></td>
                <td><?= (int)$p['stock_qty'] ?></td>
                <td>R <?= number_format((float)$p['selling_price'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
<?php layout_footer(); ?>
