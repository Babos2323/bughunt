<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/csrf.php';

require_login();
verify_csrf($_POST['csrf_token'] ?? '');

$bugId = filter_input(INPUT_POST, 'bug_id', FILTER_VALIDATE_INT);
$text  = trim($_POST['text'] ?? '');

if (!$bugId) { http_response_code(400); exit('Неверный ID ошибки'); }
if ($text === '') { http_response_code(400); exit('Комментарий не может быть пустым'); }

$check = $pdo->prepare('SELECT id FROM bugs WHERE id = :id');
$check->execute([':id' => $bugId]);
if (!$check->fetch()) { http_response_code(404); exit('Ошибка не найдена'); }

$stmt = $pdo->prepare('
    INSERT INTO comments (bug_id, user_id, text, created_at)
    VALUES (:bid, :uid, :txt, datetime("now"))
');
$stmt->execute([
    ':bid' => $bugId,
    ':uid' => $_SESSION['user_id'],
    ':txt' => $text,
]);

header('Location: bug_view.php?id=' . $bugId);
exit;