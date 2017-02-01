<?php
declare(strict_types = 1);

require '../include/library.php';

userLogin();

if (in_array('photo', $_SESSION['_NOTIFICATIONS'])) {
    finish('/profile_photo.php');
} elseif (in_array('post', $_SESSION['_NOTIFICATIONS'])) {
    finish('/new_post.php');
}

require('../include/header.php');?>
<script>'use strict';var _UID=<?=$_SESSION['_UID']?>;var _STATE=<?=$_SESSION['_STATE']?>;usersPage();</script><?php
require('../include/footer.html');
finish();
