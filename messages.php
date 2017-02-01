<?php
declare(strict_types = 1);

require '../include/library.php';

userLogin();

$uid = $_GET['uid'] ?? 0;

settype($uid, 'int');

if ($uid < 0 || $uid > 4294967295) {
    finish();
} elseif ($uid == 0 || $uid == $_SESSION['_UID']) {
    finish('/threads.php');
}

require('../include/header.php');?>
<script>'use strict';var _UID=<?=$_SESSION['_UID']?>;var uid=<?=$uid?>;messagesPage();</script><?php
require('../include/footer.html');
finish();
?>
