<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/helpers.php';

if (is_post()) {
    $portal = $_POST['portal'] ?? '';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $user = fetch_one('SELECT * FROM users WHERE username = ? AND active = 1', [$username]);

    if ($user && password_verify($password, $user['password_hash'])) {
        if (($portal === 'manager' && $user['role'] !== 'manager') || ($portal === 'cashier' && $user['role'] !== 'cashier')) {
            flash('You selected the wrong portal for this account.');
        } else {
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'full_name' => $user['full_name'],
                'username' => $user['username'],
                'role' => $user['role'],
                'portal' => ucfirst($portal),
            ];
            redirect_to($portal === 'manager' ? '/manager/dashboard.php' : '/cashier/sell.php');
            exit;
        }
    } else {
        flash('Invalid login details.');
    }
}
$message = flash();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pub POS Login</title>
    <link rel="stylesheet" href="<?= e(base_url('/assets/style.css')) ?>">
</head>
<body>
<div class="auth-page">
    <div class="auth-box">
        <div class="auth-grid">
            <div class="hero">
                <span class="pill">2-phase login POS</span>
                <h1>Pub POS for alcohol sales</h1>
                <p>Built for immediate sales, stock control, receipt-assisted stock capture, damages, and role-based access.</p>
                <p class="muted">Default demo logins:<br>Manager: manager / admin123<br>Cashier: cashier / cash123</p>
            </div>
            <div class="form-side">
                <h2>Sign in</h2>
                <?php if ($message): ?><div class="flash"><?= e($message) ?></div><?php endif; ?>
                <form method="post">
                    <div class="card">
                        <label>Select portal</label>
                        <select name="portal" required>
                            <option value="manager">Manager / Owner Portal</option>
                            <option value="cashier">Cashier Portal</option>
                        </select>
                        <br>
                        <label>Username</label>
                        <input name="username" required>
                        <br>
                        <label>Password</label>
                        <input type="password" name="password" required>
                        <br>
                        <button type="submit">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</body>
</html>
