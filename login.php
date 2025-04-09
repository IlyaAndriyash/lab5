<?php
header('Content-Type: text/html; charset=UTF-8');

session_start();

if (!empty($_SESSION['login'])) {
    if (isset($_POST['logout'])) {
        session_destroy();
        setcookie(session_name(), '', time() - 3600);
        header('Location: index.php');
        exit();
    }
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Вход</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .login-container { max-width: 400px; margin: 50px auto; padding: 20px; background-color: #fff; border: 1px solid #ddd; border-radius: 5px; }
        input[type="text"], input[type="password"] { width: 90%; padding: 10px; margin: 10px 0; }
        input[type="submit"] { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .error { color: red; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Вход</h2>
        <form action="" method="post">
            <input name="login" type="text" placeholder="Логин" required />
            <input name="pass" type="password" placeholder="Пароль" required />
            <input type="submit" value="Войти" />
        </form>
    </div>
</body>
</html>
<?php
} else {
    $db = new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE login = ?");
    $stmt->execute([$_POST['login']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && $user['password_hash'] === md5($_POST['pass'])) {
        $_SESSION['login'] = $_POST['login'];
        $_SESSION['uid'] = $user['id'];
        header('Location: index.php');
    } else {
        echo '<div class="error">Неверный логин или пароль</div>';
    }
}
?>
