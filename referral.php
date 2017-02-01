<?php
declare(strict_types = 1);

require '../include/library.php';

userLogin();

if ($_SESSION['_STATE'] != 2) {
    header('Location: /posts.php');
    finish();
}

$username = '';
settype($username, 'string');

connectDatabase();

try {
    $uid = 0;
    $res = $db->query('SELECT uid1, uid2 FROM referrals WHERE uid1 = '.
      $_SESSION['_UID']. ' LIMIT 1');
    if (($fetch = $res->fetch(PDO::FETCH_ASSOC))) {
        $uid = $fetch['uid1'];
    } else {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $username = $_POST['username'] ?? '';
            $username = strtolower(trim((string)$username));
            if (!preg_match('~^[0-9a-z][0-9_a-z]{3,28}[0-9a-z]$~i',
              $username)) {
                $message = 'Invalid username';
            } else {
                $res = null;
                $res = $db->prepare('SELECT uid FROM users WHERE username = '.
                  ':username AND state IN (1, 2) LIMIT 1');
                $res->bindParam(':username', $username);
                $res->execute();
                if (($fetch = $res->fetch(PDO::FETCH_ASSOC))) {
                    $uid = $fetch['uid'];
                    $db->exec('INSERT INTO referrals (uid1, uid2) VALUES ('.
                      $_SESSION['_UID']. ', '. $uid. ')');

                    // check
                    $res = null;
                    $res = $db->query('SELECT COUNT(*) AS count FROM '.
                      'referrals WHERE uid2 = '. $uid);
                    if (($fetch = $res->fetch(PDO::FETCH_ASSOC))) {
                        if ($fetch['count'] == 2) {
                            $db->exec('UPDATE users SET state = 1 WHERE uid = '.
                                $uid. ' AND state = 2');
                        }
                    }
                } else {
                    $message = 'Username not registered';
                }
            }
        }
    }
    $res = null;
} catch(PDOException $e) {
    $res = null;
    $message = 'Error';
}

require('../include/header.php');
?>
<form action="/referral.php" method="post">
<div class="title"><img class="menu4" src="<?=AMAZON_URL?>res/user.png" alt="" />Referral</div>
<?=($message != '' ? '<div class="error">'. $message. '</div>' : '')?><br />
<section>
<p>Tell <b>two friends</b> about this app, in order to unlock all the features, like discovering people near you, and being able to see more posts related to yours.</p>
<p>After your friends sign up, ask them to enter <b>your username</b> in the <b>referral</b> section below, in their app.</p>
</section>
<?php
if ($uid == 0) {
?>
<section>
<p>Who told you about this app?</p>
<p><input type="text" name="username" placeholder="username of existing user" tabindex="1" maxLength="30" required autofocus style="width: 100%" value="<?=htmlspecialchars($username)?>" /></p>
</section>
<section style="float: right;">
<button type="button" class="otherbutton" tabindex="3" onclick="location = '/posts.php'; return false;">Cancel</button> &nbsp; &nbsp; 
<button type="submit" tabindex="2">Submit</button>
</section>
<div style="clear: both"></div><br />
</form>
<?php
}
?>
</form>

<?
require('../include/footer.html');
finish();
?>
