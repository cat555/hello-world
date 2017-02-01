<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';

userLogin();

$uid = $_POST['uid'] ?? 0;
$pid = $_POST['pid'] ?? 0;
$type = $_POST['type'] ?? 0;
$page = $_POST['page'] ?? 0;
$info = $_POST['info'] ?? 0;

settype($uid, 'int');
settype($pid, 'int');
settype($type, 'int');
settype($page, 'int');
settype($info, 'int');

if ($uid < 0 || $uid > 4294967295) {
    $error = 'Invalid user';
    finish();
} elseif ($pid < 0 || $pid > 4294967295) {
    $error = 'Invalid post';
    finish();
} elseif ($uid == 0 && $pid != 0) {
    $uid = $_SESSION['_UID'];
} elseif ($type != 0 && $type != 2) {
    $error = 'Invalid type';
    finish();
} elseif ($uid == 0 || $pid != 0) {
    $type = 0;
} elseif ($page < 0 || $page > 9) {
    $error = 'Invalid page';
    finish();
} elseif ($page != 0 || ($info != 0 && $info != 1)) {
    $info = 0;
}

$offset = 100 * $page;
$user = [];
$post = [];
$posts = [];

connectDatabase();

try {
    if ($info) {
        if ($pid) {
            // query posts
            $res = $db->query('SELECT pid, uid, type, '.
              'UNIX_TIMESTAMP(time) AS time, longitude, latitude, likes, '.
              'comments, filter, post FROM posts WHERE pid = '. $pid.
              ' AND uid = '. $uid. ' AND state IN (1, 2) LIMIT 1');
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
        } elseif ($uid) {
            // query users
            $res = $db->query('SELECT username, name, age, sex '.
              'FROM users WHERE uid = '. $uid. ' AND state IN (1, 2) LIMIT 1');
            if (!($user = $res->fetch(PDO::FETCH_ASSOC))) {
                $error = 'Invalid user';
                finish();
            }
            $res = null;
        }
    }

    if (!$uid) {
        // posts related to self recent posts

        // query posts
        $res = $db->query('SELECT pid FROM posts WHERE uid = '.
          $_SESSION['_UID']. ' AND state = 1 ORDER BY pid DESC LIMIT 10');
        $pids = $res->fetchAll(PDO::FETCH_COLUMN, 0);
        $res = null;

        if (count($pids) > 0) {
            // query post_post
            $res = $db->query('SELECT DISTINCT pid2 FROM post_post WHERE '.
              'pid1 IN ('. implode(', ', $pids). ') ORDER BY count DESC LIMIT '.
              $offset. ', 100');
            $pids = $res->fetchAll(PDO::FETCH_COLUMN, 0);
            $pids = implode(', ', $pids);
            $res = null;

            if (count($pids) > 0) {
                // query posts
                $res = $db->query('SELECT pid, uid, type, '.
                  'UNIX_TIMESTAMP(time) AS time, longitude, latitude, likes, '.
                  'comments, filter, post FROM posts WHERE pid IN ('. $pids.
                  ') AND uid != '. $_SESSION['_UID']. ' AND state = 1 ORDER '.
                  'BY FIELD(pid, '. $pids. ')');
                $posts = $res->fetchAll(PDO::FETCH_ASSOC);
                $res = null;
            }
        }
    } elseif (!$pid) {
        // posts of user

        // query posts
        $res = $db->query('SELECT uid, pid, type, UNIX_TIMESTAMP(time) AS '.
          'time, longitude, latitude, likes, comments, filter, post FROM '.
          'posts WHERE uid = '. $uid.
          ($type == 2 ? ' AND type = 2' : '').
          ' AND state = 1 ORDER BY pid DESC LIMIT '. $offset. ', 100');
        $posts = $res->fetchAll(PDO::FETCH_ASSOC);
        $res = null;
    } else {
        // posts related to post

        // query post_post
        $res = $db->query('SELECT DISTINCT pid2 FROM post_post WHERE '.
          'pid1 = '. $pid. ' ORDER BY count DESC LIMIT '. $offset. ', 100');
        $pids = $res->fetchAll(PDO::FETCH_COLUMN, 0);
        $pids = implode(', ', $pids);
        $res = null;

        if (count($pids) > 0) {
            // query posts
            $res = $db->query('SELECT pid, uid, type, '.
              'UNIX_TIMESTAMP(time) AS time, longitude, latitude, likes, '.
              'comments, filter, post FROM posts WHERE pid IN ('. $pids. ') '.
              'AND uid != '. $uid. ' AND state = 1 ORDER BY FIELD(pid, '.
              $pids. ')');
            $posts = $res->fetchAll(PDO::FETCH_ASSOC);
            $res = null;
        }
    }
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
if (count($user) > 0) {
    // username name age sex
    echo
      $user['username']. "\t".
      $user['name']. "\t".
      $user['age']. "\t".
      $user['sex'];
} elseif (count($post) > 0) {
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
foreach ($posts as $post) {
    $distance = 0;
    // uid pid type time distance likes liked comments filter post
    echo "\n".
      $post['uid']. "\t".
      $post['pid']. "\t".
      $post['type']. "\t".
      $post['time']. "\t".
      $distance. "\t".
      $post['likes']. "\t".
      "0\t".
      $post['comments']. "\t".
      $post['filter']. "\t".
      $post['post'];
}

finish();
