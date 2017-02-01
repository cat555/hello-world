<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';

userLogin();

$uid = $_POST['uid'] ?? 0;
$pid = $_POST['pid'] ?? 0;
$page = $_POST['page'] ?? 0;
$info = $_POST['info'] ?? 0;

settype($uid, 'int');
settype($pid, 'int');
settype($page, 'int');
settype($info, 'int');

if ($uid < 0 || $uid > 4294967295) {
    $error = 'Invalid user';
    finish();
} elseif ($pid < 1 || $pid > 4294967295) {
    $error = 'Invalid post';
    finish();
} elseif ($uid == 0) {
    $uid = $_SESSION['_UID'];
}
if ($page < 0 || $page > 9) {
    $error = 'Invalid page';
    finish();
} elseif ($page != 0 || ($info != 0 && $info != 1)) {
    $info = 0;
}

$offset = 100 * $page;
$post = [];
$comments = [];

connectDatabase();

try {
    if ($info) {
        // query posts
        $res = $db->query('SELECT pid, uid, type, '.
          'UNIX_TIMESTAMP(time) AS time, longitude, latitude, likes, '.
          'comments, filter, post FROM posts WHERE pid = '. $pid.
          ' AND uid = '. $uid. ' AND state = 1 LIMIT 1');
        if (!($post = $res->fetch(PDO::FETCH_ASSOC))) {
            $error = 'Invalid post';
            finish();
        }
        $res = null;

        // query likes
        $res = $db->query('SELECT EXISTS(SELECT * FROM post_like WHERE '.
          'pid = '. $pid. ' AND uid = '. $_SESSION['_UID']. ' LIMIT 1)');
        $post['like'] = $res->fetch(PDO::FETCH_NUM)[0] ? 1 : 0;
        $res = null;
    }

    // query comments
    $res = $db->query('SELECT cid, _uid, UNIX_TIMESTAMP(time) AS time, comment '.
      'FROM post_comment WHERE pid = '. $pid. ' AND uid = '. $uid.
      ' AND state = 1 ORDER BY cid DESC LIMIT '. $offset. ', 100');
    $comments = $res->fetchAll(PDO::FETCH_ASSOC);
    $res = null;
} catch(PDOException $e) {
    $error = 'Error';
    finish();
}

// page uid pid
echo
  "ok".
  "\n". $page. "\t". $uid. "\t". $pid.
  "\n". getNotifications().
  "\n";
if (count($post) > 0) {
    $distance = 0;
    // uid pid type time distance likes liked comments filter post
    echo
      $post['uid']. "\t".
      $post['pid']. "\t".
      $post['type']. "\t".
      $post['time']. "\t".
      $distance. "\t".
      $post['likes']. "\t".
      $post['like']. "\t".
      $post['comments']. "\t".
      $post['filter']. "\t".
      $post['post'];
}
foreach ($comments as $comment) {
    // uid  cid  time  comment
    echo "\n".
      $comment['_uid']. "\t".
      $comment['cid']. "\t".
      $comment['time']. "\t".
      $comment['comment'];
}
finish();
