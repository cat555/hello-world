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

settype($uid, 'int');
settype($pid, 'int');

if ($uid < 0 || $uid > 4294967295) {
    $error = 'Invalid user';
    finish();
}
if ($pid < 1 || $pid > 4294967295) {
    $error = 'Invalid post';
    finish();
}
if ($uid == 0) {
    $uid = $_SESSION['_UID'];
}

connectDatabase();

if (!checkFilter(FILTER_LIKE)) {
    $error = 'Too many recent likes';
    finish();
}
newFilter(FILTER_LIKE);

try {
    // query posts
    $res = $db->query('SELECT type, likes, post FROM posts WHERE pid = '. $pid.
      ' AND uid = '. $uid. ' AND state = 1 LIMIT 1');
    if (!($post = $res->fetch(PDO::FETCH_ASSOC))) {
        $error = 'Invalid post';
        finish();
    }
    $res = null;

    // delete post_like
    $count = $db->exec('DELETE FROM post_like WHERE pid = '. $pid.
      ' AND uid = '. $_SESSION['_UID']);

    if ($count > 0) {
        // disliked
        $liked = 0;
        if ($post['likes'] > 0) {
            $post['likes']--;
        }

        // update posts
        $db->exec('UPDATE posts SET likes = likes - 1 WHERE pid = '. $pid.
          ' AND uid = '. $uid. ' AND likes > 0');

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
              ' AND cid = 0');
        }
    } else {
        // liked
        $liked = 1;
        if ($post['likes'] < 1000000) {
            $post['likes']++;
        }

        // insert post_like
        $db->exec('INSERT INTO post_like (pid, uid) VALUES ('. $pid. ', '.
          $_SESSION['_UID']. ')');

        // update posts
        $db->exec('UPDATE posts SET likes = likes + 1 WHERE pid = '. $pid.
          ' AND uid = '. $uid. ' AND likes < 1000000');

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
            $res = $db->prepare('INSERT INTO feed (_uid, uid, pid, type, post)'.
              ' VALUES ('. $_SESSION['_UID']. ', '. $uid. ', '.
              $pid. ', '. $post['type']. ', :post)');
            $res->bindParam(':post', $post['post']);
            $res->execute();
        }
    }
} catch(PDOException $e) {
    $error = 'Error';
    finish();
}

echo 'ok';
// uid  pid  type  likes  liked
echo "\n".
  $uid. "\t".
  $pid. "\t".
  $post['type']. "\t".
  $post['likes']. "\t".
  $liked;

finish();
