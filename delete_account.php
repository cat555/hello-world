<?php
declare(strict_types = 1);

require '../include/library.php';
require '../include/filter.php';

userLogin();

$password = $_POST['password'] ?? '';

settype($password, 'string');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    connectDatabase();

    if ((strlen($password) < 5) || (strlen($password) > 30)) {
        $message = 'Invalid password';
    } elseif (!checkFilter(FILTER_DELETE_ACCOUNT)) {
        $message = 'Too many failed attempts. Wait a few minutes';
    } else {
        try {
            $res = $db->query('SELECT state, password FROM users WHERE uid = '.
              $_SESSION['_UID']. ' AND state IN (1, 2) LIMIT 1');
            if ($user = $res->fetch(PDO::FETCH_ASSOC)) {
                if (password_verify($password, $user['password'])) {
                    $res = null;
                    $db->exec('UPDATE users SET state = 3 WHERE uid = '.
                      $_SESSION['_UID']);
                    header('Location: /logout.php');
                    finish();
                } else {
                    $message = 'Invalid password';
                }
            } else {
                $message = 'Error';
            }
            $res = null;
            newFilter(FILTER_DELETE_ACCOUNT);
        } catch(PDOException $e) {
            $message = 'Error';
        }
    }
}

require('../include/header.php');
?>
<form action="/delete_account.php" method="post">
<div class="title"><img class="menu4" src="res/delete.png" alt="" />Delete Account</div>
<?=($message != '' ? '<div class="error">'. $message. '</div>' : '')?><br />
<section>
<p><input type="password" name="password" placeholder="password" tabindex="1" maxLength="30" required autofocus style="width: 100%" /></p>
</section>
<section style="float: right;">
<button type="button" class="otherbutton" tabindex="3" onclick="location.href = '/posts.php'">Cancel</button> &nbsp; &nbsp; 
<button type="submit" tabindex="2">Delete Account</button>
</section><br /><br />
</form>
<?
require('../include/footer.html');
finish();
?>
