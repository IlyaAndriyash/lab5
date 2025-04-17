<?php
header('Content-Type: text/html; charset=UTF-8');

session_start(); // Запускаем сессию для хранения ошибок и данных формы

// Функция для подключения к базе данных (DRY)
function getDbConnection() {
    return new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);
}

// HTTP-аутентификация с использованием таблицы admins
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

    if (!$admin || $admin['password_hash'] !== md5($_SERVER['PHP_AUTH_PW'])) {
        header('HTTP/1.1 401 Unauthorized');
        header('WWW-Authenticate: Basic realm="My site"');
        print('<h1>401 Требуется авторизация</h1>');
        exit();
    }
} catch (PDOException $e) {
    print('Ошибка аутентификации: ' . htmlspecialchars($e->getMessage()));
    exit();
}

// Обработка удаления
if (isset($_GET['delete'])) {
    try {
        $db = getDbConnection();
        $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        header('Location: admin.php');
        exit(); // Добавлено exit()
    } catch (PDOException $e) {
        print('Ошибка при удалении: ' . htmlspecialchars($e->getMessage()));
        exit();
    }
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    error_log("POST request received with edit_id: " . $_POST['edit_id']);
    try {
        $db = getDbConnection();
        $db->beginTransaction();

        // Валидация данных
        $errors = FALSE;
        $error_messages = [];
        $all_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];

        if (empty($_POST['fio']) || !preg_match('/^[a-zA-Zа-яА-Я\s]{1,150}$/u', $_POST['fio'])) {
            $errors = TRUE;
            $error_messages[] = 'Некорректное ФИО.';
            error_log("Validation error: Некорректное ФИО");
        }
        if (empty($_POST['phone']) || !preg_match('/^\+?\d{10,15}$/', $_POST['phone'])) {
            $errors = TRUE;
            $error_messages[] = 'Некорректный телефон.';
            error_log("Validation error: Некорректный телефон");
        }
        if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors = TRUE;
            $error_messages[] = 'Некорректный email.';
            error_log("Validation error: Некорректный email");
        }
        if (empty($_POST['dob']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['dob'])) {
            $errors = TRUE;
            $error_messages[] = 'Некорректная дата рождения.';
            error_log("Validation error: Некорректная дата рождения");
        }
        if (empty($_POST['gender']) || !in_array($_POST['gender'], ['male', 'female'])) {
            $errors = TRUE;
            $error_messages[] = 'Выберите пол.';
            error_log("Validation error: Некорректный пол");
        }
        if (empty($_POST['languages']) || !is_array($_POST['languages']) || count(array_diff($_POST['languages'], $all_languages)) > 0) {
            $errors = TRUE;
            $error_messages[] = 'Выберите корректные языки программирования.';
            error_log("Validation error: Некорректные языки программирования");
        }
        if (empty($_POST['bio'])) {
            $errors = TRUE;
            $error_messages[] = 'Заполните биографию.';
            error_log("Validation error: Пустая биография");
        }
        if (empty($_POST['contract'])) {
            $errors = TRUE;
            $error_messages[] = 'Ознакомьтесь с контрактом.';
            error_log("Validation error: Контракт не отмечен");
        }

        if ($errors) {
            error_log("Validation errors: " . implode(", ", $error_messages));
            $_SESSION['edit_errors'] = $error_messages;
            $_SESSION['edit_values'] = $_POST;
            // Проверяем, активна ли транзакция перед откатом
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            header('Location: admin.php?edit=' . $_POST['edit_id']);
            exit();
        } else {
            error_log("Validation passed, updating database...");
            // Обновление данных заявки
            $stmt = $db->prepare("UPDATE applications SET fio = ?, phone = ?, email = ?, dob = ?, gender = ?, bio = ?, contract = ? WHERE id = ?");
            $stmt->execute([$_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['dob'], $_POST['gender'], $_POST['bio'], isset($_POST['contract']) ? 1 : 0, $_POST['edit_id']]);
            error_log("Application updated for ID: " . $_POST['edit_id']);

            // Удаление существующих языков
            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$_POST['edit_id']]);
            error_log("Existing languages deleted for application ID: " . $_POST['edit_id']);

            // Добавление новых языков
            $stmt = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
            $insertLang = $db->prepare("INSERT INTO programming_languages (name) VALUES (?)");
            $linkStmt = $db->prepare("INSERT INTO application_languages (application_id, language_id) VALUES (?, ?)");

            foreach ($_POST['languages'] as $language) {
                $stmt->execute([$language]);
                $languageData = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$languageData) {
                    $insertLang->execute([$language]);
                    $language_id = $db->lastInsertId();
                    error_log("New language inserted: $language, ID: $language_id");
                } else {
                    $language_id = $languageData['id'];
                    error_log("Existing language found: $language, ID: $language_id");
                }
                $linkStmt->execute([$_POST['edit_id'], $language_id]);
                error_log("Linked language ID $language_id to application ID: " . $_POST['edit_id']);
            }

            $db->commit();
            error_log("Transaction committed, redirecting to admin.php");
            unset($_SESSION['edit_errors'], $_SESSION['edit_values']); // Очищаем сессию
            header('Location: admin.php');
            exit();
        }
    } catch (PDOException $e) {
        // Проверяем, активна ли транзакция перед откатом
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        error_log("Database error: " . $e->getMessage());
        print('Ошибка при редактировании: ' . htmlspecialchars($e->getMessage()));
        exit();
    }
}

// Получение всех заявок
try {
    $db = getDbConnection();
    $stmt = $db->query("SELECT a.*, GROUP_CONCAT(pl.name) as languages 
                        FROM applications a 
                        LEFT JOIN application_languages al ON a.id = al.application_id 
                        LEFT JOIN programming_languages pl ON al.language_id = pl.id 
                        GROUP BY a.id");
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Получение статистики
    $stmt = $db->query("SELECT pl.name, COUNT(al.application_id) as count 
                        FROM programming_languages pl 
                        LEFT JOIN application_languages al ON pl.id = al.language_id 
                        GROUP BY pl.id");
    $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    print('Ошибка при получении данных: ' . htmlspecialchars($e->getMessage()));
    exit();
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #f0f0f0; }
        .container { max-width: 1000px; margin: 20px auto; padding: 20px; background-color: #fff; border: 1px solid #ddd; border-radius: 5px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .form-container { margin-top: 20px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; }
        label { display: block; margin-bottom: 10px; }
        input, select, textarea { width: 95%; padding: 8px; margin-bottom: 10px; }
        input[type="submit"] { background-color: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .error { color: red; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h2>Панель администратора</h2>

        <!-- Отображение заявок -->
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
                    <td><?php echo htmlspecialchars($app['id']); ?></td>
                    <td><?php echo htmlspecialchars($app['fio']); ?></td>
                    <td><?php echo htmlspecialchars($app['phone']); ?></td>
                    <td><?php echo htmlspecialchars($app['email']); ?></td>
                    <td><?php echo htmlspecialchars($app['dob']); ?></td>
                    <td><?php echo htmlspecialchars($app['gender']); ?></td>
                    <td><?php echo htmlspecialchars($app['languages'] ?: ''); ?></td>
                    <td><?php echo htmlspecialchars($app['bio']); ?></td>
                    <td><?php echo $app['contract'] ? 'Да' : 'Нет'; ?></td>
                    <td>
                        <a href="?edit=<?php echo $app['id']; ?>">Редактировать</a> |
                        <a href="?delete=<?php echo $app['id']; ?>" onclick="return confirm('Вы уверены?');">Удалить</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Статистика -->
        <h3>Статистика по языкам программирования</h3>
        <table>
            <tr>
                <th>Язык</th>
                <th>Количество пользователей</th>
            </tr>
            <?php foreach ($stats as $stat): ?>
                <tr>
                    <td><?php echo htmlspecialchars($stat['name'] ?: 'Без языка'); ?></td>
                    <td><?php echo $stat['count']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>

        <!-- Форма редактирования -->
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

                        // Используем сохраненные значения из сессии, если есть
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
                                <div style="color: red;">
                                    <?php foreach ($_SESSION['edit_errors'] as $msg): ?>
                                        <p><?php echo htmlspecialchars($msg); ?></p>
                                    <?php endforeach; ?>
                                </div>
                                <?php unset($_SESSION['edit_errors']); ?>
                            <?php endif; ?>
                            <form method="POST">
                                <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                                <label>ФИО:</label>
                                <input type="text" name="fio" value="<?php echo htmlspecialchars($values['fio']); ?>">
                                <label>Телефон:</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($values['phone']); ?>">
                                <label>Email:</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($values['email']); ?>">
                                <label>Дата рождения:</label>
                                <input type="date" name="dob" value="<?php echo htmlspecialchars($values['dob']); ?>">
                                <label>Пол:</label>
                                <select name="gender">
                                    <option value="male" <?php echo $values['gender'] == 'male' ? 'selected' : ''; ?>>Мужской</option>
                                    <option value="female" <?php echo $values['gender'] == 'female' ? 'selected' : ''; ?>>Женский</option>
                                </select>
                                <label>Языки программирования:</label>
                                <select name="languages[]" multiple>
                                    <?php
                                    $all_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                                    foreach ($all_languages as $lang) {
                                        $selected = in_array($lang, $values['languages']) ? 'selected' : '';
                                        echo "<option value='$lang' $selected>$lang</option>";
                                    }
                                    ?>
                                </select>
                                <label>Биография:</label>
                                <textarea name="bio"><?php echo htmlspecialchars($values['bio']); ?></textarea>
                                <label>
                                    <input type="checkbox" name="contract" <?php echo $values['contract'] ? 'checked' : ''; ?>>
                                    Ознакомлен с контрактом
                                </label>
                                <input type="submit" value="Сохранить">
                            </form>
                        </div>
        <?php
                    }
                } catch (PDOException $e) {
                    print('<div class="error">Ошибка при загрузке заявки: ' . htmlspecialchars($e->getMessage()) . '</div>');
                }
            }
        endif;
        ?>
    </div>
</body>
</html>
