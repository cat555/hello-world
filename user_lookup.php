<?php
declare(strict_types = 1);

require '../include/library.php';
require '../include/filter.php';

userLogin();

$username = $_POST['username'] ?? '';

settype($username, 'string');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    connectDatabase();

    $username = strtolower(trim((string)$username));

    if (!preg_match('~^[0-9a-z][0-9_a-z]{3,28}[0-9a-z]$~i', $username)) {
        $message = 'Invalid username';
    } elseif (!checkFilter(FILTER_USER_LOOKUP)) {
        $message = 'Too many lookup attempts. Wait a few minutes';
    } else {
        try {
            $res = $db->prepare('SELECT uid FROM users WHERE username = '.
              ':username AND state IN (1, 2) LIMIT 1');
            $res->bindParam(':username', $username);
            $res->execute();
            if (($user = $res->fetch(PDO::FETCH_ASSOC))) {
                $res = null;
                header('Location: /posts.php?uid='. $user['uid']);
                finish();
            } else {
                $message = 'No users found';
            }
            $res = null;
            newFilter(FILTER_USER_LOOKUP);
        } catch(PDOException $e) {
            $message = 'Error';
        }
    }
}

require('../include/header.php');
?>
<form action="/user_lookup.php" method="post">
<div class="title"><img class="menu4" src="<?=AMAZON_URL?>res/user.png" alt="" />User Lookup</div>
<?=($message != '' ? '<div class="error">'. $message. '</div>' : '')?><br />
<section>
<p><input type="text" name="username" placeholder="username" tabindex="1" maxLength="30" required autofocus style="width: 100%" value="<?=htmlspecialchars($username)?>" /></p>
</section>
<section style="float: right;">
<button type="button" class="otherbutton" tabindex="3" onclick="location.href = '/posts.php'">Cancel</button> &nbsp; &nbsp; 
<button type="submit" tabindex="2">Lookup</button>
</p>
</section><br /><br />
</form>
<?
require('../include/footer.html');
finish();
?>
