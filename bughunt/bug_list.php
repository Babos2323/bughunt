<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_login();

$user = current_user();
$bugs = $pdo->query('SELECT * FROM bugs ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Список ошибок</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
    <div class="topbar">
        <span>Вы: <strong><?= htmlspecialchars($user['login']) ?></strong> (<?= htmlspecialchars($user['role']) ?>)</span>
        <a href="logout.php">Выйти</a>
    </div>
    <h1>Список ошибок</h1>
    <table>
        <tr><th>ID</th><th>Заголовок</th><th>Статус</th><th>Создана</th><th></th></tr>
        <?php foreach ($bugs as $b): ?>
        <tr>
            <td><?= (int)$b['id'] ?></td>
            <td><?= htmlspecialchars($b['title']) ?></td>
            <td><span class="status s-<?= str_replace(' ', '_', $b['status']) ?>"><?= htmlspecialchars($b['status']) ?></span></td>
            <td><?= htmlspecialchars($b['created_at']) ?></td>
            <td><a href="bug_view.php?id=<?= (int)$b['id'] ?>">Открыть</a></td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>
</body>
</html>