<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';
require '../../include/filter.php';

userLogin();

$uid = $_POST['uid'] ?? 0;
$pid = $_POST['pid'] ?? 0;
$comment = $_POST['comment'] ?? '';

settype($uid, 'int');
settype($pid, 'int');
settype($comment, 'string');

if ($uid < 0 || $uid > 4294967295) {
    $error = 'Invalid user';
    finish();
}
if ($pid < 0 || $pid > 4294967295) {
    $error = 'Invalid post';
    finish();
}
if (strlen($comment) > 300) {
    $error = 'Comment too long';
    finish();
} else {
    $comment = trim(preg_replace('/\s+/', ' ', $comment));
    if (strlen($comment) < 2) {
        $error = 'Comment too short';
        finish();
    }
}

connectDatabase();

if (!checkFilter(FILTER_COMMENT)) {
    $error = 'Too many recent comments';
    finish();
}
newFilter(FILTER_COMMENT);

try {
    // query posts
    $res = $db->query('SELECT type, comments, post FROM posts WHERE pid = '.
      $pid. ' AND uid = '. $uid. ' AND state = 1 LIMIT 1');
    if (!($post = $res->fetch(PDO::FETCH_ASSOC))) {
        $error = 'Invalid post';
        finish();
    }
    $res = null;

    // insert post_comment
    $res = $db->prepare('INSERT INTO post_comment (pid, uid, _uid, comment) '.
      'VALUES ('. $pid. ', '. $uid. ', '. $_SESSION['_UID']. ', :comment)');
    $res->bindParam(':comment', $comment);
    $res->execute();
    $cid = $db->lastInsertId();
    $res = null;
    if ($cid < 1 || $cid > 4294967295) {
        finish();
    }

    // update posts
    $db->exec('UPDATE posts SET comments = comments + 1 WHERE pid = '. $pid.
      ' AND uid = '. $uid. ' AND comments < 4294967295');

    if ($_SESSION['_UID'] != $uid) {
        // insert notifications1
        $db->exec('INSERT IGNORE INTO notifications1 (uid) VALUES ('. $uid.
          ')');

        // insert/update score
        $db->exec('INSERT INTO score (uid1, uid2, score) VALUES ('.
          $_SESSION['_UID']. ', '. $uid. ', 1)'.
          ' ON DUPLICATE KEY UPDATE score = score + 1');

        // insert/update score
        $db->exec('INSERT INTO score (uid1, uid2, score) VALUES ('.
          $uid. ', '. $_SESSION['_UID']. ', 1)'.
          ' ON DUPLICATE KEY UPDATE score = score + 1');

        // insert feed
        $res = $db->prepare('INSERT INTO feed (_uid, uid, pid, cid, type, '.
          'post, comment) VALUES ('. $_SESSION['_UID']. ', '. $uid. ', '.
          $pid. ', '. $cid. ', '. $post['type']. ', :post, :comment)');
        $res->bindParam(':post', $post['post']);
        $res->bindParam(':comment', $comment);
        $res->execute();
    }
} catch(PDOException $e) {
    finish();
}

echo 'ok';
finish();
