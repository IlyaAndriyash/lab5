<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Форма заявки</title>
  <style>
    body {
      font-family: sans-serif;
      margin: 2em;
    }
    .error {
      border: 2px solid red;
    }
    .messages {
      padding: 10px;
      margin-bottom: 15px;
      background-color: #f0f0f0;
      border-left: 4px solid #999;
    }
  </style>
</head>
<body>
  <?php if (!empty($messages)) : ?>
    <div class="messages">
      <?php foreach ($messages as $msg) echo "<p>$msg</p>"; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['login'])) : ?>
    <form action="index.php?logout=1" method="get">
      <button type="submit" name="logout" value="1">Выйти</button>
    </form>
  <?php endif; ?>

  <form action="index.php" method="POST">
    <label>ФИО:<br />
      <input name="fio" value="<?= htmlspecialchars($values['fio']) ?>"
             class="<?= $errors['fio'] ? 'error' : '' ?>" />
    </label><br><br>

    <label>Телефон:<br />
      <input name="phone" value="<?= htmlspecialchars($values['phone']) ?>"
             class="<?= $errors['phone'] ? 'error' : '' ?>" />
    </label><br><br>

    <label>Email:<br />
      <input name="email" value="<?= htmlspecialchars($values['email']) ?>"
             class="<?= $errors['email'] ? 'error' : '' ?>" />
    </label><br><br>

    <label>Дата рождения:<br />
      <input type="date" name="dob" value="<?= htmlspecialchars($values['dob']) ?>"
             class="<?= $errors['dob'] ? 'error' : '' ?>" />
    </label><br><br>

    <label>Пол:<br />
      <input type="radio" name="gender" value="male"
        <?= $values['gender'] === 'male' ? 'checked' : '' ?> /> Мужской
      <input type="radio" name="gender" value="female"
        <?= $values['gender'] === 'female' ? 'checked' : '' ?> /> Женский
    </label><br><br>

    <label>Биография:<br />
      <textarea name="bio" rows="5" cols="40"
                class="<?= $errors['bio'] ? 'error' : '' ?>"><?= htmlspecialchars($values['bio']) ?></textarea>
    </label><br><br>

    <label>Языки программирования:<br />
      <?php
      try {
        $pdo = new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $stmt = $pdo->query("SELECT name FROM programming_languages");
        $all_langs = $stmt->fetchAll(PDO::FETCH_COLUMN);

        foreach ($all_langs as $lang) {
          $checked = in_array($lang, $values['languages']) ? 'checked' : '';
          echo "<label><input type='checkbox' name='languages[]' value='$lang' $checked /> $lang</label><br />";
        }
      } catch (PDOException $e) {
        echo "<p>Ошибка загрузки языков: " . $e->getMessage() . "</p>";
      }
      ?>
    </label><br>

    <label>
      <input type="checkbox" name="contract" <?= $values['contract'] ? 'checked' : '' ?> />
      С контрактом ознакомлен(а)
    </label><br><br>

    <button type="submit">Сохранить</button>
  </form>
</body>
</html>
