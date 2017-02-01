<?php
declare(strict_types = 1);

require '../include/library.php';
require '../include/filter.php';

userLogin();
connectDatabase();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
} else {
    try {
        // query users
        $res = $db->query('SELECT email FROM users WHERE uid = '.
          $_SESSION['_UID']. ' AND state IN (1, 2) LIMIT 1');
        if (!($user = $res->fetch(PDO::FETCH_ASSOC))) {
            finish();
        }
        $res = null;
        $email = $user['email'];
    } catch(PDOException $e) {
        finish();
    }
}

settype($email, 'string');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = strtolower(trim($email));
    if ($email != '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 50) {
            $message = 'Invalid e-mail address';
        } elseif (!checkFilter(FILTER_CHANGE_EMAIL)) {
            $message = 'Too many attempts. Wait a few minutes';
        } else {
            try {
                // check if e-mail used by different account
                $res = $db->prepare('SELECT EXISTS(SELECT * FROM users WHERE '.
                  'email = :email AND uid != '. $_SESSION['_UID']. ' LIMIT 1)');
                $res->bindParam(':email', $email);
                $res->execute();
                if ($res->fetch(PDO::FETCH_NUM)[0]) {
                    $message = 'E-mail already used by a different user';
                }
                $res = null;
            } catch(PDOException $e) {
                $message = 'Error';
            }
        }
    }

    if ($message == '') {
        try {
            // update users
            $res = $db->prepare('UPDATE users SET email = :email '.
              'WHERE uid = '. $_SESSION['_UID']. ' AND state IN (1, 2)');
            $res->bindParam(':email', $email);
            $res->execute();
            $res = null;

            $_SESSION['_NOTIFICATIONS'] =
              array_diff($_SESSION['_NOTIFICATIONS'], ['email']);
            if ($email == '') {
                $_SESSION['_NOTIFICATIONS'][] = 'email';
            }

            header('Location: /posts.php');
            finish();
        } catch(PDOException $e) {
            $message = 'Error';
        }
    } else {
        newFilter(FILTER_CHANGE_EMAIL);
    }
}

require('../include/header.php');
?>
<form action="/change_email.php" method="post">
<div class="title"><img class="menu4" src="res/settings.png" alt="" />Recovery E-mail</div>
<?=($message != '' ? '<div class="error">'. $message. '</div>' : '')?><br />
<section>
<p><input type="text" name="email" placeholder="E-mail address, for recovery" tabindex="1" maxLength="50" autofocus style="width: 100%" value="<?=htmlspecialchars($email)?>" /></p>
</section>
<section style="float: right;">
<button type="button" class="otherbutton" tabindex="3" onclick="location = '/posts.php'; return false;">Cancel</button> &nbsp; &nbsp; 
<button type="submit" tabindex="2">Save</button>
</section><br /><br />
</form>
<?
require('../include/footer.html');
finish();
?>
