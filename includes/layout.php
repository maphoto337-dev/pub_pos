<?php
require_once __DIR__ . '/auth.php';
function layout_header(string $title): void {
    $user = current_user();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?></title>
        <link rel="stylesheet" href="<?= e(base_url('/assets/style.css')) ?>">
    </head>
    <body>
    <div class="shell">
        <aside class="sidebar">
            <div>
                <h2>Pub POS</h2>
                <p class="muted"><?= e($user['portal'] ?? 'Portal') ?> · <?= e($user['full_name'] ?? '') ?></p>
            </div>
            <nav>
                <?php if (($user['role'] ?? '') === 'manager'): ?>
                    <a href="<?= e(base_url('/manager/dashboard.php')) ?>">Dashboard</a>
                    <a href="<?= e(base_url('/manager/products.php')) ?>">Products</a>
                    <a href="<?= e(base_url('/manager/stock_receive.php')) ?>">Receive Stock</a>
                    <a href="<?= e(base_url('/manager/damages.php')) ?>">Damages</a>
                    <a href="<?= e(base_url('/manager/reports.php')) ?>">Reports</a>
                    <a href="<?= e(base_url('/manager/users.php')) ?>">Users</a>
                <?php else: ?>
                    <a href="<?= e(base_url('/cashier/sell.php')) ?>">Sell</a>
                    <a href="<?= e(base_url('/cashier/shift.php')) ?>">Shift</a>
                    <a href="<?= e(base_url('/cashier/history.php')) ?>">My Sales</a>
                <?php endif; ?>
                <a href="<?= e(base_url('/logout.php')) ?>">Logout</a>
            </nav>
        </aside>
        <main class="main">
            <header class="topbar">
                <h1><?= e($title) ?></h1>
            </header>
    <?php
}

function layout_footer(): void {
    ?>
        </main>
    </div>
    </body>
    </html>
    <?php
}
