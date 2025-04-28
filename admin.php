<?php
// Установка заголовков безопасности
header('Content-Type: text/html; charset=UTF-8');
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'");
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

// Функция для создания PDO-соединения
function getDbConnection() {
    return new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
}

// HTTP-аутентификация
try {
    $db = getDbConnection();
    
    if (empty($_SERVER['PHP_AUTH_USER']) || empty($_SERVER['PHP_AUTH_PW'])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="My site"');
        print('<h1>401 Требуется авторизация</h1>');
        exit();
    }

    $stmt = $db->prepare("SELECT password_hash FROM admins WHERE login = ?");
    $stmt->execute([$_SERVER['PHP_AUTH_USER']]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin || !password_verify($_SERVER['PHP_AUTH_PW'], $admin['password_hash'])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="My site"');
        print('<h1>401 Требуется авторизация</h1>');
        exit();
    }
} catch (PDOException $e) {
    error_log('Auth error: ' . $e->getMessage());
    print('<h1>Ошибка аутентификации</h1>');
    exit();
}

// Проверка CSRF для POST-запросов
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        die('Неверный CSRF-токен');
    }
}

// Обработка удаления заявки
if (isset($_GET['delete'])) {
    try {
        $delete_id = filter_var($_GET['delete'], FILTER_VALIDATE_INT);
        if (!$delete_id) {
            throw new Exception("Invalid ID");
        }
        
        $db = getDbConnection();
        $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$delete_id]);
        header('Location: admin.php');
        exit();
    } catch (Exception $e) {
        error_log('Delete error: ' . $e->getMessage());
        print('<h1>Ошибка при удалении</h1>');
        exit();
    }
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    try {
        $db = getDbConnection();
        $errors = FALSE;
        $error_messages = [];
        $all_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];

        // Валидация данных
        if (empty($_POST['fio']) || !preg_match('/^[\p{Cyrillic}\p{Latin}\s]{1,150}$/u', $_POST['fio'])) {
            $errors = TRUE;
            $error_messages[] = 'Некорректное ФИО.';
        }
        if (empty($_POST['phone']) || !preg_match('/^\+?\d{10,15}$/', $_POST['phone'])) {
            $errors = TRUE;
            $error_messages[] = 'Некорректный телефон.';
        }
        if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors = TRUE;
            $error_messages[] = 'Некорректный email.';
        }
        if (empty($_POST['dob']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['dob'])) {
            $errors = TRUE;
            $error_messages[] = 'Некорректная дата рождения.';
        }
        if (empty($_POST['gender']) || !in_array($_POST['gender'], ['male', 'female'])) {
            $errors = TRUE;
            $error_messages[] = 'Выберите пол.';
        }
        if (empty($_POST['languages']) || !is_array($_POST['languages']) || count(array_diff($_POST['languages'], $all_languages)) > 0) {
            $errors = TRUE;
            $error_messages[] = 'Выберите корректные языки программирования.';
        }
        if (empty($_POST['bio'])) {
            $errors = TRUE;
            $error_messages[] = 'Заполните биографию.';
        }

        if ($errors) {
            $_SESSION['edit_errors'] = $error_messages;
            $_SESSION['edit_values'] = $_POST;
            header('Location: admin.php?edit=' . (int)$_POST['edit_id']);
            exit();
        }

        $db->beginTransaction();

        try {
            $stmt = $db->prepare("UPDATE applications SET fio = ?, phone = ?, email = ?, dob = ?, gender = ?, bio = ?, contract = ? WHERE id = ?");
            $stmt->execute([
                $_POST['fio'],
                $_POST['phone'],
                $_POST['email'],
                $_POST['dob'],
                $_POST['gender'],
                $_POST['bio'],
                isset($_POST['contract']) ? 1 : 0,
                (int)$_POST['edit_id']
            ]);

            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([(int)$_POST['edit_id']]);

            $stmt = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $insertLang = $db->prepare("INSERT INTO programming_languages (name) VALUES (?)");
            $linkStmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");

            foreach ($_POST['languages'] as $language) {
                $stmt->execute([$language]);
                $languageData = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$languageData) {
                    $insertLang->execute([$language]);
                    $language_id = $db->lastInsertId();
                } else {
                    $language_id = $languageData['id'];
                }
                $linkStmt->execute([(int)$_POST['edit_id'], $language_id]);
            }

            $db->commit();
            unset($_SESSION['edit_errors'], $_SESSION['edit_values']);
            header('Location: admin.php');
            exit();
        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }

    } catch (PDOException $e) {
        error_log('Edit error: ' . $e->getMessage());
        $_SESSION['edit_values'] = $_POST;
        header('Location: admin.php?edit=' . (int)$_POST['edit_id']);
        exit();
    }
}

// Получение данных
try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT a.*, GROUP_CONCAT(pl.name) as languages 
                        FROM applications a 
                        LEFT JOIN application_languages al ON a.id = al.application_id 
                        LEFT JOIN programming_languages pl ON al.language_id = pl.id 
                        GROUP BY a.id");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->query("SELECT pl.name, COUNT(al.application_id) as count 
                        FROM programming_languages pl 
                        LEFT JOIN application_languages al ON pl.id = al.language_id 
                        GROUP BY pl.id");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    error_log('Data fetch error: ' . $e->getMessage());
    print('<h1>Ошибка при получении данных</h1>');
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Панель администратора</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; margin: 0; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 5px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .error { color: red; }
        .form-container { margin-top: 20px; padding: 20px; background: #f9f9f9; border-radius: 5px; }
        input, select, textarea { width: 100%; padding: 8px; margin-bottom: 10px; border: 1px solid #ddd; border-radius: 4px; }
        input[type="submit"] { background: #4CAF50; color: white; border: none; padding: 10px 15px; cursor: pointer; }
        input[type="submit"]:hover { background: #45a049; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Панель администратора</h2>
        
        <h3>Заявки</h3>
        <table>
            <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Телефон</th>
                <th>Email</th>
                <th>Дата рождения</th>
                <th>Пол</th>
                <th>Языки</th>
                <th>Биография</th>
                <th>Контракт</th>
                <th>Действия</th>
            </tr>
            <?php foreach ($applications as $app): ?>
                <tr>
                    <td><?php echo htmlspecialchars($app['id'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($app['fio'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($app['phone'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($app['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($app['dob'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($app['gender'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($app['languages'] ?: '', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo htmlspecialchars($app['bio'], ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo $app['contract'] ? 'Да' : 'Нет'; ?></td>
                    <td>
                        <a href="?edit=<?php echo (int)$app['id']; ?>">Редактировать</a> |
                        <a href="?delete=<?php echo (int)$app['id']; ?>" onclick="return confirm('Вы уверены?')">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <h3>Статистика по языкам программирования</h3>
        <table>
            <tr>
                <th>Язык</th>
                <th>Количество пользователей</th>
            </tr>
            <?php foreach ($stats as $stat): ?>
                <tr>
                    <td><?php echo htmlspecialchars($stat['name'] ?: 'Без языка', ENT_QUOTES, 'UTF-8'); ?></td>
                    <td><?php echo (int)$stat['count']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <?php if (isset($_GET['edit'])): 
            $edit_id = filter_var($_GET['edit'], FILTER_VALIDATE_INT);
            if ($edit_id === false || $edit_id <= 0) {
                print('<div class="error">Неверный ID заявки.</div>');
            } else {
                try {
                    $db = getDbConnection();
                    $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
                    $stmt->execute([$edit_id]);
                    $app = $stmt->fetch(PDO::FETCH_ASSOC);

                    if (!$app) {
                        print('<div class="error">Заявка не найдена.</div>');
                    } else {
                        $stmt = $db->prepare("SELECT pl.name FROM programming_languages pl JOIN application_languages al ON pl.id = al.language_id WHERE al.application_id = ?");
                        $stmt->execute([$edit_id]);
                        $languages = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        $values = isset($_SESSION['edit_values']) ? $_SESSION['edit_values'] : [
                            'fio' => $app['fio'],
                            'phone' => $app['phone'],
                            'email' => $app['email'],
                            'dob' => $app['dob'],
                            'gender' => $app['gender'],
                            'languages' => $languages,
                            'bio' => $app['bio'],
                            'contract' => $app['contract'] ? 'on' : ''
                        ];
        ?>
                        <div class="form-container">
                            <h3>Редактировать заявку #<?php echo $edit_id; ?></h3>
                            <?php if (isset($_SESSION['edit_errors'])): ?>
                                <div class="error">
                                    <?php foreach ($_SESSION['edit_errors'] as $msg): ?>
                                        <p><?php echo htmlspecialchars($msg, ENT_QUOTES, 'UTF-8'); ?></p>
                                    <?php endforeach; ?>
                                </div>
                                <?php unset($_SESSION['edit_errors']); ?>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
                                <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                                
                                <label>ФИО:</label>
                                <input type="text" name="fio" value="<?php echo htmlspecialchars($values['fio'], ENT_QUOTES, 'UTF-8'); ?>">
                                
                                <label>Телефон:</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($values['phone'], ENT_QUOTES, 'UTF-8'); ?>">
                                
                                <label>Email:</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($values['email'], ENT_QUOTES, 'UTF-8'); ?>">
                                
                                <label>Дата рождения:</label>
                                <input type="date" name="dob" value="<?php echo htmlspecialchars($values['dob'], ENT_QUOTES, 'UTF-8'); ?>">
                                
                                <label>Пол:</label>
                                <select name="gender">
                                    <option value="male" <?php echo $values['gender'] == 'male' ? 'selected' : ''; ?>>Мужской</option>
                                    <option value="female" <?php echo $values['gender'] == 'female' ? 'selected' : ''; ?>>Женский</option>
                                </select>
                                
                                <label>Языки программирования:</label>
                                <select name="languages[]" multiple size="5">
                                    <?php
                                    $all_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                                    foreach ($all_languages as $lang) {
                                        $selected = in_array($lang, $values['languages']) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . 
                                             htmlspecialchars($lang, ENT_QUOTES, 'UTF-8') . '</option>';
                                    }
                                    ?>
                                </select>
                                
                                <label>Биография:</label>
                                <textarea name="bio" rows="5"><?php echo htmlspecialchars($values['bio'], ENT_QUOTES, 'UTF-8'); ?></textarea>
                                
                                <label><input type="checkbox" name="contract" <?php echo !empty($values['contract']) ? 'checked' : ''; ?>> С контрактом ознакомлен</label>
                                
                                <input type="submit" value="Сохранить">
                            </form>
                        </div>
        <?php 
                    }
                } catch (PDOException $e) {
                    error_log('Form load error: ' . $e->getMessage());
                    print('<div class="error">Ошибка при загрузке формы</div>');
                }
            }
        endif; ?>
    </div>
</body>
</html>
