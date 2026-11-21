<?php
require_once __DIR__ . '/bootstrap.php';

$dbFile = __DIR__ . '/bughunter.db';
if (file_exists($dbFile)) {
    unlink($dbFile); // пересоздать с нуля
}

$pdo = new PDO('sqlite:' . $dbFile);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$pdo->exec("
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    login TEXT UNIQUE NOT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ('Customer','Tester','Developer','Admin'))
);

CREATE TABLE bugs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    description TEXT,
    status TEXT NOT NULL DEFAULT 'New' CHECK(status IN ('New','Review','Confirmed','Rejected','In Progress','Fixed','Closed')),
    created_at TEXT DEFAULT (datetime('now'))
);

CREATE TABLE bug_history (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bug_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    old_status TEXT NOT NULL,
    new_status TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (bug_id) REFERENCES bugs(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    bug_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    text TEXT NOT NULL,
    created_at TEXT DEFAULT (datetime('now')),
    FOREIGN KEY (bug_id) REFERENCES bugs(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);
");

$users = [
    ['customer',  'Customer'],
    ['tester',    'Tester'],
    ['developer', 'Developer'],
    ['admin',     'Admin'],
];
$stmt = $pdo->prepare('INSERT INTO users (login, password_hash, role) VALUES (:l, :h, :r)');
foreach ($users as [$login, $role]) {
    $stmt->execute([
        ':l' => $login,
        ':h' => password_hash('12345', PASSWORD_DEFAULT),
        ':r' => $role,
    ]);
}

$pdo->exec("
INSERT INTO bugs (title, description, status) VALUES
('Пустое поле пароля', 'При пустом пароле страница падает с 500', 'New'),
('Кнопка не работает', 'Кнопка \"Сохранить\" не реагирует в Firefox', 'Review'),
('Опечатка в заголовке', 'На главной странице \"Добро пожаловать\"', 'Confirmed');
");

echo "База создана. Пользователи: customer / tester / developer / admin, пароль 12345<br>";
echo '<a href="index.php">Перейти к входу</a>';