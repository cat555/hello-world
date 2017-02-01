<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';

userLogin();

$type = $_POST['type'] ?? 0;
$page = $_POST['page'] ?? 0;

settype($type, 'int');
settype($page, 'int');

if ($type != 0 && $type != 1) {
    $error = 'Invalid type';
    finish();
}
if ($page < 0 || $page > 9) {
    $error = 'Invalid page';
    finish();
}

$offset = 100 * $page;

connectDatabase();

try {
    // query threads
    $res = $db->query('SELECT uid2, `read`, UNIX_TIMESTAMP(time) AS time, '.
      'message FROM threads WHERE uid1 = '. $_SESSION['_UID'].
      ($type == 1 ? ' AND `read` = 0' : '').
      ' ORDER BY time DESC LIMIT '. $offset. ', 100');
    $threads = $res->fetchAll(PDO::FETCH_ASSOC);
    $res = null;

    $db->exec('DELETE FROM notifications2 WHERE uid = '. $_SESSION['_UID']);
} catch(PDOException $e) {
    $error = 'Error';
    finish();
}

// page
echo "ok\n". $page;
foreach ($threads as $thread) {
    // uid read time snippet
    echo "\n".
      $thread['uid2']. "\t".
      $thread['read']. "\t".
      $thread['time']. "\t".
      $thread['message'];
}
finish();
