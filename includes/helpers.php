<?php
require_once __DIR__ . '/db.php';

function fetch_all(string $sql, array $params = []): array {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function fetch_one(string $sql, array $params = []): ?array {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row ?: null;
}

function exec_stmt(string $sql, array $params = []): bool {
    global $pdo;
    $stmt = $pdo->prepare($sql);
    return $stmt->execute($params);
}

function db_now(): string {
    return date('Y-m-d H:i:s');
}

function add_stock_movement(int $productId, string $type, int $qtyIn, int $qtyOut, string $notes, int $userId): void {
    $product = fetch_one('SELECT stock_qty FROM products WHERE id = ?', [$productId]);
    $balance = (int)($product['stock_qty'] ?? 0);
    exec_stmt(
        'INSERT INTO stock_movements (product_id, movement_type, qty_in, qty_out, balance_after, notes, user_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [$productId, $type, $qtyIn, $qtyOut, $balance, $notes, $userId, db_now()]
    );
}

function update_product_stock(int $productId, int $delta): void {
    exec_stmt('UPDATE products SET stock_qty = stock_qty + ? WHERE id = ?', [$delta, $productId]);
}
