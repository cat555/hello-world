<?php
declare(strict_types = 1);

require '../include/library.php';

userLogin();
connectDatabase();

$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = '';
    $name = $_POST['name'] ?? '';
    $age = $_POST['age'] ?? '';
    $sex = $_POST['sex'] ?? '';

    $name = strip_tags($name);
    $name = addslashes($name);
} else {
    try {
        // query users
        $res = $db->query('SELECT username, name, age, sex FROM users WHERE uid = '.
          $_SESSION['_UID']. ' AND state IN (1, 2) LIMIT 1');
        if (!($user = $res->fetch(PDO::FETCH_ASSOC))) {
            finish();
        }
        $res = null;
    } catch(PDOException $e) {
        finish();
    }
    $username = $user['username'];
    $name = $user['name'];
    $age = $user['age'];
    $sex = $user['sex'];
}

settype($name, 'string');
settype($age, 'int');
settype($sex, 'int');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (strlen($name) > 50) {
        $message = 'Name is too long';
    } elseif ($age != 0 && ($age < 15 || $age > 99)) {
        $message = 'You must be at least 15 years old';
    } elseif ($sex < 0 || $sex > 2) {
        $sex = 0;
    }

    if ($message === '') {
        try {
            // update users
            $res = $db->prepare('UPDATE users SET name = :name, age = :age, '.
              'sex = :sex WHERE uid = '. $_SESSION['_UID'].
              ' AND state IN (1, 2)');
            $res->bindParam(':name', $name);
            $res->bindParam(':age', $age);
            $res->bindParam(':sex', $sex);
            $res->execute();

            $_SESSION['_NOTIFICATIONS'] =
              array_diff($_SESSION['_NOTIFICATIONS'], ['name', 'age', 'sex']);
            if ($name == '') {
                $_SESSION['_NOTIFICATIONS'][] = 'name';
            }
            if ($age == 0) {
                $_SESSION['_NOTIFICATIONS'][] = 'age';
            }
            if ($sex == 0) {
                $_SESSION['_NOTIFICATIONS'][] = 'sex';
            }



        } catch(PDOException $e) {
            finish();
        }
        header('Location: posts.php?uid='. $_SESSION['_UID']);
    }
}

require('../include/header.php');
?>
<form action="/edit_profile.php" method="post">
<div class="title"><img class="menu4" src="res/user.png" alt="" />Edit Profile</div>
<?=($message != '' ? '<div class="error">'. $message. '</div>' : '')?><br />
<section>
<?=('<p>Username: <b>'. htmlspecialchars($username). '</b></p>')?>
<p><input type="text" name="name" placeholder="Name" tabindex="1" maxLength="50" autofocus style="width: 100%" value="<?=htmlspecialchars($name)?>" /></p>
<p><select name="age" tabindex="2">
<option value="0">Age</option>
<?php
for ($i = 15; $i <= 99; ++$i)
	echo '<option value="'. $i. '"'. (($age == $i) ? ' selected' : ''). '>'. $i. '</option>'. "\n";
?>
</select>
 &nbsp; 
<select name="sex" tabindex="3">
<option value="0">Sex</option>
<option value="1"<?=(($sex == 1) ? ' selected' : '')?>>Male</option>
<option value="2"<?=(($sex == 2) ? ' selected' : '')?>>Female</option>
</select></p>
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
