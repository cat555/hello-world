<?php
declare(strict_types = 1);

require '../include/library.php';

userLogin();

$uid = $_GET['uid'] ?? 0;
$pid = $_GET['pid'] ?? 0;
$tab = $_GET['tab'] ?? 0;

settype($uid, 'int');
settype($pid, 'int');
settype($tab, 'int');

if ($uid < 0 || $uid > 4294967295) {
    finish();
} elseif ($pid < 0 || $uid > 4294967295) {
    finish();
} elseif (!$uid && $pid) {
    $uid = $_SESSION['_UID'];
}
if ($tab < 0 || $tab > 3) {
    finish();
}

if (in_array('photo', $_SESSION['_NOTIFICATIONS'])) {
    finish('/profile_photo.php');
} elseif (in_array('post', $_SESSION['_NOTIFICATIONS'])) {
    finish('/new_post.php');
}

require('../include/header.php');?>
<script>'use strict';var _UID=<?=$_SESSION['_UID']?>;var _STATE=<?=$_SESSION['_STATE']?>;var uid=<?=$uid?>;var pid=<?=$pid?>;var tab=<?=$tab?>;var notification3=[<?=((count($_SESSION['_NOTIFICATIONS']) == 0) ? '' : '\''. implode('\',\'',$_SESSION['_NOTIFICATIONS']). '\'')?>];postsPage();</script><?php
require('../include/footer.html');
finish();
