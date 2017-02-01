<?php
declare(strict_types = 1);

require '../include/library.php';
require '../include/filter.php';

if (isset($_SESSION['_UID'])) {
    if ($_SESSION['_UID'] >= 1 && $_SESSION['_UID'] <= 4294967295) {
        finish('/menu.php');
    }
} else {
    $_SESSION['_UID'] = 0;
}

if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android') === false ||
  strpos($_SERVER['HTTP_USER_AGENT'], '; wv)') === false) {
    finish('/');
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

settype($username, 'string');
settype($password, 'string');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    connectDatabase();

    $username = strtolower(trim((string)$username));
    $blacklistedPasswords = file('../include/blacklisted_passwords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (!preg_match('~^[0-9a-z][0-9_a-z]{3,28}[0-9a-z]$~i', $username) ||
      strlen($username) < 6 || strlen($username) > 30) {
        $message = 'Invalid username';
    } elseif ((strlen($password) < 5) || (strlen($password) > 30)) {
        $message = 'Invalid password';
    } elseif (in_array($password, $blacklistedPasswords)) {
        $message = 'New password is too common';
    } elseif (!checkFilter(FILTER_REGISTER)) {
        $message = 'Too many registrations from your device';
    } else {
        try {
            // query users
            $res = $db->prepare('SELECT EXISTS(SELECT * FROM users '.
              'WHERE username = :username LIMIT 1)');
            $res->bindParam(':username', $username);
            $res->execute();
            if ($res->fetch(PDO::FETCH_NUM)[0]) {
                $message = 'Username already taken';
            }
            $res = null;
        } catch(PDOException $e) {
            finish();
        }
    }

    if ($message == '') {
        newFilter(FILTER_REGISTER);

        // insert users
        $password = password_hash($password, PASSWORD_DEFAULT);
        $res = $db->prepare('INSERT INTO users (state, username, password, ip1)'.
          ' VALUES (2, :username, :password, :ip1)');
        $res->bindParam(':username', $username);
        $res->bindParam(':password', $password);
        $res->bindParam(':ip1', $_SERVER['REMOTE_ADDR']);
        $res->execute();

        $_SESSION['_UID'] = (int)$db->lastInsertId();
        $_SESSION['_STATE'] = 2;
        $_SESSION['_USERNAME'] = $username;
        $_SESSION['_NOTIFICATIONS'] = ['state', 'email', 'name', 'age', 'sex',
          'post', 'photo'];

        settype($_SESSION['_UID'], 'int');
        settype($_SESSION['_STATE'], 'int');
        settype($_SESSION['_USERNAME'], 'string');
        settype($_SESSION['_NOTIFICATIONS'], 'array');

        header('Location: /login.php');
        finish();
    }
}

require('../include/header.php');
?>
<form action="/register.php" method="post">
<div class="title"><img class="menu4" src="<?=AMAZON_URL?>res/settings.png" alt="" />Register</div>
<?=($message != '' ? '<div class="error">'. $message. '</div>' : '')?><br />
<section>
<p><input type="text" name="username" placeholder="create username" tabindex="1" maxLength="30" required autofocus style="width: 100%" value="<?=htmlspecialchars($username)?>" /></p>
<p><input type="text" name="password" placeholder="create password" tabindex="2" maxLength="30" required style="width: 100%" value="<?=htmlspecialchars($password)?>" /></p>
</section>
<section>
<p style="float: right"><img src="<?=AMAZON_URL?>res/ok.png" alt="" style="height: 14px;" /> <a href="/terms.php"><b>I AGREE</b> with the <b>Terms of Service</b></a></p>
</section>
<section style="float: right;">
<button type="button" class="otherbutton" tabindex="4" onclick="location = '/login.php'; return false;">Login</button> &nbsp; &nbsp; 
<button type="submit" tabindex="3">Register</button>
</section><br /><br />
</form>
<?
require('../include/footer.html');
finish();
?>
