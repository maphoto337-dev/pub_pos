<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
if (isPost()) {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE username = ? AND active = 1 LIMIT 1');
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'name' => $user['full_name'],
            'username' => $user['username'],
            'role' => $user['role'],
            'pin_code' => $user['pin_code'],
        ];
        auditLog($pdo, (int)$user['id'], 'LOGIN', 'User logged in');
        header('Location: ' . ($user['role'] === 'owner' ? 'owner_dashboard.php' : 'cashier_pos.php'));
        exit;
    }
    $error = 'Invalid login details.';
}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title><?= appName() ?> Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="login-wrap">
  <div class="card login-card">
    <div class="brand" style="justify-content:center; margin-bottom:18px; color:#262b49;">
      <img src="../assets/logo.png" alt="PUB Logo">
      <span>PUB POS</span>
    </div>
    <h2>Secure Login</h2>
    <p class="muted">Owner and cashier accounts have separate access levels.</p>
    <?php if ($error): ?><div class="notice error"><?= htmlspecialchars($error) ?></div><?php endif; ?>
    <form method="post">
      <label>Username</label>
      <input name="username" required>
      <br><br>
      <label>Password</label>
      <input type="password" name="password" required>
      <br><br>
      <button type="submit">Log In</button>
    </form>
    <p class="muted" style="margin-top:14px;">Demo owner: <strong>owner</strong> / <strong>Owner@123</strong><br>Demo cashier: <strong>cashier1</strong> / <strong>Cashier@123</strong></p>
  </div>
</div>
</body>
</html>
