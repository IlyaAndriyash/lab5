<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    include 'form.php';
    exit();
}

$errors = [];
$data = [];

foreach (['fio', 'email', 'phone', 'dob', 'gender', 'bio', 'contract', 'languages'] as $field) {
    $data[$field] = $_POST[$field] ?? '';
}

if (!isset($_POST['contract'])) $data['contract'] = 0;

include 'db.php';

// Валидация
if (!preg_match('/^[\p{L}\s\-]+$/u', $data['fio'])) $errors['fio'] = 'Введите корректное имя.';
if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Некорректный email.';
if (!preg_match('/^\+?\d{10,15}$/', $data['phone'])) $errors['phone'] = 'Некорректный телефон.';
if (empty($data['dob'])) $errors['dob'] = 'Дата рождения обязательна.';
if (!in_array($data['gender'], ['male', 'female'])) $errors['gender'] = 'Выберите пол.';
if (empty($data['bio'])) $errors['bio'] = 'Биография обязательна.';
if (empty($data['languages']) || !is_array($data['languages'])) $errors['languages'] = 'Выберите хотя бы один язык.';

if (!empty($errors)) {
    $_SESSION['errors'] = $errors;
    $_SESSION['data'] = $data;
    header('Location: form.php');
    exit();
}

// Сохраняем заявку
$stmt = $pdo->prepare("INSERT INTO applications (fio, phone, email, dob, gender, bio, contract)
    VALUES (?, ?, ?, ?, ?, ?, ?)");
$stmt->execute([
    $data['fio'], $data['phone'], $data['email'], $data['dob'],
    $data['gender'], $data['bio'], $data['contract']
]);
$appId = $pdo->lastInsertId();

// Сохраняем языки
$stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
foreach ($data['languages'] as $langId) {
    $stmt->execute([$appId, $langId]);
}

// Регистрируем пользователя
$login = 'user' . rand(1000, 9999);
$password = $login; // пароль равен логину
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
$stmt->execute([$login, $hash, $appId]);

$_SESSION['login'] = $login;
$_SESSION['uid'] = $pdo->lastInsertId();

setcookie('login', $login);
setcookie('password', $password);

header('Location: form.php');
