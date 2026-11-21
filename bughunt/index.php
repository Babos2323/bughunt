<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login'] ?? '');
    $pass  = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE login = :l');
    $stmt->execute([':l' => $login]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['login']   = $user['login'];
        header('Location: bug_list.php');
        exit;
    }
    $error = 'Неверный логин или пароль';
}
?>
<!DOCTYPE html>
<html lang="ru">
<head><meta charset="UTF-8"><title>Вход — BugHunter</title><link rel="stylesheet" href="style.css"></head>
<body>
<div class="container">
    <h1>BugHunter</h1>
    <h2>Вход в систему</h2>
    <?php if ($error): ?><p class="error"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <form method="POST">
        <label>Логин<br><input name="login" required autofocus></label>
        <label>Пароль<br><input name="password" type="password" required></label>
        <button type="submit">Войти</button>
    </form>
    <p class="hint">Тестовые пользователи: customer, tester, developer, admin (пароль 12345)</p>
</div>
</body>
</html>