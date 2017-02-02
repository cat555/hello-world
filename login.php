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
//    finish('/');
}

// retrieve username and password
$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

settype($username, 'string');
settype($password, 'string');

// error message
$message = '';

// process login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    connectDatabase();

    $username = strtolower(trim((string)$username));

    if ((
      !preg_match('~^[0-9a-z][0-9_a-z]{3,28}[0-9a-z]$~i', $username)
      &&
      !filter_var($username, FILTER_VALIDATE_EMAIL)
      ) || strlen($username) < 6 || strlen($username) > 50) {
        $message = 'Invalid username or e-mail';
    } elseif ((strlen($password) < 5) || (strlen($password) > 30)) {
        $message = 'Invalid password';
    } elseif (!checkFilter(FILTER_LOGIN)) {
        $message = 'Too many login attempts. Wait a few minutes';
    } else {
        try {
            $res = $db->prepare('SELECT uid, state, password, email, name, '.
              'age, sex, posts FROM users WHERE (username = :username OR '.
              ' email = :username) AND state IN (1, 2) LIMIT 1');
            $res->bindParam(':username', $username);
            $res->execute();
            if ($user = $res->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $user['password'])) {
                    $res = null;
                    $res = $db->prepare('UPDATE users SET time2 = '.
                      'CURRENT_TIMESTAMP, ip2 = :ip2 WHERE uid = '.
                      $user['uid']);
                    $res->bindParam(':ip2', $_SERVER['REMOTE_ADDR']);
                    $res->execute();
                    if ($res->rowCount() != 1) {
                        finish();
                    }
                    $res = null;

                    $db->exec('DELETE FROM reset_password WHERE uid = '.
                      $_SESSION['_UID']);

                    $_SESSION['_UID'] = (int)$user['uid'];
                    $_SESSION['_STATE'] = (int)$user['state'];
                    $_SESSION['_USERNAME'] = (string)$user['username'];
                    $_SESSION['_NOTIFICATIONS'] = [];

                    settype($_SESSION['_UID'], 'int');
                    settype($_SESSION['_STATE'], 'int');
                    settype($_SESSION['_USERNAME'], 'string');
                    settype($_SESSION['_NOTIFICATIONS'], 'array');

                    // notifications from mysql table users
                    if ($_SESSION['_STATE'] == 2) {
                        $_SESSION['_NOTIFICATIONS'][] = 'state';
                    }
                    if ($user['email'] == '') {
                        $_SESSION['_NOTIFICATIONS'][] = 'email';
                    }
                    if ($user['name'] == '') {
                        $_SESSION['_NOTIFICATIONS'][] = 'name';
                    }
                    if ($user['age'] == 0) {
                        $_SESSION['_NOTIFICATIONS'][] = 'age';
                    }
                    if ($user['sex'] == 0) {
                        $_SESSION['_NOTIFICATIONS'][] = 'sex';
                    }
                    if ($user['posts'] < 3) {
                        $_SESSION['_NOTIFICATIONS'][] = 'post';
                        $_SESSION['_COUNTER'] = 3 - $user['posts'];
                    }

/*
                    // i need to install openssl in the php container
                    // notification from amazon
                    if (@fopen(AMAZON_URL.
                      'u/'. $_SESSION['_UID']. '.jpg', 'r') == false) {
                        $_SESSION['_NOTIFICATIONS'][] = 'photo';
                    }
*/

                  finish('/login.php');
                }
            }
            $res = null;
            newFilter(FILTER_LOGIN);
            $message = 'Incorrect username/e-mail and/or password<br /><a href="/reset_password.php">Reset Password</a>';
        } catch(PDOException $e) {
            finish();
        }
    }
}

require('../include/header.php');
?>
<form action="/login.php" method="post">
<div class="title"><img class="menu4" src="<?=AMAZON_URL?>res/settings.png" alt="" />Login</div>
<?=($message != '' ? '<div class="error">'. $message. '</div>' : '')?><br />
<section>
<p><input type="text" name="username" placeholder="username or e-mail" tabindex="1" maxLength="30" required autofocus style="width: 100%" value="<?=htmlspecialchars($username)?>" /></p>
<p><input type="password" name="password" placeholder="password" tabindex="2" maxLength="30" required style="width: 100%" /></p>
</section>
<section style="float: right;">
<button type="button" class="otherbutton" tabindex="4" onclick="location = '/register.php'; return false;">Register</button> &nbsp; &nbsp; 
<button type="submit" tabindex="3">Login</button>
</section><br /><br />
</form>
<?
require('../include/footer.html');
finish();
?>
