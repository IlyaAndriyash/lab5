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
        
        input[type="submit"] {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        
        input[type="submit"]:hover {
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
        
        .messages {
            background-color: #fff0cc;
            border-left: 4px solid #ffa500;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <?php if (!empty($messages)): ?>
            <div class="messages">
                <?php foreach ($messages as $message): ?>
                    <p><?php print $message; ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['login'])): ?>
            <form action="index.php?logout=1" method="get">
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
                <?php if ($errors['gender']) { print '<span style="color: red;">Ошибка</span>'; } ?>
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
                <?php if ($errors['contract']) { print '<span style="color: red;">Ошибка</span>'; } ?>
            </div>
            <input type="submit" value="Сохранить">
        </form>

        <?php if (empty($_SESSION['login'])): ?>
            <p><a href="login.php">Войти</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
