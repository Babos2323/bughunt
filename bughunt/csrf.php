<?php
require_once __DIR__ . '/bootstrap.php';

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function verify_csrf(?string $token): void {
    if (empty($_SESSION['csrf']) || !$token || !hash_equals($_SESSION['csrf'], $token)) {
        http_response_code(403);
        exit('CSRF-токен недействителен');
    }
}