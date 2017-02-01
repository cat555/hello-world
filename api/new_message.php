<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';
require '../../include/filter.php';

userLogin();

$uid = $_POST['uid'] ?? 0;
$message = $_POST['message'] ?? '';

settype($uid, 'int');
settype($message, 'string');

if ($uid < 0 || $uid > 4294967295) {
    $error = 'Invalid user';
    finish();
}
if ($_SESSION['_UID'] == $uid) {
    $error = 'Cannot send message to yourself';
    finish();
}
if (strlen($message) > 1000) {
    $error = 'Message too long';
    finish();
} else {
    $message = trim(preg_replace('/\s+/', ' ', $message));
    if (strlen($message) < 1) {
        $error = 'message too short';
        finish();
    }
}

connectDatabase();

$tid1 = 0;
$tid2 = 0;

try {
    $res = $db->query('SELECT EXISTS(SELECT * FROM users WHERE uid = '. $uid.
      ' AND state IN (1, 2) LIMIT 1)');
    if (!$res->fetch(PDO::FETCH_NUM)[0]) {
        $error = 'user doesn\'t exist';
        finish();
    }
    $res = null;

    $res = $db->query('SELECT tid FROM threads WHERE uid1 = '.
      $_SESSION['_UID']. ' AND uid2 = '. $uid);
    if ($thread = $res->fetch()) {
        $tid1 = $thread['tid'];
    } else {
        if (!checkFilter(FILTER_MESSAGE)) {
            $error = 'too many recent messages';
            finish();
        }
        newFilter(FILTER_MESSAGE);

        $db->query('INSERT INTO threads (uid1, uid2) VALUES ('.
          $_SESSION['_UID']. ', '. $uid. ')');
        $tid1 = $db->lastInsertId();
    }
    $res = null;
    if ($tid1 < 1 || $tid1 > 4294967295) {
        finish();
    }

    $res = $db->query('SELECT tid FROM threads WHERE uid1 = '. $uid.
      ' AND uid2 = '. $_SESSION['_UID']);
    if ($thread = $res->fetch()) {
        $tid2 = $thread['tid'];
    } else {
        $db->exec('INSERT INTO threads (uid1, uid2) VALUES ('. $uid.
          ', '. $_SESSION['_UID']. ')');
        $tid2 = $db->lastInsertId();
    }
    $res = null;
    if ($tid2 < 1 || $tid2 > 4294967295) {
        finish();
    }

    $res = $db->prepare('INSERT INTO messages (uid, tid, message) VALUES ('.
      $_SESSION['_UID']. ', '. $tid1. ', :message)');
    $res->bindParam(':message', $message);
    $res->execute();
    $res = null;

    $res = $db->prepare('INSERT INTO messages (uid, tid, message) VALUES ('.
      $_SESSION['_UID']. ', '. $tid2. ', :message)');
    $res->bindParam(':message', $message);
    $res->execute();
    $res = null;

    $snippet = (strlen($message) <= 100) ?
      $message : substr($message, 0, 97). '...';

    $res = $db->prepare('UPDATE threads SET `read` = 1,'.
      ' messages = messages + 1, time = CURRENT_TIMESTAMP, message = :message'.
      ' WHERE tid = '. $tid1);
    $res->bindParam(':message', $snippet);
    $res->execute();
    $res = null;

    $res = $db->prepare('UPDATE threads SET `read` = 0,'.
      ' messages = messages + 1, time = CURRENT_TIMESTAMP, message = :message'.
      ' WHERE tid = '. $tid2);
    $res->bindParam(':message', $snippet);
    $res->execute();
    $res = null;

    $db->exec('INSERT IGNORE INTO notifications2 (uid) VALUES ('. $uid. ')');
} catch(PDOException $e) {
    finish();
}

echo 'ok';
finish();
