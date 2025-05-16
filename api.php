<?php
header('Content-Type: application/json; charset=UTF-8');
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("X-Content-Type-Options: nosniff");

require_once 'db.php';

// Обработка CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Основная обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    try {
        $input = json_decode(file_get_contents('php://input'), true);
        
        // Валидация данных (аналогично index.php)
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
        
        if (!empty($errors)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'errors' => $errors]);
            exit;
        }
        
        // Сохранение в БД (аналогично index.php)
        $db = getDbConnection();
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO applications (fio, phone, email, dob, gender, bio, contract) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $input['FIO'],
            $input['PHONE'],
            $input['EMAIL'],
            $input['DOB'] ?? '2000-01-01', // Дефолтное значение, если не передано
            $input['GENDER'] ?? 'male',     // Дефолтное значение
            $input['BIO'] ?? '',            // Пустая строка, если не передано
            isset($input['CONTRACT']) ? 1 : 0
        ]);
        
        $application_id = $db->lastInsertId();

        // Генерация логина/пароля (как в index.php)
        $login = substr(md5(uniqid(rand(), true)), 0, 8);
        $pass = substr(md5(uniqid(rand(), true)), 0, 8);
        $pass_hash = password_hash($pass, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
        $stmt->execute([$login, $pass_hash, $application_id]);

        // Обработка языков программирования
        if (!empty($input['LANGUAGES'])) {
            $stmt = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $insertLang = $db->prepare("INSERT INTO programming_languages (name) VALUES (?)");
            $linkStmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");

            foreach ($input['LANGUAGES'] as $language) {
                $stmt->execute([$language]);
                $languageData = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$languageData) {
                    $insertLang->execute([$language]);
                    $language_id = $db->lastInsertId();
                } else {
                    $language_id = $languageData['id'];
                }
                $linkStmt->execute([$application_id, $language_id]);
            }
        }

        $db->commit();
        
        // Ответ с данными для входа (как в index.php)
        echo json_encode([
            'success' => true,
            'message' => 'Спасибо за вашу заявку! Мы свяжемся с вами в ближайшее время.',
            'credentials' => [
                'login' => $login,
                'password' => $pass
            ]
        ]);
        
    } catch (PDOException $e) {
        $db->rollBack();
        error_log('Database error: ' . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Ошибка сервера: ' . $e->getMessage()]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Метод не поддерживается']);
}
