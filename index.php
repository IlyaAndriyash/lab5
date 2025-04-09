<?php
header('Content-Type: text/html; charset=UTF-8');

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();

    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', time() - 3600);
        setcookie('login', '', time() - 3600);
        setcookie('pass', '', time() - 3600);
        $messages[] = 'Спасибо, результаты сохранены.';
        if (!empty($_COOKIE['pass'])) {
            $messages[] = sprintf(
                'Вы можете <a href="login.php">войти</a> с логином <strong>%s</strong> и паролем <strong>%s</strong> для изменения данных.',
                strip_tags($_COOKIE['login']),
                strip_tags($_COOKIE['pass'])
            );
        }
    }

    $errors = array();
    $errors['fio'] = !empty($_COOKIE['fio_error']);
    $errors['phone'] = !empty($_COOKIE['phone_error']);
    $errors['email'] = !empty($_COOKIE['email_error']);
    $errors['dob'] = !empty($_COOKIE['dob_error']);
    $errors['gender'] = !empty($_COOKIE['gender_error']);
    $errors['languages'] = !empty($_COOKIE['languages_error']);
    $errors['bio'] = !empty($_COOKIE['bio_error']);
    $errors['contract'] = !empty($_COOKIE['contract_error']);

    if ($errors['fio']) { setcookie('fio_error', '', time() - 3600); $messages[] = '<div class="error">Заполните ФИО.</div>'; }
    if ($errors['phone']) { setcookie('phone_error', '', time() - 3600); $messages[] = '<div class="error">Некорректный телефон.</div>'; }
    if ($errors['email']) { setcookie('email_error', '', time() - 3600); $messages[] = '<div class="error">Некорректный email.</div>'; }
    if ($errors['dob']) { setcookie('dob_error', '', time() - 3600); $messages[] = '<div class="error">Некорректная дата рождения.</div>'; }
    if ($errors['gender']) { setcookie('gender_error', '', time() - 3600); $messages[] = '<div class="error">Выберите пол.</div>'; }
    if ($errors['languages']) { setcookie('languages_error', '', time() - 3600); $messages[] = '<div class="error">Выберите хотя бы один язык.</div>'; }
    if ($errors['bio']) { setcookie('bio_error', '', time() - 3600); $messages[] = '<div class="error">Заполните биографию.</div>'; }
    if ($errors['contract']) { setcookie('contract_error', '', time() - 3600); $messages[] = '<div class="error">Ознакомьтесь с контрактом.</div>'; }

    $values = array();
    $values['fio'] = empty($_COOKIE['fio_value']) ? '' : strip_tags($_COOKIE['fio_value']);
    $values['phone'] = empty($_COOKIE['phone_value']) ? '' : strip_tags($_COOKIE['phone_value']);
    $values['email'] = empty($_COOKIE['email_value']) ? '' : strip_tags($_COOKIE['email_value']);
    $values['dob'] = empty($_COOKIE['dob_value']) ? '' : strip_tags($_COOKIE['dob_value']);
    $values['gender'] = empty($_COOKIE['gender_value']) ? '' : strip_tags($_COOKIE['gender_value']);
    $values['languages'] = empty($_COOKIE['languages_value']) ? array() : json_decode($_COOKIE['languages_value'], true);
    $values['bio'] = empty($_COOKIE['bio_value']) ? '' : strip_tags($_COOKIE['bio_value']);
    $values['contract'] = !empty($_COOKIE['contract_value']);

    if (empty($errors) && !empty($_SESSION['login'])) {
        $db = new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ]);
        $stmt = $db->prepare("SELECT * FROM applications WHERE id = (SELECT application_id FROM users WHERE login = ?)");
        $stmt->execute([$_SESSION['login']]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($data) {
            $values['fio'] = strip_tags($data['fio']);
            $values['phone'] = strip_tags($data['phone']);
            $values['email'] = strip_tags($data['email']);
            $values['dob'] = strip_tags($data['dob']);
            $values['gender'] = strip_tags($data['gender']);
            $values['bio'] = strip_tags($data['bio']);
            $values['contract'] = $data['contract'];

            $stmt = $db->prepare("SELECT pl.name FROM programming_languages pl JOIN application_languages al ON pl.id = al.language_id WHERE al.application_id = ?");
            $stmt->execute([$data['id']]);
            $values['languages'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
        }
        printf('Вход с логином %s, uid %d', $_SESSION['login'], $_SESSION['uid']);
    }

    include('form.php');
} else {
    $errors = FALSE;

    if (empty($_POST['fio']) || !preg_match('/^[a-zA-Zа-яА-Я\s]{1,150}$/u', $_POST['fio'])) {
        setcookie('fio_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('fio_value', $_POST['fio'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['phone']) || !preg_match('/^\+?\d{10,15}$/', $_POST['phone'])) {
        setcookie('phone_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('phone_value', $_POST['phone'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['email']) || !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        setcookie('email_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('email_value', $_POST['email'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['dob']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_POST['dob'])) {
        setcookie('dob_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('dob_value', $_POST['dob'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['gender']) || !in_array($_POST['gender'], ['male', 'female'])) {
        setcookie('gender_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('gender_value', $_POST['gender'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['languages'])) {
        setcookie('languages_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('languages_value', json_encode($_POST['languages']), time() + 30 * 24 * 60 * 60);

    if (empty($_POST['bio'])) {
        setcookie('bio_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('bio_value', $_POST['bio'], time() + 30 * 24 * 60 * 60);

    if (empty($_POST['contract'])) {
        setcookie('contract_error', '1', time() + 24 * 60 * 60);
        $errors = TRUE;
    }
    setcookie('contract_value', $_POST['contract'], time() + 30 * 24 * 60 * 60);

    if ($errors) {
        header('Location: index.php');
        exit();
    } else {
        setcookie('fio_error', '', time() - 3600);
        setcookie('phone_error', '', time() - 3600);
        setcookie('email_error', '', time() - 3600);
        setcookie('dob_error', '', time() - 3600);
        setcookie('gender_error', '', time() - 3600);
        setcookie('languages_error', '', time() - 3600);
        setcookie('bio_error', '', time() - 3600);
        setcookie('contract_error', '', time() - 3600);
    }

    $db = new PDO('mysql:host=localhost;dbname=u68818', 'u68818', '9972335', [
        PDO::ATTR_PERSISTENT => true,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    try {
        $db->beginTransaction();

        if (!empty($_SESSION['login'])) {
            $stmt = $db->prepare("UPDATE applications SET fio = ?, phone = ?, email = ?, dob = ?, gender = ?, bio = ?, contract = ? WHERE id = (SELECT application_id FROM users WHERE login = ?)");
            $stmt->execute([$_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['dob'], $_POST['gender'], $_POST['bio'], isset($_POST['contract']) ? 1 : 0, $_SESSION['login']]);

            $stmt = $db->prepare("SELECT application_id FROM users WHERE login = ?");
            $stmt->execute([$_SESSION['login']]);
            $application_id = $stmt->fetchColumn();

            $db->prepare("DELETE FROM application_languages WHERE application_id = ?")->execute([$application_id]);
        } else {
            $stmt = $db->prepare("INSERT INTO applications (fio, phone, email, dob, gender, bio, contract) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$_POST['fio'], $_POST['phone'], $_POST['email'], $_POST['dob'], $_POST['gender'], $_POST['bio'], isset($_POST['contract']) ? 1 : 0]);
            $application_id = $db->lastInsertId();

            $login = substr(md5(uniqid(rand(), true)), 0, 8);
            $pass = substr(md5(uniqid(rand(), true)), 0, 8);
            $pass_hash = md5($pass);
            $stmt = $db->prepare("INSERT INTO users (login, password_hash, application_id) VALUES (?, ?, ?)");
            $stmt->execute([$login, $pass_hash, $application_id]);

            setcookie('login', $login);
            setcookie('pass', $pass);
        }

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
            $linkStmt->execute([$application_id, $language_id]);
        }

        $db->commit();
        setcookie('save', '1');
        header('Location: index.php');
    } catch (PDOException $e) {
        $db->rollBack();
        print('Ошибка: ' . $e->getMessage());
        exit();
    }
}
?>
