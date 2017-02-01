<?php
declare(strict_types = 1);

require '../include/library.php';

userLogin();

$type = $_GET['type'] ?? 0; // 0 all, 1 unread

settype($type, 'int');

if ($type != 0 && $type != 1) {
    finish();
}

require('../include/header.php');?>
<script>'use strict';var _UID=<?=$_SESSION['_UID']?>;type2=<?=$type?>;threadsPage();</script><?php
require('../include/footer.html');
finish();
?>
