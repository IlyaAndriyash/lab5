<?php
session_start();
if (!empty($_SESSION['login'])) {
    header('Location: form.php');
    exit();
}

$errors = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';

    include 'db.php'; // Подключение к базе

    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['login'] = $login;
        $_SESSION['uid'] = $user['id'];
        header('Location: form.php');
        exit();
    } else {
        $errors = 'Неверный логин или пароль.';
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
</head>
<body>
    <h2>Вход</h2>
    <?php if (!empty($errors)) echo "<p style='color:red;'>$errors</p>"; ?>
    <form method="POST">
        <label>Логин: <input name="login" type="text" required></label><br>
        <label>Пароль: <input name="password" type="password" required></label><br>
        <input type="submit" value="Войти">
    </form>
</body>
</html>
