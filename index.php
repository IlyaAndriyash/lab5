<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

include 'db.php';

// Массив для сообщений
$messages = array();

// Обработка GET-запроса
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Проверка успешного сохранения
    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        setcookie('login', '', 100000, '/');
        setcookie('pass', '', 100000, '/');
        $messages[] = 'Спасибо, результаты сохранены.';
        if (!empty($_COOKIE['login']) && !empty($_COOKIE['pass'])) {
            $messages[] = sprintf('Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.',
                strip_tags($_COOKIE['login']),
                strip_tags($_COOKIE['pass']));
        }
    }

    // Инициализация ошибок и значений
    $errors = array();
    $values = array();
    $fields = ['fio', 'phone', 'email', 'dob', 'gender', 'bio', 'languages', 'contract'];
    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        $values[$field] = empty($_COOKIE[$field . '_value']) ? ($field === 'languages' ? [] : ($field === 'contract' ? 0 : '')) : strip_tags($_COOKIE[$field . '_value']);
    }

    // Вывод ошибок
    foreach ($fields as $field) {
        if ($errors[$field]) {
            setcookie($field . '_error', '', 100000);
            $messages[] = "<div class='error'>Ошибка в поле: $field</div>";
        }
    }

    // Загрузка данных авторизованного пользователя
    if (empty(array_filter($errors)) && !empty($_COOKIE[session_name()]) && !empty($_SESSION['login'])) {
        $stmt = $pdo->prepare("SELECT a.* FROM applications a JOIN users u ON a.id = u.application_id WHERE u.id = ?");
        $stmt->execute([$_SESSION['uid']]);
        $app = $stmt->fetch();
        if ($app) {
            $values = $app;
            $stmt = $pdo->prepare("SELECT pl.name FROM application_languages al JOIN programming_languages pl ON al.language_id = pl.id WHERE al.application_id = ?");
            $stmt->execute([$app['id']]);
            $values['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        printf('Вход с логином %s, uid %d', $_SESSION['login'], $_SESSION['uid']);
    }

    include 'form.php';
}
// Обработка POST-запроса
else {
    $errors = FALSE;
    $fields = ['fio', 'phone', 'email', 'dob', 'gender', 'bio', 'languages', 'contract'];
    $values = array();

    // Проверка и сохранение значений
    foreach ($fields as $field) {
        $value = $_POST[$field] ?? ($field === 'contract' ? 0 : ($field === 'languages' ? [] : ''));
        $values[$field] = $value;

        // Валидация
        if ($field === 'fio' && !preg_match('/^[\p{L}\s\-]+$/u', $value)) {
            setcookie('fio_error', '1', time() + 24 * 60 * 60);
            $errors = TRUE;
        } elseif ($field === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            setcookie('email_error', '1', time() + 24 * 60 * 60);
            $errors = TRUE;
        } elseif ($field === 'phone' && !preg_match('/^\+?\d{10,15}$/', $value)) {
            setcookie('phone_error', '1', time() + 24 * 60 * 60);
            $errors = TRUE;
        } elseif ($field === 'dob' && empty($value)) {
            setcookie('dob_error', '1', time() + 24 * 60 * 60);
            $errors = TRUE;
        } elseif ($field === 'gender' && !in_array($value, ['male', 'female'])) {
            setcookie('gender_error', '1', time() + 24 * 60 * 60);
            $errors = TRUE;
        } elseif ($field === 'bio' && empty($value)) {
            setcookie('bio_error', '1', time() + 24 * 60 * 60);
            $errors = TRUE;
        } elseif ($field === 'languages' && (empty($value) || !is_array($value))) {
            setcookie('languages_error', '1', time() + 24 * 60 * 60);
            $errors = TRUE;
        } elseif ($field === 'contract' && !$value) {
            setcookie('contract_error', '1', time() + 24 * 60 * 60);
            $errors = TRUE;
        } else {
            setcookie($field . '_value', $value, time() + 30 * 24 * 60 * 60);
        }
    }

    if ($errors) {
        header('Location: ./');
        exit();
    } else {
        foreach ($fields as $field) {
            setcookie($field . '_error', '', 100000);
        }
    }

    // Сохранение или обновление данных
    if (!empty($_COOKIE[session_name()]) && !empty($_SESSION['login'])) {
        $stmt = $pdo->prepare("UPDATE applications SET fio = ?, phone = ?, email = ?, dob = ?, gender = ?, bio = ?, contract = ? WHERE id = (SELECT application_id FROM users WHERE id = ?)");
        $stmt->execute([$values['fio'], $values['phone'], $values['email'], $values['dob'], $values['gender'], $values['bio'], $values['contract'], $_SESSION['uid']]);

        $stmt = $pdo->prepare("DELETE FROM application_languages WHERE application_id = (SELECT application_id FROM users WHERE id = ?)");
        $stmt->execute([$_SESSION['uid']]);

        $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        $appId = $pdo->query("SELECT application_id FROM users WHERE id = " . $_SESSION['uid'])->fetchColumn();
        foreach ($values['languages'] as $lang) {
            $stmt_lang = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $stmt_lang->execute([$lang]);
            $langId = $stmt_lang->fetchColumn();
            $stmt->execute([$appId, $langId]);
        }
    } else {
        $stmt = $pdo->prepare("INSERT INTO applications (fio, phone, email, dob, gender, bio, contract) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$values['fio'], $values['phone'], $values['email'], $values['dob'], $values['gender'], $values['bio'], $values['contract']]);
        $appId = $pdo->lastInsertId();

        $stmt = $pdo->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");
        foreach ($values['languages'] as $lang) {
            $stmt_lang = $pdo->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $stmt_lang->execute([$lang]);
            $langId = $stmt_lang->fetchColumn();
            $stmt->execute([$appId, $langId]);
        }

        $login = 'user' . substr(md5(uniqid(rand(), true)), 0, 6);
        $pass = bin2hex(random_bytes(4));
        $hash = password_hash($pass, PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
        $stmt->execute([$login, $hash, $appId]);

        setcookie('login', $login, time() + 30 * 24 * 60 * 60, '/');
        setcookie('pass', $pass, time() + 30 * 24 * 60 * 60, '/');
    }

    setcookie('save', '1', time() + 24 * 60 * 60);
    header('Location: ./');
}
