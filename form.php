<?php
session_start();

// Инициализируем значения формы
$values = $_SESSION['data'] ?? [
    'fio' => '',
    'phone' => '',
    'email' => '',
    'dob' => '',
    'gender' => '',
    'bio' => '',
    'languages' => [],
    'contract' => 0
];

$errors = $_SESSION['errors'] ?? []; // Извлекаем ошибки из сессии, если они есть
unset($_SESSION['errors'], $_SESSION['data']); // Очищаем данные после использования
?>

<!DOCTYPE html>
<html lang="ru">
<head>
  <meta charset="UTF-8">
  <title>Форма заявки</title>
  <style>
    /* Стили остаются без изменений */
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
