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
  <form action="login.php" method="POST">
    <input name="login" placeholder="Логин" />
    <input name="pass" placeholder="Пароль" type="password" />
    <input type="submit" value="Войти" />
  </form>
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
      echo "Неверный логин или пароль.";
    }
  } catch (PDOException $e) {
    echo 'Ошибка: ' . $e->getMessage();
  }
}
