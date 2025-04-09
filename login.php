<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

if (!empty($_SESSION['login'])) {
    if (!empty($_GET['logout'])) {
        session_destroy();
        header('Location: login.php');
        exit();
    }
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    ?>
    <!DOCTYPE html>
    <html lang="ru">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Вход в систему</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                background-color: #f0f0f0;
                display: flex;
                justify-content: center;
                align-items: center;
                height: 100vh;
                margin: 0;
            }
            .login-container {
                background-color: white;
                padding: 20px;
                border-radius: 8px;
                box-shadow: 0 0 10px rgba(0,0,0,0.1);
                width: 300px;
            }
            .login-container h2 {
                text-align: center;
                margin-bottom: 20px;
            }
            .login-container input {
                width: 100%;
                padding: 10px;
                margin-bottom: 15px;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-sizing: border-box;
            }
            .login-container button {
                width: 100%;
                padding: 10px;
                background-color: #007bff;
                color: white;
                border: none;
                border-radius: 4px;
                cursor: pointer;
            }
            .login-container button:hover {
                background-color: #0056b3;
            }
        </style>
    </head>
    <body>
        <div class="login-container">
            <h2>Вход в систему</h2>
            <form action="login.php" method="POST">
                <input name="login" placeholder="Логин" required>
                <input name="pass" placeholder="Пароль" type="password" required>
                <button type="submit">Войти</button>
            </form>
        </div>
    </body>
    </html>
    <?php
} else {
    $login = $_POST['login'];
    $pass = $_POST['pass'];

    try {
        $pdo = new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $stmt = $pdo->prepare("SELECT id, password FROM users WHERE login = ?");
        $stmt->execute([$login]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($pass, $user['password'])) {
            $_SESSION['login'] = $login;
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
        } else {
            echo "<div class='login-container'><p style='color:red'>Неверный логин или пароль.</p>";
            echo "<a href='login.php'>Попробовать снова</a></div>";
        }
    } catch (PDOException $e) {
        echo "<div class='login-container'><p style='color:red'>Ошибка: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<a href='login.php'>Попробовать снова</a></div>";
    }
}
