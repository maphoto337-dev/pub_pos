<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
if (!empty($_SESSION['user'])) {
    auditLog($pdo, (int)$_SESSION['user']['id'], 'LOGOUT', 'User logged out');
}
session_destroy();
header('Location: index.php');
exit;
