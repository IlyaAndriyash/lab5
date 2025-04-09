<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форма заявки</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background: linear-gradient(135deg, #f0f4f8, #d9e2ec);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            margin: 0;
            padding: 20px;
        }

        .form-container {
            background: #ffffff;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            width: 100%;
            animation: fadeIn 0.5s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
            font-size: 24px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        input[type="text"],
        input[type="tel"],
        input[type="email"],
        input[type="date"],
        select,
        textarea {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
        }

        input[type="text"]:focus,
        input[type="tel"]:focus,
        input[type="email"]:focus,
        input[type="date"]:focus,
        select:focus,
        textarea:focus {
            border-color: #007bff;
            box-shadow: 0 0 5px rgba(0, 123, 255, 0.3);
            outline: none;
        }

        .error {
            border: 2px solid #ff4d4d;
        }

        .messages {
            background: #fff3e6;
            border-left: 4px solid #ff9500;
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            color: #333;
        }

        .radio-group, .checkbox-group {
            margin-bottom: 15px;
        }

        .radio-group label,
        .checkbox-group label {
            display: inline;
            margin-right: 20px;
            color: #555;
        }

        input[type="radio"],
        input[type="checkbox"] {
            margin-right: 5px;
            accent-color: #007bff;
        }

        input[type="submit"] {
            background: #007bff;
            color: #fff;
            padding: 12px 25px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            width: 100%;
        }

        input[type="submit"]:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        a {
            color: #007bff;
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        select[multiple] {
            height: 120px;
        }

        .logout-form {
            text-align: right;
            margin-bottom: 15px;
        }

        .logout-form input[type="submit"] {
            width: auto;
            padding: 8px 15px;
            background: #dc3545;
        }

        .logout-form input[type="submit"]:hover {
            background: #b02a37;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 20px;
            }

            input[type="submit"] {
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>Форма заявки</h2>

    <?php if (!empty($messages)): ?>
        <div class="messages">
            <?php foreach ($messages as $message): ?>
                <p><?php print $message; ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if (!empty($_SESSION['login'])): ?>
        <form action="index.php?logout=1" method="get" class="logout-form">
            <input type="submit" value="Выйти">
        </form>
    <?php endif; ?>

    <form action="" method="POST">
        <label>ФИО:
            <input name="fio" <?php if ($errors['fio']) { print 'class="error"'; } ?> value="<?php print htmlspecialchars($values['fio']); ?>">
        </label>
        <label>Телефон:
            <input name="phone" type="tel" <?php if ($errors['phone']) { print 'class="error"'; } ?> value="<?php print htmlspecialchars($values['phone']); ?>">
        </label>
        <label>Email:
            <input name="email" type="email" <?php if ($errors['email']) { print 'class="error"'; } ?> value="<?php print htmlspecialchars($values['email']); ?>">
        </label>
        <label>Дата рождения:
            <input name="dob" type="date" <?php if ($errors['dob']) { print 'class="error"'; } ?> value="<?php print htmlspecialchars($values['dob']); ?>">
        </label>
        <div class="radio-group">
            <label>Пол:</label>
            <label><input type="radio" name="gender" value="male" <?php if ($values['gender'] === 'male') { print 'checked'; } ?>> Мужской</label>
            <label><input type="radio" name="gender" value="female" <?php if ($values['gender'] === 'female') { print 'checked'; } ?>> Женский</label>
            <?php if ($errors['gender']) { print '<span class="error">Ошибка</span>'; } ?>
        </div>
        <label>Биография:
            <textarea name="bio" rows="5" <?php if ($errors['bio']) { print 'class="error"'; } ?>><?php print htmlspecialchars($values['bio']); ?></textarea>
        </label>
        <label>Языки программирования:
            <select name="languages[]" multiple <?php if ($errors['languages']) { print 'class="error"'; } ?>>
                <?php
                $stmt = $pdo->query("SELECT name FROM programming_languages");
                while ($row = $stmt->fetch()) {
                    $selected = in_array($row['name'], $values['languages']) ? 'selected' : '';
                    print "<option value='{$row['name']}' $selected>{$row['name']}</option>";
                }
                ?>
            </select>
        </label>
        <div class="checkbox-group">
            <label><input type="checkbox" name="contract" <?php if ($values['contract']) { print 'checked'; } ?>> С контрактом ознакомлен(а)</label>
            <?php if ($errors['contract']) { print '<span class="error">Ошибка</span>'; } ?>
        </div>
        <input type="submit" value="Сохранить">
    </form>

    <?php if (empty($_SESSION['login'])): ?>
        <p style="text-align: center; margin-top: 15px;"><a href="login.php">Войти</a></p>
    <?php endif; ?>
</div>
</body>
</html>
