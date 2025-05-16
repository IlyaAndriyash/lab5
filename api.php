<?php
header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("X-Content-Type-Options: nosniff");

// Подключение к БД
function getDbConnection() {
    return new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
}

// Обработка CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Основная обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Валидация данных
        $errors = [];
        
        if (empty($input['FIO']) || !preg_match('/^[\p{Cyrillic}\p{Latin}\s]{1,150}$/u', $input['FIO'])) {
            $errors['FIO'] = 'Некорректное ФИО';
        }
        
        if (empty($input['PHONE']) || !preg_match('/^\+?\d{10,15}$/', $input['PHONE'])) {
            $errors['PHONE'] = 'Некорректный телефон';
        }
        
        if (empty($input['EMAIL']) || !filter_var($input['EMAIL'], FILTER_VALIDATE_EMAIL)) {
            $errors['EMAIL'] = 'Некорректный email';
        }
        
        if (empty($input['agreement'])) {
            $errors['agreement'] = 'Необходимо согласие';
        }
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        // Сохранение в БД
        $db = getDbConnection();
        $stmt = $db->prepare("INSERT INTO applications (fio, phone, email, comment) VALUES (?, ?, ?, ?)");
        $stmt->execute([
            $input['FIO'],
            $input['PHONE'],
            $input['EMAIL'],
            $input['COMMENT'] ?? ''
        ]);
        
        // Генерация логина/пароля
        $login = substr(md5(uniqid(rand(), true)), 0, 8);
        $pass = substr(md5(uniqid(rand(), true)), 0, 8);
        $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
        $stmt->execute([$login, $pass_hash, $db->lastInsertId()]);
        
        // Ответ
        echo json_encode([
            'success' => true,
            'message' => 'Спасибо за вашу заявку! Мы свяжемся с вами в ближайшее время.',
            'credentials' => [
                'login' => $login,
                'password' => $pass,
                'profile_url' => '/profile'
            ]
        ]);
        
    } catch (PDOException $e) {
        error_log('Database error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера']);
    } catch (Exception $e) {
        error_log('Error: ' . $e->getMessage());
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
}
