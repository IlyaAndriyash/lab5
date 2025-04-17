<?php
header('Content-Type: text/html; charset=UTF-8');

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
        exit();
    } catch (PDOException $e) {
        print('Ошибка при удалении: ' . htmlspecialchars($e->getMessage()));
        exit();
    }
}

// Обработка формы редактирования
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    try {
        $db = getDbConnection();

        // Валидация данных
        $errors = false;
        $error_messages = [];
        if (empty($_POST['fio']) || !preg_match('/^[a-zA-Zа-яА-Я\s]{1,150}$/u', $_POST['fio'])) {
            $errors = true;
            $error_messages[] = 'Некорректное ФИО.';
        }
        if (empty($_POST['phone']) || !preg_match('/^\+?\d{10,15}$/', $_POST['phone'])) {
            $errors = true;
            $error_messages[] = 'Некорректный телефон.';
        }
        if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
            $errors = true;
            $error_messages[] = 'Некорректный email.';
        }
        if (empty($_POST['dob']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['dob'])) {
            $errors = true;
            $error_messages[] = 'Некорректная дата рождения.';
        }
        if (empty($_POST['gender']) || !in_array($_POST['gender'], ['male', 'female'])) {
            $errors = true;
            $error_messages[] = 'Выберите пол.';
        }
        if (empty($_POST['languages']) || !is_array($_POST['languages'])) {
            $errors = true;
            $error_messages[] = 'Выберите хотя бы один язык.';
        }
        if (empty($_POST['bio'])) {
            $errors = true;
            $error_messages[] = 'Заполните биографию.';
        }
        if (empty($_POST['contract'])) {
            $errors = true;
            $error_messages[] = 'Ознакомьтесь с контрактом.';
        }

        if ($errors) {
            // Сохраняем введенные данные для повторного отображения формы
            $edit_id = filter_var($_POST['edit_id'], FILTER_VALIDATE_INT);
            $app = [
                'id' => $edit_id,
                'fio' => $_POST['fio'],
                'phone' => $_POST['phone'],
                'email' => $_POST['email'],
                'dob' => $_POST['dob'],
                'gender' => $_POST['gender'],
                'bio' => $_POST['bio'],
                'contract' => isset($_POST['contract']) ? 1 : 0,
            ];
            $languages = $_POST['languages'];
            foreach ($error_messages as $msg) {
                print('<div class="error">Ошибка: ' . htmlspecialchars($msg) . '</div>');
            }
        } else {
            // Начинаем транзакцию только после успешной валидации
            $db->beginTransaction();

            // Обновление данных заявки
            $stmt = $db->prepare("UPDATE applications SET fio = ?, phone = ?, email = ?, dob = ?, gender = ?, bio = ?, contract = ? WHERE id = ?");
            $stmt->execute([$_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['dob'], $_POST['gender'], $_POST['bio'], isset($_POST['contract']) ? 1 : 0, $_POST['edit_id']]);

            // Удаление существующих языков
            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$_POST['edit_id']]);

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
                } else {
                    $language_id = $languageData['id'];
                }
                $linkStmt->execute([$_POST['edit_id'], $language_id]);
            }

            $db->commit();
            header('Location: admin.php');
            exit();
        }
    } catch (PDOException $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
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
    < نمونه style>
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
        <?php if (isset($_GET['edit']) || (isset($errors) && $errors)): 
            $edit_id = isset($_GET['edit']) ? filter_var($_GET['edit'], FILTER_VALIDATE_INT) : (isset($_POST['edit_id']) ? $_POST['edit_id'] : 0);
            if ($edit_id === false || $edit_id <= 0) {
                print('<div class="error">Неверный ID заявки.</div>');
            } else {
                try {
                    $db = getDbConnection();
                    if (isset($errors) && $errors) {
                        // Используем данные из POST при ошибке валидации
                        $app = [
                            'id' => $edit_id,
                            'fio' => $_POST['fio'],
                            'phone' => $_POST['phone'],
                            'email' => $_POST['email'],
                            'dob' => $_POST['dob'],
                            'gender' => $_POST['gender'],
                            'bio' => $_POST['bio'],
                            'contract' => isset($_POST['contract']) ? 1 : 0,
                        ];
                        $languages = $_POST['languages'];
                    } else {
                        // Загружаем данные из базы
                        $stmt = $db->prepare("SELECT * FROM applications WHERE id = ?");
                        $stmt->execute([$edit_id]);
                        $app = $stmt->fetch(PDO::FETCH_ASSOC);

                        if (!$app) {
                            print('<div class="error">Заявка не найдена.</div>');
                            $app = null;
                        } else {
                            $stmt = $db->prepare("SELECT pl.name FROM programming_languages pl JOIN application_languages al ON pl.id = al.language_id WHERE al.application_id = ?");
                            $stmt->execute([$edit_id]);
                            $languages = $stmt->fetchAll(PDO::FETCH_COLUMN);
                        }
                    }

                    if ($app):
        ?>
                        <div class="form-container">
                            <h3>Редактировать заявку #<?php echo $edit_id; ?></h3>
                            <form method="POST">
                                <input type="hidden" name="edit_id" value="<?php echo $edit_id; ?>">
                                <label>ФИО:</label>
                                <input type="text" name="fio" value="<?php echo htmlspecialchars($app['fio']); ?>">
                                <label>Телефон:</label>
                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($app['phone']); ?>">
                                <label>Email:</label>
                                <input type="email" name="email" value="<?php echo htmlspecialchars($app['email']); ?>">
                                <label>Дата рождения:</label>
                                <input type="date" name="dob" value="<?php echo htmlspecialchars($app['dob']); ?>">
                                <label>Пол:</label>
                                <select name="gender">
                                    <option value="male" <?php echo $app['gender'] == 'male' ? 'selected' : ''; ?>>Мужской</option>
                                    <option value="female" <?php echo $app['gender'] == 'female' ? 'selected' : ''; ?>>Женский | selected | </option>
                                </select>
                                <label>Языки программирования:</label>
                                <select name="languages[]" multiple>
                                    <?php
                                    $all_languages = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                                    foreach ($all_languages as $lang) {
                                        $selected = in_array($lang, $languages) ? 'selected' : '';
                                        echo "<option value='$lang' $selected>$lang</option>";
                                    }
                                    ?>
                                </select>
                                <label>Биография:</label>
                                <textarea name="bio" rows="5"><?php echo htmlspecialchars($app['bio']); ?></textarea>
                                <label><input type="checkbox" name="contract" <?php echo $app['contract'] ? 'checked' : ''; ?>> С контрактом ознакомлен</label>
                                <input type="submit" value="Сохранить">
                            </form>
                        </div>
        <?php
                    endif;
                } catch (PDOException $e) {
                    print('<div class="error">Ошибка при загрузке формы: ' . htmlspecialchars($e->getMessage()) . '</div>');
                }
            }
        endif; ?>
    </div>
</body>
</html>
