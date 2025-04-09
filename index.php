<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

$messages = [];
$errors = [];
$values = [
  'fio' => '',
  'phone' => '',
  'email' => '',
  'dob' => '',
  'gender' => '',
  'bio' => '',
  'contract' => '',
  'languages' => []
];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
  if (!empty($_COOKIE['save'])) {
    setcookie('save', '', 100000);
    setcookie('login', '', 100000);
    setcookie('pass', '', 100000);
    $messages[] = 'Спасибо, результаты сохранены.';

    if (!empty($_COOKIE['login']) && !empty($_COOKIE['pass'])) {
      $messages[] = sprintf(
        'Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для редактирования.',
        strip_tags($_COOKIE['login']),
        strip_tags($_COOKIE['pass'])
      );
    }
  }

  foreach (['fio', 'phone', 'email', 'dob', 'gender', 'bio', 'contract'] as $field) {
    $errors[$field] = !empty($_COOKIE[$field . '_error']);
    setcookie($field . '_error', '', 100000);
    $values[$field] = empty($_COOKIE[$field . '_value']) ? '' : strip_tags($_COOKIE[$field . '_value']);
  }

  $values['languages'] = isset($_COOKIE['languages_value']) ? json_decode($_COOKIE['languages_value'], true) : [];

  if (!array_filter($errors) && !empty($_SESSION['login'])) {
    try {
      $pdo = new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
      ]);
      $stmt = $pdo->prepare("SELECT * FROM applications WHERE user_id = ?");
      $stmt->execute([$_SESSION['uid']]);
      $app = $stmt->fetch(PDO::FETCH_ASSOC);

      if ($app) {
        foreach (['fio', 'phone', 'email', 'dob', 'gender', 'bio', 'contract'] as $field) {
          $values[$field] = strip_tags($app[$field]);
        }

        $stmt = $pdo->prepare("SELECT pl.name FROM programming_languages pl
          JOIN application_languages al ON al.language_id = pl.id WHERE al.application_id = ?");
        $stmt->execute([$app['id']]);
        $values['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
      }

      $messages[] = sprintf('Вход выполнен как <strong>%s</strong>', $_SESSION['login']);
    } catch (PDOException $e) {
      $messages[] = 'Ошибка загрузки из БД: ' . $e->getMessage();
    }
  }

  include('form.php');
  exit();
}

// === POST ===
foreach (['fio', 'phone', 'email', 'dob', 'gender', 'bio'] as $field) {
  if (empty($_POST[$field])) {
    setcookie($field . '_error', '1', time() + 24 * 60 * 60);
    $errors[$field] = true;
  } else {
    setcookie($field . '_value', $_POST[$field], time() + 30 * 24 * 60 * 60);
  }
}

setcookie('contract_value', isset($_POST['contract']) ? '1' : '', time() + 30 * 24 * 60 * 60);
setcookie('languages_value', json_encode($_POST['languages'] ?? []), time() + 30 * 24 * 60 * 60);

if (!empty($errors)) {
  header('Location: index.php');
  exit();
}

try {
  $pdo = new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
  ]);

  $fio = $_POST['fio'];
  $phone = $_POST['phone'];
  $email = $_POST['email'];
  $dob = $_POST['dob'];
  $gender = $_POST['gender'];
  $bio = $_POST['bio'];
  $contract = isset($_POST['contract']) ? 1 : 0;
  $languages = $_POST['languages'] ?? [];

  if (!empty($_SESSION['login'])) {
    // Обновление
    $stmt = $pdo->prepare("UPDATE applications SET fio=?, phone=?, email=?, dob=?, gender=?, bio=?, contract=? WHERE user_id=?");
    $stmt->execute([$fio, $phone, $email, $dob, $gender, $bio, $contract, $_SESSION['uid']]);

    $stmt = $pdo->prepare("SELECT id FROM applications WHERE user_id=?");
    $stmt->execute([$_SESSION['uid']]);
    $app_id = $stmt->fetchColumn();

    $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id=?");
    $stmt->execute([$app_id]);
  } else {
    // Генерация логина и пароля
    $login = uniqid('user');
    $password = substr(md5(rand()), 0, 8);
    $pass_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("INSERT INTO users (login, password) VALUES (?, ?)");
    $stmt->execute([$login, $pass_hash]);
    $user_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO applications (fio, phone, email, dob, gender, bio, contract, user_id)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$fio, $phone, $email, $dob, $gender, $bio, $contract, $user_id]);
    $app_id = $pdo->lastInsertId();

    setcookie('login', $login);
    setcookie('pass', $password);
  }

  // Добавляем языки
  $stmt = $pdo->query("SELECT id, name FROM programming_languages");
  $langs = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
  $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");

  foreach ($languages as $lang) {
    if ($id = array_search($lang, $langs)) {
      $stmt->execute([$app_id, $id]);
    }
  }

  setcookie('save', '1');
  header('Location: index.php');
} catch (PDOException $e) {
  print('Ошибка: ' . $e->getMessage());
  exit();
}
