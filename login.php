<?php
// Установка заголовков безопасности
header('Content-Type: text/html; charset=UTF-8');
header("Content-Security-Policy: default-src 'self'; script-src 'self'");
header("X-Content-Type-Options: nosniff");
header("X-Frame-Options: DENY");

// Настройки сессии
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'use_strict_mode' => true
]);

// Генерация CSRF-токена
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Выход из системы
if (!empty($_SESSION['login']) && isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    setcookie(session_name(), '', time() - 3600);
    header('Location: index.php');
    exit();
}

// Перенаправление если уже авторизован
if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

// Обработка GET-запроса (показ формы)
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
            margin: 0;
            padding: 20px;
        }
        .login-container {
            max-width: 400px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h2 {
            text-align: center;
            margin-bottom: 20px;
        }
        input[type="text"], input[type="password"] {
            width: 90%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        input[type="submit"] {
            width: 100%;
            background-color: #007bff;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            margin-bottom: 15px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Вход</h2>
        <form action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
            <input name="login" type="text" placeholder="Логин" required />
            <input name="pass" type="password" placeholder="Пароль" required />
            <input type="submit" value="Войти" />
        </form>
    </div>
</body>
</html>
<?php
} else {
    // Обработка POST-запроса (попытка входа)
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Неверный CSRF-токен');
    }

    try {
        $db = new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_EMULATE_PREPARES => false
        ]);

        $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE login = ?");
        $stmt->execute([$_POST['login']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($_POST['pass'], $user['password_hash'])) {
            $_SESSION['login'] = $_POST['login'];
            $_SESSION['uid'] = $user['id'];
            header('Location: index.php');
        } else {
            echo '<div class="error">Неверный логин или пароль</div>';
        }
    } catch (PDOException $e) {
        error_log('Login error: ' . $e->getMessage());
        echo '<div class="error">Ошибка при входе в систему</div>';
    }
}
?>
