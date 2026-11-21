<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';
require_login();

$user = current_user();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) { http_response_code(400); exit('Неверный ID'); }

$stmt = $pdo->prepare('SELECT * FROM bugs WHERE id = :id');
$stmt->execute([':id' => $id]);
$bug = $stmt->fetch();
if (!$bug) { http_response_code(404); exit('Ошибка не найдена'); }

// история
$hStmt = $pdo->prepare('
    SELECT h.old_status, h.new_status, h.created_at, u.login AS author
    FROM bug_history h
    JOIN users u ON u.id = h.user_id
    WHERE h.bug_id = :id
    ORDER BY h.created_at ASC, h.id ASC
');
$hStmt->execute([':id' => $id]);
$history = $hStmt->fetchAll();

// комментарии
$cStmt = $pdo->prepare('
    SELECT c.text, c.created_at, u.login AS author
    FROM comments c
    JOIN users u ON u.id = c.user_id
    WHERE c.bug_id = :id
    ORDER BY c.created_at ASC, c.id ASC
');
$cStmt->execute([':id' => $id]);
$comments = $cStmt->fetchAll();

$transitions = [
    'New'         => ['Review'],
    'Review'      => ['Confirmed', 'Rejected'],
    'Confirmed'   => ['In Progress'],
    'In Progress' => ['Fixed'],
    'Fixed'       => ['Closed', 'In Progress'],
    'Rejected'    => [],
    'Closed'      => [],
];
$allowedNext = $transitions[$bug['status']] ?? [];
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Ошибка #<?= (int)$bug['id'] ?></title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
    <div class="topbar">
        <a href="bug_list.php">← К списку</a>
        <span><?= htmlspecialchars($user['login']) ?> (<?= htmlspecialchars($user['role']) ?>) · <a href="logout.php">Выйти</a></span>
    </div>

    <h1>Карточка #<?= (int)$bug['id'] ?> · <?= htmlspecialchars($bug['title']) ?></h1>
    <p><strong>Статус:</strong> <span class="status s-<?= str_replace(' ', '_', $bug['status']) ?>"><?= htmlspecialchars($bug['status']) ?></span></p>
    <p><strong>Описание:</strong> <?= nl2br(htmlspecialchars($bug['description'])) ?></p>

    <h2>История статусов</h2>
    <?php if (!$history): ?>
        <p class="hint">Пока нет изменений.</p>
    <?php else: ?>
        <ul class="history">
        <?php foreach ($history as $h): ?>
            <li>
                <span class="status s-<?= str_replace(' ', '_', $h['old_status']) ?>"><?= htmlspecialchars($h['old_status']) ?></span>
                →
                <span class="status s-<?= str_replace(' ', '_', $h['new_status']) ?>"><?= htmlspecialchars($h['new_status']) ?></span>
                <em>(<?= htmlspecialchars($h['author']) ?>, <?= htmlspecialchars($h['created_at']) ?>)</em>
            </li>
        <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <h2>Обсуждение</h2>
    <?php if (!$comments): ?>
        <p class="hint">Комментариев пока нет.</p>
    <?php else: ?>
        <?php foreach ($comments as $c): ?>
            <div class="comment">
                <div class="comment-head">
                    <strong><?= htmlspecialchars($c['author']) ?></strong>
                    <span class="muted"><?= htmlspecialchars($c['created_at']) ?></span>
                </div>
                <p><?= nl2br(htmlspecialchars($c['text'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

    <form method="POST" action="comment_add.php" class="comment-form">
        <input type="hidden" name="bug_id" value="<?= (int)$bug['id'] ?>">
        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
        <textarea name="text" required placeholder="Введите комментарий..."></textarea>
        <button type="submit">Отправить</button>
    </form>

    <h2>Смена статуса</h2>
    <?php if (!$allowedNext): ?>
        <p class="hint">Из статуса «<?= htmlspecialchars($bug['status']) ?>» переходов нет.</p>
    <?php else: ?>
        <form method="POST" action="bug_status_change.php" class="status-form">
            <input type="hidden" name="bug_id" value="<?= (int)$bug['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <select name="new_status" required>
                <?php foreach ($allowedNext as $s): ?>
                    <option value="<?= htmlspecialchars($s) ?>"><?= htmlspecialchars($s) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit">Изменить</button>
        </form>
    <?php endif; ?>
</div>
</body>
</html>