<?php
header('Content-Type: text/html; charset=UTF-8');
$session_started = false;
if (!empty($_COOKIE[session_name()]) && session_start()) {
    $session_started = true;
    if (!empty($_SESSION['login'])) {
        header('Location: ./');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
?>
<form action="" method="post">
    <label>Логин: <input name="login"></label><br>
    <label>Пароль: <input name="pass" type="password"></label><br>
    <input type="submit" value="Войти">
</form>
<?php
} else {
    include 'db.php';
    $login = $_POST['login'] ?? '';
    $pass = $_POST['pass'] ?? '';

    $stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['password_hash'])) {
        if (!$session_started) {
            session_start();
        }
        $_SESSION['login'] = $login;
        $_SESSION['uid'] = $user['id'];
        header('Location: ./');
    } else {
        echo "Неверный логин или пароль.";
    }
}
