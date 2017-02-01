<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';

userLogin();

$page = $_POST['page'] ?? 0;

settype($page, 'int');

if ($page < 0 || $page > 9) {
    $error = 'Invalid page';
    finish();
}

$offset = 100 * $page;
$users = [];

connectDatabase();

try {
    // query posts
    $res = $db->query('SELECT uid2 FROM score WHERE uid1 = '. $_SESSION['_UID'].
      ' ORDER BY score DESC LIMIT '. $offset. ', 100');
    $users = $res->fetchAll(PDO::FETCH_ASSOC);
    $res = null;
} catch(PDOException $e) {
    $error = 'Error';
    finish();
}

// page uid pid
echo
  "ok".
  "\n". $page.
  "\n". getNotifications();
foreach ($users as $user) {
    // uid
    echo "\n".
      $user['uid2'];
}
finish();
