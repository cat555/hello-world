<?php
declare(strict_types = 1);

require '../include/library.php';
require '../include/filter.php';
require '../include/email.php';

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

$email = $_POST['email'] ?? '';
$code = $_POST['code'] ?? '';
$_SESSION['_CODE'] = $_SESSION['_CODE'] ?? rand(100000, 999999);

settype($email, 'string');
settype($code, 'string');
settype($_SESSION['_CODE'], 'int');

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    connectDatabase();

    $email = strtolower(trim((string)$email));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
      strlen($email) < 6 || strlen($email) > 50) {
        $message = 'Invalid e-mail';
    } elseif (!preg_match('~^[0-9]{6}$~i', $code)) {
        $message = 'Code not mathing the image';
    } else if ($code != $_SESSION['_CODE']) {
        $message = 'Code not mathing the image';
	$_SESSION['_CODE'] = rand(100000, 999999);
    } elseif (!checkFilter(FILTER_RESET_PASSWORD1)) {
        $message = 'Too many password reset attempts. Wait a day or so.';
    } else {
        try {
            $res = $db->prepare('SELECT uid, username FROM users WHERE '.
              'email = :email AND state IN (1, 2) LIMIT 1');
            $res->bindParam(':email', $email);
            $res->execute();
            if ($user = $res->fetch(PDO::FETCH_ASSOC)) {
                $res = null;
                $reset_code = bin2hex(random_bytes(10));
                $res = $db->prepare('INSERT INTO reset_password '.
                  '(uid, email, code) VALUES (:uid, :email, :code)');
                $res->bindParam(':uid', $user['uid']);
                $res->bindParam(':email', $email);
                $res->bindParam(':code', $reset_code);
                $res->execute();
                if ($res->rowCount() != 1) {
                    finish();
                }
            }
            $res = null;
            $_SESSION['_CODE'] = rand(100000, 999999);
            newFilter(FILTER_RESET_PASSWORD1);

            // email
            $body =
              "<p>Password reset for <a href=\"https://play.google.com/store/".
              "apps/details?id=com.findastranger.android\"><b>Find a Stranger".
              "</b></a> app!</p>\n".
              "<p>Your username is <b>". $user['username']. "</b><br />\n".
              "To reset your password, click the following link:</p>\n".
              "<p><a href=\"https://findastranger.com/new_password.php?email=".
              $email. "&code=". $reset_code. "\"><b style=\"color: green\">".
              "https://findastranger.com/new_password.php?email=". $email. "&code=".
              $reset_code. "</b></a></p><p>Visit us at<br /><a href=".
              "\"https://findastranger.com/\">www.findastranger.com</a></p>\n";
            email($email, 'Reset Password - Find a Stranger App', $body);

            require('../include/header.php');
?>
<div class="title"><img class="menu4" src="<?=AMAZON_URL?>res/settings.png" alt="" />Reset Password</div>
<section>
<p>If the e-mail address provided is registered, you will receive a message with instructions to reset your password.</p>
<p>Check both your Inbox and Spam folder for messages.</p>
</section>
<section style="float: right;">
<button type="button" tabindex="1" onclick="location = '/login.php'; return false;">Ok</button> &nbsp; &nbsp; 
</section>
<?php
            require('../include/footer.html');
            finish();
        } catch(PDOException $e) {
            finish();
        }
    }
}

require('../include/header.php');
?>
<form action="/reset_password.php" method="post">
<div class="title"><img class="menu4" src="<?=AMAZON_URL?>res/settings.png" alt="" />Reset Password</div>
<?=($message != '' ? '<div class="error">'. $message. '</div>' : '')?><br />
<section>
<p><input type="text" name="email" placeholder="E-mail address" tabindex="1" maxLength="50" required autofocus style="width: 100%" value="<?=htmlspecialchars($email)?>" /></p>
<p><img src="/res/code.php?rand=<?=rand(1000, 9999)?>" alt="" style="border: 1px #CCC solid" /></p>
<p><input type="text" name="code" placeholder="Re-type above code" tabindex="2" maxLength="6" required style="width: 50%" /></p>
</section>
<section style="float: right;">
<button type="button" class="otherbutton" tabindex="4" onclick="location = '/login.php'; return false;">Cancel</button> &nbsp; &nbsp; 
<button type="submit" tabindex="3">Reset Password</button>
</section><br /><br />
</form>
<?
require('../include/footer.html');
finish();
?>
