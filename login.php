<?php
header('Content-Type: text/html; charset=UTF-8');

session_start();

if (!empty($_SESSION['login'])) { //Проверяет, есть ли в сессии переменная $_SESSION['login'], то есть авторизован ли пользователь.
                                 //Если пользователь уже вошел в систему (есть активная сессия), выполняется код внутри этого условия.
    if (isset($_POST['logout'])) {
        //Проверяет, отправлена ли форма с параметром logout через POST-запрос.
        //Это обычно означает, что пользователь нажал кнопку "Выйти" или инициировал действие для завершения сессии.
        session_destroy(); 
        //Уничтожает текущую сессию, удаляя все данные, 
        //связанные с ней. Это завершает авторизацию пользователя.
        setcookie(session_name(), '', time() - 3600); //Удаляет cookie сессии
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

    $stmt = $db->prepare("SELECT id, password_hash FROM users WHERE login = ?"); //ищет запись в таблице users, 
                                                            //где поле login совпадает с переданным значением.
    $stmt->execute([$_POST['login']]); //Выполняет подготовленный запрос, заменяя ?
                                        //значением из $_POST['login'] (логин, введенный пользователем в форме).
                                        //Запрос ищет пользователя с указанным логином в базе данных.
    $user = $stmt->fetch(PDO::FETCH_ASSOC); //Результат: $user содержит данные пользователя (если он найден) или false (если пользователя с таким логином нет).

    if ($user && $user['password_hash'] === md5($_POST['pass'])) { //Проверяет два условия:
                      //$user: Убедиться, что пользователь найден ( $user не false).
                      //$user['password_hash'] === md5($_POST['pass']): Сравнивает хеш пароля из базы данных с хешем введенного пароля.
                       //$_POST['pass'] — пароль, введенный пользователем в форме (поле <input name="pass">).
                      //md5($_POST['pass']) — вычисляет MD5-хеш введенного пароля.
                    //Сравнивается с $user['password_hash'], который хранит MD5-хеш пароля, сохраненный при регистрации.
        $_SESSION['login'] = $_POST['login'];//Устанавливает переменную сессии $_SESSION['login'], сохраняя введенный логин.
                    //Это означает, что пользователь теперь считается авторизованным, и его логин будет доступен в других частях приложения (например, в index.php).
                                                                  
        $_SESSION['uid'] = $user['id'];  //Устанавливает переменную сессии $_SESSION['uid'], сохраняя идентификатор пользователя (id) из базы данных.
        //Это полезно для идентификации пользователя в базе данных при последующих запросах (например, для извлечения данных формы в index.php).
        header('Location: index.php');
    } else {
        echo '<div class="error">Неверный логин или пароль</div>';
    }
}
?>
