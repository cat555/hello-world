<?php
declare(strict_types = 1);

require '../include/library.php';
require '../include/filter.php';

if (isset($_SESSION['_UID'])) {
    if ($_SESSION['_UID'] >= 1 && $_SESSION['_UID'] <= 4294967295) {
        finish('https://findastranger.com/');
    }
}

$email = $_GET['email'] ?? '';
$code = $_GET['code'] ?? '';

settype($email, 'string');
settype($code, 'string');

$email = strtolower(trim((string)$email));
$code = strtolower(trim((string)$code));
$message = '';

connectDatabase();

if (!filter_var($email, FILTER_VALIDATE_EMAIL) ||
  strlen($email) < 6 || strlen($email) > 50) {
    $message = 'Invalid e-mail';
} elseif (!preg_match('~^[0-9a-f]{20}$~i', $code)) {
    $message = 'Invalid verification code';
} elseif (!checkFilter(FILTER_RESET_PASSWORD2)) {
    $message = 'Too many password reset attempts. Wait a day or so.';
} else {
    try {
        $res = $db->prepare('SELECT uid FROM reset_password WHERE email = '.
          ':email AND code = :code AND time > NOW() - INTERVAL 1 DAY LIMIT 1');
        $res->bindParam(':email', $email);
        $res->bindParam(':code', $code);
        $res->execute();
        if ($reset = $res->fetch(PDO::FETCH_ASSOC)) {
            $res = null;
            $password = bin2hex(random_bytes(5));
            $password_enc = password_hash($password, PASSWORD_DEFAULT);
            $res = $db->prepare('UPDATE users set password = :password '.
              ' WHERE uid = :uid AND email = :email AND state IN (1, 2)');
            $res->bindParam(':password', $password_enc);
            $res->bindParam(':uid', $reset['uid']);
            $res->bindParam(':email', $email);
            $res->execute();
            if ($res->rowCount() != 1) {
                $message = 'Invalid e-mail and verification code combination';
            } else {
                $res = null;
                $db->exec('DELETE FROM reset_password WHERE uid = '.
                  $reset['uid']);

                require('../include/header.php');
?>
<div class="title"><img class="menu4" src="<?=AMAZON_URL?>res/settings.png" alt="" />Reset Password</div>
<section>
<p>Your new password is <b><?=$password?></b></p>
<p>Once you login in the app with the new password, make sure to change it again with a password easier for you to remember.</p>
<p>Visit us at<br /><a href=https://findastranger.com/">www.findastranger.com</a></p>
</section>
<section style="float: right;">
<button type="button" tabindex="1" onclick="location = 'https://findastranger.com/'; return false;">Ok</button> &nbsp; &nbsp; 
</section>
<?
                require('../include/footer.html');
                finish();
            }
        }
        $res = null;
        newFilter(FILTER_RESET_PASSWORD2);
    } catch(PDOException $e) {
        finish();
    }
}

require('../include/header.php');
?>
<div class="title"><img class="menu4" src="<?=AMAZON_URL?>res/settings.png" alt="" />Reset Password</div>
<div class="error"><?=$message?></div><br />
<section>
xxx
</section>
<?
require('../include/footer.html');
finish();
?>
