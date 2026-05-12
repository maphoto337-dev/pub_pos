<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/layout.php';
require_login('manager');

if (is_post()) {
    exec_stmt('INSERT INTO users (full_name, username, password_hash, role, active) VALUES (?, ?, ?, ?, 1)', [
        trim($_POST['full_name'] ?? ''),
        trim($_POST['username'] ?? ''),
        password_hash($_POST['password'] ?? '123456', PASSWORD_DEFAULT),
        trim($_POST['role'] ?? 'cashier'),
    ]);
    flash('User added successfully.');
    redirect_to('/manager/users.php');
}
$users = fetch_all('SELECT id, full_name, username, role, active FROM users ORDER BY id DESC');
layout_header('Users');
?>
<?php if ($msg = flash()): ?><div class="flash"><?= e($msg) ?></div><?php endif; ?>
<div class="grid grid-2">
    <div class="card">
        <h3>Create user</h3>
        <form method="post" class="grid">
            <div><label>Full name</label><input name="full_name" required></div>
            <div><label>Username</label><input name="username" required></div>
            <div><label>Password</label><input name="password" type="password" required></div>
            <div><label>Role</label><select name="role"><option value="cashier">Cashier</option><option value="manager">Manager</option></select></div>
            <button type="submit">Create user</button>
        </form>
    </div>
    <div class="card">
        <h3>System users</h3>
        <table><tr><th>Name</th><th>Username</th><th>Role</th></tr>
        <?php foreach ($users as $u): ?><tr><td><?= e($u['full_name']) ?></td><td><?= e($u['username']) ?></td><td><?= e($u['role']) ?></td></tr><?php endforeach; ?>
        </table>
    </div>
</div>
<?php layout_footer(); ?>
