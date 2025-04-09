<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Форма заявки</title>
    <style>
        .error { border: 2px solid red; }
        .messages { background-color: #fff0cc; border-left: 4px solid #ffa500; padding: 10px; margin-bottom: 20px; }
        body { font-family: Arial, sans-serif; }
        input, select, textarea { margin-bottom: 10px; padding: 5px; }
        input[type="submit"] { background-color: #007bff; color: white; border: none; padding: 10px; }
    </style>
</head>
<body>
<?php
if (!empty($messages)) {
    print('<div class="messages">');
    foreach ($messages as $message) {
        print($message);
    }
    print('</div>');
}
?>

<?php if (!empty($_SESSION['login'])): ?>
    <form action="index.php?logout=1" method="get">
        <input type="submit" value="Выйти">
    </form>
<?php endif; ?>

<form action="" method="POST">
    <label>ФИО:
        <input name="fio" <?php if ($errors['fio']) { print 'class="error"'; } ?> value="<?php print htmlspecialchars($values['fio']); ?>">
    </label><br>
    <label>Телефон:
        <input name="phone" type="tel" <?php if ($errors['phone']) { print 'class="error"'; } ?> value="<?php print htmlspecialchars($values['phone']); ?>">
    </label><br>
    <label>Email:
        <input name="email" type="email" <?php if ($errors['email']) { print 'class="error"'; } ?> value="<?php print htmlspecialchars($values['email']); ?>">
    </label><br>
    <label>Дата рождения:
        <input name="dob" type="date" <?php if ($errors['dob']) { print 'class="error"'; } ?> value="<?php print htmlspecialchars($values['dob']); ?>">
    </label><br>
    <label>Пол:
        <input type="radio" name="gender" value="male" <?php if ($values['gender'] === 'male') { print 'checked'; } ?>> Мужской
        <input type="radio" name="gender" value="female" <?php if ($values['gender'] === 'female') { print 'checked'; } ?>> Женский
        <?php if ($errors['gender']) { print '<span class="error">Ошибка</span>'; } ?>
    </label><br>
    <label>Биография:
        <textarea name="bio" <?php if ($errors['bio']) { print 'class="error"'; } ?>><?php print htmlspecialchars($values['bio']); ?></textarea>
    </label><br>
    <label>Языки программирования:
        <select name="languages[]" multiple size="4" <?php if ($errors['languages']) { print 'class="error"'; } ?>>
            <?php
            $stmt = $pdo->query("SELECT name FROM programming_languages");
            while ($row = $stmt->fetch()) {
                $selected = in_array($row['name'], $values['languages']) ? 'selected' : '';
                print "<option value='{$row['name']}' $selected>{$row['name']}</option>";
            }
            ?>
        </select>
    </label><br>
    <label>
        <input type="checkbox" name="contract" <?php if ($values['contract']) { print 'checked'; } ?>> С контрактом ознакомлен(а)
        <?php if ($errors['contract']) { print '<span class="error">Ошибка</span>'; } ?>
    </label><br>
    <input type="submit" value="Сохранить">
</form>

<?php if (empty($_SESSION['login'])): ?>
    <p><a href="login.php">Войти</a></p>
<?php endif; ?>
</body>
</html>
