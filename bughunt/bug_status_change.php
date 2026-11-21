<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

require_login();
verify_csrf($_POST['csrf_token'] ?? '');

$id = filter_input(INPUT_POST, 'bug_id', FILTER_VALIDATE_INT);
$newStatus = $_POST['new_status'] ?? '';
if (!$id || !$newStatus) { http_response_code(400); exit('Неверные параметры'); }

$stmt = $pdo->prepare('SELECT * FROM bugs WHERE id = :id');
$stmt->execute([':id' => $id]);
$bug = $stmt->fetch();
if (!$bug) { http_response_code(404); exit('Ошибка не найдена'); }

$allowed = [
    'New'         => ['Review'],
    'Review'      => ['Confirmed', 'Rejected'],
    'Confirmed'   => ['In Progress'],
    'In Progress' => ['Fixed'],
    'Fixed'       => ['Closed', 'In Progress'],
    'Rejected'    => [],
    'Closed'      => [],
];

$oldStatus = $bug['status'];
if (!in_array($newStatus, $allowed[$oldStatus] ?? [], true)) {
    http_response_code(400); exit('Недопустимый переход статуса');
}

// Права ролей
$roleMatrix = [
    'Tester' => [
        'New'    => ['Review'],
        'Review' => ['Confirmed', 'Rejected'],
        'Fixed'  => ['Closed', 'In Progress'],
    ],
    'Developer' => [
        'Confirmed'   => ['In Progress'],
        'In Progress' => ['Fixed'],
    ],
    'Admin' => $allowed,
    'Customer' => [],
];

$role = $_SESSION['role'] ?? '';
if ($role !== 'Admin') {
    $roleAllowed = $roleMatrix[$role][$oldStatus] ?? [];
    if (!in_array($newStatus, $roleAllowed, true)) {
        http_response_code(403); exit('Нет прав на этот переход');
    }
}

try {
    $pdo->beginTransaction();
    $upd = $pdo->prepare('UPDATE bugs SET status = :new WHERE id = :id');
    $upd->execute([':new' => $newStatus, ':id' => $id]);

    $hist = $pdo->prepare('
        INSERT INTO bug_history (bug_id, user_id, old_status, new_status, created_at)
        VALUES (:bid, :uid, :old, :new, datetime("now"))
    ');
    $hist->execute([
        ':bid' => $id,
        ':uid' => $_SESSION['user_id'],
        ':old' => $oldStatus,
        ':new' => $newStatus,
    ]);
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    exit('Ошибка транзакции: ' . htmlspecialchars($e->getMessage()));
}

header('Location: bug_view.php?id=' . $id);
exit;