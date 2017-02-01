<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';
require '../../include/filter.php';

userLogin();

$uid = $_POST['uid'] ?? 0;

settype($uid, 'int');

if ($uid < 0 || $uid > 4294967295) {
    $error = 'Invalid user';
    finish();
}
if ($_SESSION['_UID'] == $test->uid) {
	finish();
}

connectDatabase();

try {
    $res = $db->query('SELECT tid FROM threads WHERE uid1 = '.
      $_SESSION['_UID']. ' AND uid2 = '. $uid. ' LIMIT 1');
    if ($thread = $res->fetch()) {
        $tid = $thread['tid'];
        if ($tid > 0 && $tid < 4294967296) {
            $db->exec('DELETE FROM messages WHERE tid = '. $tid);
            $db->exec('DELETE FROM threads WHERE tid = '. $tid);
        }
    }
    $res = null;
} catch(PDOException $e) {
    finish();
}

echo 'ok';
finish();
