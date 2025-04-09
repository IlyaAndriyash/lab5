<?php
if (!isset($values)) {
    $values = [
        'fio' => '',
        'phone' => '',
        'email' => '',
        'dob' => '',
        'gender' => '',
        'languages' => [],
        'bio' => '',
        'contract' => false
    ];
}

if (!isset($errors)) {
    $errors = [
        'fio' => false,
        'phone' => false,
        'email' => false,
        'dob' => false,
        'gender' => false,
        'languages' => false,
        'bio' => false,
        'contract' => false
    ];
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
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

        .error-message {
            color: red;
            font-size: 0.9em;
            margin-bottom: 10px;
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
      <?php foreach ($messages as $msg) echo "<p>$msg</p>"; ?>
    </div>
  <?php endif; ?>

  <?php if (!empty($_SESSION['login'])): ?>
    <form action="index.php?logout=1" method="get">
      <input type="submit" name="logout" value="Выйти">
    </form>
  <?php endif; ?>

  <form action="index.php" method="POST">
    <label>ФИО:
      <input type="text" name="fio" value="<?= htmlspecialchars($values['fio']) ?>" class="<?= $errors['fio'] ? 'error' : '' ?>">
    </label>

    <label>Телефон:
      <input type="tel" name="phone" value="<?= htmlspecialchars($values['phone']) ?>" class="<?= $errors['phone'] ? 'error' : '' ?>">
    </label>

    <label>Email:
      <input type="email" name="email" value="<?= htmlspecialchars($values['email']) ?>" class="<?= $errors['email'] ? 'error' : '' ?>">
    </label>

    <label>Дата рождения:
      <input type="date" name="dob" value="<?= htmlspecialchars($values['dob']) ?>" class="<?= $errors['dob'] ? 'error' : '' ?>">
    </label>

    <div class="radio-group">
      <label><input type="radio" name="gender" value="male" <?= $values['gender'] === 'male' ? 'checked' : '' ?>> Мужской</label>
      <label><input type="radio" name="gender" value="female" <?= $values['gender'] === 'female' ? 'checked' : '' ?>> Женский</label>
    </div>

    <label>Биография:
      <textarea name="bio" rows="5" class="<?= $errors['bio'] ? 'error' : '' ?>"><?= htmlspecialchars($values['bio']) ?></textarea>
    </label>

    <label>Языки программирования:
      <select name="languages[]" multiple size="4" class="<?= $errors['languages'] ? 'error' : '' ?>">
        <?php
        try {
          $pdo = new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335');
          $stmt = $pdo->query("SELECT name FROM programming_languages");
          $all_langs = $stmt->fetchAll(PDO::FETCH_COLUMN);

          foreach ($all_langs as $lang) {
            $selected = in_array($lang, $values['languages']) ? 'selected' : '';
            echo "<option value='$lang' $selected>$lang</option>";
          }
        } catch (PDOException $e) {
          echo "<option disabled>Ошибка загрузки языков</option>";
        }
        ?>
      </select>
    </label>

    <div class="checkbox-group">
      <input type="checkbox" name="contract" <?= $values['contract'] ? 'checked' : '' ?>>
      <label>С контрактом ознакомлен(а)</label>
    </div>

    <input type="submit" value="Сохранить">
  </form>
</div>

</body>
</html>
