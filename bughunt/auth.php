<?php
require_once __DIR__ . '/bootstrap.php';

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: index.php');
        exit;
    }
}

function current_user(): array {
    if (empty($_SESSION['user_id'])) {
        return ['id' => 0, 'login' => 'Гость', 'role' => 'Guest'];
    }
    return [
        'id'    => (int)$_SESSION['user_id'],
        'login' => $_SESSION['login'] ?? '',
        'role'  => $_SESSION['role'] ?? '',
    ];
}