<?php
$config = require __DIR__ . '/../config.php';

if (!function_exists('app_config')) {
    function app_config(?string $key = null) {
        static $configCache = null;
        if ($configCache === null) {
            $configCache = require __DIR__ . '/../config.php';
        }
        if ($key === null) {
            return $configCache;
        }
        return $configCache[$key] ?? null;
    }
}

try {
    $pdo = new PDO(
        'mysql:host=' . app_config('db_host') . ';dbname=' . app_config('db_name') . ';charset=utf8mb4',
        app_config('db_user'),
        app_config('db_pass'),
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . htmlspecialchars($e->getMessage()));
}
