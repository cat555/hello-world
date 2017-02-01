<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';

userLogin();

$uid = $_POST['uid'] ?? 0;
$page = $_POST['page'] ?? 0;
$info = $_POST['info'] ?? 0;

settype($uid, 'int');
settype($page, 'int');
settype($info, 'int');

if ($uid < 0 || $uid > 4294967295) {
    $error = 'Invalid user';
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
$user = [];
$feeds = [];

connectDatabase();

try {
    if ($info) {
        $res = $db->query('SELECT username, name, age, sex '.
          'FROM users WHERE uid = '. $uid. ' AND state IN (1, 2) LIMIT 1');
        if (!($user = $res->fetch(PDO::FETCH_ASSOC))) {
            $error = 'Invalid user';
            finish();
        }
        $res = null;
    }

    $res = $db->query('SELECT UNIX_TIMESTAMP(time) AS time, _uid, uid, pid, '.
      'cid, type, post, comment FROM feed WHERE _uid IN ('. $_SESSION['_UID'].
      ', '. $_POST['uid']. ') '.
      ($uid == $_SESSION['_UID'] ? 'OR ' : 'AND ').
      'uid IN ('. $_SESSION['_UID']. ', '. $_POST['uid']. ') ORDER BY fid '.
      'DESC LIMIT '. $offset. ', 100');
    $feeds = $res->fetchAll(PDO::FETCH_ASSOC);
    $res = null;

    $db->exec('DELETE FROM notifications1 WHERE uid = '. $_SESSION['_UID']);
} catch(PDOException $e) {
    $error = 'Error';
    finish();
}

// page uid
echo
  "ok".
  "\n". $page. "\t". $uid.
  "\n". getNotifications().
  "\n";
if (count($user) > 0) {
    // username name age sex
    echo
      $user['username']. "\t".
      $user['name']. "\t".
      $user['age']. "\t".
      $user['sex'];
}
foreach ($feeds as $feed) {
    // time  _uid  uid  pid  cid  type  post  comment
    echo "\n".
      $feed['time']. "\t".
      $feed['_uid']. "\t".
      $feed['uid']. "\t".
      $feed['pid']. "\t".
      $feed['cid']. "\t".
      $feed['type']. "\t".
      $feed['post']. "\t".
      $feed['comment'];
}
finish();
