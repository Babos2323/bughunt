<?php
require_once __DIR__ . '/bootstrap.php';

$dbPath = __DIR__ . '/bughunter.db';

try {
    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->exec('PRAGMA foreign_keys = ON');
} catch (Throwable $e) {
    http_response_code(500);
    exit('Ошибка подключения к БД: ' . htmlspecialchars($e->getMessage()));
}