<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';

userLogin();

$pid = $_POST['pid'] ?? 0;

settype($pid, 'int');

if ($pid < 0 || $pid > 4294967295) {
    $error = 'Invalid post';
    finish();
}

connectDatabase();

try {
    // query posts
    $res = $db->query('SELECT type FROM posts WHERE pid = '. $pid.
      ' AND uid = '. $_SESSION['_UID']. ' AND state = 1 LIMIT 1');
    if (!($post = $res->fetch(PDO::FETCH_ASSOC))) {
        $error = 'Invalid post';
        finish();
    }
    $res = null;

    // update posts
    $db->exec('UPDATE posts SET state = 2 WHERE pid = '. $pid.
      ' AND uid = '. $_SESSION['_UID']. ' AND state = 1');

    // update post_like
    $db->exec('DELETE FROM post_like WHERE pid = '. $pid);
    // update post_comment
    $db->exec('UPDATE post_comment SET state = 0 WHERE pid = '. $pid);
    // delete post_tag
    $db->exec('DELETE FROM post_tag WHERE pid = '. $pid);
    // delete post_post
    $db->exec('DELETE FROM post_post WHERE pid1 = '. $pid. ' OR pid2 = '. $pid);

    if ($post['type'] == 2) {
        // update users
        $db->exec('UPDATE users SET photos = photos - 1'.
          ' WHERE uid = '. $_SESSION['_UID']. ' AND photos > 0');

        // delete photo from amazon
    }

    // update posts
    $db->exec('UPDATE posts SET state = 0 WHERE pid = '. $pid.
      ' AND uid = '. $_SESSION['_UID']. ' AND state = 2');

    // update users
    $db->exec('UPDATE users SET posts = posts - 1'.
      ' WHERE uid = '. $_SESSION['_UID']. ' AND posts > 0');

    // delete feed
    $res = $db->exec('DELETE FROM feed WHERE uid = '. $_SESSION['_UID'].
      ' AND pid = '. $pid);

} catch(PDOException $e) {
    finish();
}

echo "ok\n". $pid;
finish();
