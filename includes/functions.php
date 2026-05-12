<?php
require_once __DIR__ . '/../config/database.php';

function appName(): string { return 'PUB POS'; }

function money(float $amount): string {
    return 'R ' . number_format($amount, 2);
}

function auditLog(PDO $pdo, ?int $userId, string $action, string $details = ''): void {
    $stmt = $pdo->prepare('INSERT INTO audit_logs (user_id, action, details) VALUES (?, ?, ?)');
    $stmt->execute([$userId, $action, $details]);
}

function currentShift(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare("SELECT * FROM shifts WHERE cashier_id = ? AND status = 'open' ORDER BY id DESC LIMIT 1");
    $stmt->execute([$userId]);
    return $stmt->fetch() ?: null;
}

function lowStockItems(PDO $pdo): array {
    $stmt = $pdo->query('SELECT * FROM products WHERE stock_qty <= low_stock_alert ORDER BY stock_qty ASC LIMIT 10');
    return $stmt->fetchAll();
}

function fetchProducts(PDO $pdo): array {
    $stmt = $pdo->query("SELECT * FROM products WHERE active = 1 ORDER BY name ASC");
    return $stmt->fetchAll();
}
