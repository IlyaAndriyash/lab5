<?php
$host = 'localhost';
$db   = 'u68818'; // Имя твоей базы данных
$user = 'u68818'; // Твой логин
$pass = '9972335'; // Твой пароль
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Ошибки через исключения
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Ассоциативный массив
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Использование настоящих prepared statements
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    exit('Ошибка подключения к БД: ' . $e->getMessage());
}
