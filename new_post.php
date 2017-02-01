<?php
declare(strict_types = 1);

require '../include/library.php';

userLogin();

if (in_array('post', $_SESSION['_NOTIFICATIONS'])) {
    $_SESSION['_COUNTER'] = $_SESSION['_COUNTER'] ?? 3;
}

require('../include/header.php');
?>
<div class="title"><img class="menu4" src="<?=AMAZON_URL?>res/new.png" alt="" />New Post</div>
<?php
if (in_array('post', $_SESSION['_NOTIFICATIONS'])) {
?>
<div class="notification3">Swipe left-right for menu and inbox</div>
<?php
}
?>
<div id="error" class="error" style="display: none"></div><br />
<section>
<?php
if (in_array('post', $_SESSION['_NOTIFICATIONS'])) {
    $num = ['zero', 'one', 'two', 'three'];
?>
<p>As a new user, you must make <b><?=$num[$_SESSION['_COUNTER']]?></b> <?=($_SESSION['_COUNTER'] == 3 ? '' : 'more')?> posts,<br />having between <b>two</b> and <b>five</b> <a href="" onclick="return false"><b>#hashtags</b></a> each.</p>
<?
}
?>
<form>
<p><textarea id="post" name="post" placeholder="write something" tabindex="1" autocomplete="off" maxlength="300" required autofocus rows="10" style="width: 100%;"></textarea></p>
<button type="button" tabindex="3" style="float: right;" onclick="Android.newPost(document.getElementById('post').value); return false;">Post</button>
<div><button type="button" tabindex="2" onclick="Android.choosePhoto(); return false;" style="float: left;">Choose Photo</button><div id="preview" style="margin-left: 10px; float: left; color: #CCC; height: 44px; line-height: 44px;">*optional</div></div>
<div style="clear: both"></div>
</form>
<?php
if (in_array('post', $_SESSION['_NOTIFICATIONS'])) {
    require '../include/post_samples.php';
}
if ($_SESSION['_STATE'] == 2) {
    require '../include/post_hashtags.php';
}
?>
</section>
<?php
require('../include/footer.html');
finish();
