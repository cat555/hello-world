<?php
declare(strict_types = 1);

require '../include/library.php';

userLogin();
connectDatabase();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password1 = $_POST['password1'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $password3 = $_POST['password3'] ?? '';

    settype($password1, 'string');
    settype($password2, 'string');
    settype($password3, 'string');

    try {
        // query users
        $res = $db->query('SELECT password FROM users WHERE uid = '.
          $_SESSION['_UID']. ' AND state IN (1, 2) LIMIT 1');
        if (!($user = $res->fetch(PDO::FETCH_ASSOC))) {
            finish();
        }
        $res = null;
    } catch(PDOException $e) {
        finish();
    }

    $blacklistedPasswords = file('../include/blacklisted_passwords.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if (strlen($password1) < 5) {
        $message = 'New password too short';
    } elseif (strlen($password1) > 30) {
        $message = 'New password too long';
    } elseif (in_array($password1, $blacklistedPasswords)) {
        $message = 'New password is too common';
    } elseif ($password1 != $password2) {
        $message = 'Re-typed password not matching';
    } elseif (strlen($password3) < 5 || strlen($password3) > 30) {
        $message = 'Current password not matching';
    } elseif (!password_verify($password3, $user['password'])) {
        $message = 'Current password not matching';
    }

    if ($message === '') {
        try {
            // update users
            $res = $db->prepare('UPDATE users SET password = :password '.
              'WHERE uid = '. $_SESSION['_UID']. ' AND state IN (1, 2)');
            $password1 = password_hash($password1, PASSWORD_DEFAULT);
            $res->bindParam(':password', $password1);
            $res->execute();
            $res = null;
            header('Location: /posts.php');
            finish();
        } catch(PDOException $e) {
            $message = 'Error';
        }
    }
}

require('../include/header.php');
?>
<form action="/change_password.php" method="post">
<div class="title"><img class="menu4" src="res/settings.png" alt="" />Change Password</div>
<?=($message != '' ? '<div class="error">'. $message. '</div>' : '')?><br />
<section>
<p><input type="password" name="password3" placeholder="Current password" tabindex="1" maxLength="30" required autofocus style="width: 100%" /></p>
</section><p />
<section>
<p><input type="password" name="password1" placeholder="New password" tabindex="2" maxLength="30" required style="width: 100%" /></p>
<p><input type="password" name="password2" placeholder="Re-type password" tabindex="3" maxLength="30" required style="width: 100%" /></p>
</section>
<section style="float: right;">
<button type="button" class="otherbutton" tabindex="5" onclick="location = '/posts.php'; return false;">Cancel</button> &nbsp; &nbsp; 
<button type="submit" tabindex="4">Save</button>
</section><br /><br />
</form>
<?
require('../include/footer.html');
finish();
?>
