<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';

userLogin();

$uid = $_POST['uid'] ?? 0;
$pid = $_POST['pid'] ?? 0;
$cid = $_POST['cid'] ?? 0;

settype($uid, 'int');
settype($pid, 'int');
settype($cid, 'int');

if ($uid < 0 || $uid > 4294967295) {
    $error = 'Invalid user';
    finish();
}
if ($pid < 0 || $pid > 4294967295) {
    $error = 'Invalid post';
    finish();
}
if ($cid < 0 || $cid > 4294967295) {
    $error = 'Invalid comment';
    finish();
}

connectDatabase();

try {
    // update post_comment
    $count = $db->exec('UPDATE post_comment SET state = 0 WHERE cid = '. $cid.
      ' AND pid = '. $pid. ' AND uid = '. $uid.
      ($uid != $_SESSION['_UID'] ? ' AND _uid = '. $_SESSION['_UID'] : '').
      ' AND state = 1');
    if ($count != 1) {
        $error = 'Invalid post';
        finish();
    }

    // update posts
    $db->exec('UPDATE posts SET comments = comments - 1 WHERE pid = '. $pid.
      ' AND uid = '. $uid. ' AND comments > 0');

    if ($_SESSION['_UID'] != $uid) {
        // update score
        $db->exec('UPDATE IGNORE score SET score = score - 1 WHERE uid1 = '.
          $_SESSION['_UID']. ' AND uid2 = '. $uid. ' AND score > 0');
        // update score
        $db->exec('UPDATE IGNORE score SET score = score - 1 WHERE uid1 = '.
          $uid. ' AND uid2 = '. $_SESSION['_UID']. ' AND score > 0');

        // delete feed
        $res = $db->exec('DELETE FROM feed WHERE uid = '. $uid.
          ' AND pid = '. $pid. ' AND _uid = '. $_SESSION['_UID'].
          ' AND cid = '. $cid);
    }
} catch(PDOException $e) {
    finish();
}

echo 'ok';
finish();
