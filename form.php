<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Форма заявки</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f0f0f0;
        }

        .form-container {
            max-width: 600px;
            margin: 40px auto;
            padding: 20px;
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        label {
            display: block;
            margin-bottom: 10px;
        }

        input[type="text"], input[type="tel"], input[type="email"], input[type="date"], select, textarea {
            width: 95%;
            padding: 10px;
            margin-bottom: 20px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        input[type="radio"] {
            margin-right: 10px;
        }

        input[type="checkbox"] {
            margin-right: 10px;
        }

        button[type="submit"], button[type="button"] {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 10px;
        }

        button[type="submit"]:hover, button[type="button"]:hover {
            background-color: #0056b3;
        }

        .radio-group {
            margin-bottom: 20px;
        }

        .radio-group label {
            display: inline;
        }

        .checkbox-group {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .checkbox-group label {
            margin-left: 10px;
            padding-top: 10px;
        }

        .error {
            border: 2px solid red;
        }

        .error-message {
            color: red;
            font-size: 0.9em;
            margin-bottom: 10px;
        }

        .messages {
            background-color: #f8f9fa;
            border-left: 4px solid #17a2b8;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 0 4px 4px 0;
        }

        .logout-btn {
            background-color: #dc3545;
            float: right;
        }

        .logout-btn:hover {
            background-color: #c82333;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Форма заявки</h2>

        <?php if (!empty($messages)) : ?>
            <div class="messages">
                <?php foreach ($messages as $msg) : ?>
                    <div><?= $msg ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['login'])) : ?>
            <form action="index.php?logout=1" method="post" style="margin-bottom: 20px;">
                <button type="submit" class="logout-btn">Выйти (<?= htmlspecialchars($_SESSION['login']) ?>)</button>
            </form>
        <?php endif; ?>

        <form action="index.php" method="POST">
            <!-- Поле ФИО -->
            <label for="fio">ФИО:</label>
            <input type="text" name="fio" id="fio" value="<?= htmlspecialchars($values['fio']) ?>" <?= $errors['fio'] ? 'class="error"' : '' ?>>
            <?php if ($errors['fio']) : ?>
                <div class="error-message">Заполните ФИО.</div>
            <?php endif; ?>

            <!-- Поле Телефон -->
            <label for="phone">Телефон:</label>
            <input type="tel" name="phone" id="phone" value="<?= htmlspecialchars($values['phone']) ?>" <?= $errors['phone'] ? 'class="error"' : '' ?>>
            <?php if ($errors['phone']) : ?>
                <div class="error-message">Телефон должен быть в формате +7XXXXXXXXXX или XXXXXXXXXX.</div>
            <?php endif; ?>

            <!-- Поле Email -->
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?= htmlspecialchars($values['email']) ?>" <?= $errors['email'] ? 'class="error"' : '' ?>>
            <?php if ($errors['email']) : ?>
                <div class="error-message">Некорректный email.</div>
            <?php endif; ?>

            <!-- Поле Дата рождения -->
            <label for="dob">Дата рождения:</label>
            <input type="date" name="dob" id="dob" value="<?= htmlspecialchars($values['dob']) ?>" <?= $errors['dob'] ? 'class="error"' : '' ?>>
            <?php if ($errors['dob']) : ?>
                <div class="error-message">Некорректная дата рождения.</div>
            <?php endif; ?>

            <!-- Поле Пол -->
            <label>Пол:</label>
            <div class="radio-group">
                <input type="radio" name="gender" value="male" id="male" <?= $values['gender'] === 'male' ? 'checked' : '' ?>>
                <label for="male">Мужской</label>
                <input type="radio" name="gender" value="female" id="female" <?= $values['gender'] === 'female' ? 'checked' : '' ?>>
                <label for="female">Женский</label>
            </div>
            <?php if ($errors['gender']) : ?>
                <div class="error-message">Выберите пол.</div>
            <?php endif; ?>

            <!-- Поле Любимый язык программирования -->
            <label for="languages">Любимый язык программирования:</label>
            <div style="margin-bottom: 20px;">
                <?php
                try {
                    $pdo = new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
                    ]);
                    $stmt = $pdo->query("SELECT name FROM programming_languages");
                    $all_langs = $stmt->fetchAll(PDO::FETCH_COLUMN);

                    foreach ($all_langs as $lang) {
                        $checked = in_array($lang, $values['languages']) ? 'checked' : '';
                        echo "<div class='checkbox-group'>";
                        echo "<input type='checkbox' name='languages[]' id='lang_".htmlspecialchars($lang)."' value='".htmlspecialchars($lang)."' $checked>";
                        echo "<label for='lang_".htmlspecialchars($lang)."'>".htmlspecialchars($lang)."</label>";
                        echo "</div>";
                    }
                } catch (PDOException $e) {
                    echo "<div class='error-message'>Ошибка загрузки языков: ".htmlspecialchars($e->getMessage())."</div>";
                }
                ?>
            </div>
            <?php if ($errors['languages']) : ?>
                <div class="error-message">Выберите хотя бы один язык программирования.</div>
            <?php endif; ?>

            <!-- Поле Биография -->
            <label for="bio">Биография:</label>
            <textarea name="bio" id="bio" rows="5" cols="40" <?= $errors['bio'] ? 'class="error"' : '' ?>><?= htmlspecialchars($values['bio']) ?></textarea>
            <?php if ($errors['bio']) : ?>
                <div class="error-message">Заполните биографию.</div>
            <?php endif; ?>

            <!-- Чекбокс "С контрактом ознакомлен" -->
            <div class="checkbox-group">
                <input type="checkbox" name="contract" id="contract" <?= $values['contract'] ? 'checked' : '' ?>>
                <label for="contract">С контрактом ознакомлен(а)</label>
            </div>
            <?php if ($errors['contract']) : ?>
                <div class="error-message">Необходимо ознакомиться с контрактом.</div>
            <?php endif; ?>

            <!-- Кнопка отправки формы -->
            <button type="submit">Сохранить</button>
        </form>
    </div>
</body>
</html>
