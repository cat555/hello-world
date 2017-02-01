<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';

userLogin();

$uid = $_POST['uid'] ?? 0;
$page = $_POST['page'] ?? 0;

settype($uid, 'int');
settype($page, 'int');

if ($uid < 0 || $uid > 4294967295) {
    $error = 'Invalid user';
    finish();
}
if ($page < 0 || $page > 9) {
    $error = 'Invalid page';
    finish();
}

$offset = 100 * $page;
$messages = [];

connectDatabase();

try {
    $res = $db->query('SELECT EXISTS(SELECT * FROM users WHERE uid = '. $uid.
      ' AND state IN (1, 2) LIMIT 1)');
    if (!$res->fetch(PDO::FETCH_NUM)[0]) {
        $error = 'user doesn\'t exist';
        finish();
    }
    $res = null;

    // query threads
    $res = $db->query('SELECT tid, `read` FROM threads WHERE uid1 = '.
      $_SESSION['_UID']. ' AND uid2 = '. $uid. ' LIMIT 1');
    if ($thread = $res->fetch()) {
        $res = null;
        if ($thread['read'] == 0) {
            $db->exec('UPDATE threads SET `read` = 1 WHERE tid = '.
              $thread['tid']);
        }

        // query messages
        $res = $db->query('SELECT uid, UNIX_TIMESTAMP(time) AS time, '.
          'message FROM messages WHERE tid = '. $thread['tid']. ' ORDER BY mid'.
          ' DESC LIMIT '. $offset. ', 100');
        $messages = $res->fetchAll(PDO::FETCH_ASSOC);
    }
    $res = null;

    $db->exec('DELETE FROM notifications2 WHERE uid = '. $_SESSION['_UID']);
} catch(PDOException $e) {
    $error = 'Error';
    finish();
}

// page uid
echo "ok\n". $page. "\t". $uid;
for ($i = count($messages) - 1; $i >= 0; --$i) {
    // uid time message
    echo "\n".
      $messages[$i]['uid']. "\t".
      $messages[$i]['time']. "\t".
      $messages[$i]['message'];
}

finish();
