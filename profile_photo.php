<?php
declare(strict_types = 1);

// dependencies
require '../include/library.php';

// login user
userLogin();

require('../include/header.php');
?>
<form>
<div class="title"><img class="menu4" src="<?=AMAZON_URL?>res/camera.png" alt="" />Profile Photo</div>
<div id="error" class="error" style="display: none"></div><br /><br />
<section>
<?php
if (in_array('photo', $_SESSION['_NOTIFICATIONS'])) {
?>
<h1>Choose A Profile Photo</h1>
<?
}
?>
<button type="button" tabindex="1" style="float: right;" onclick="Android.profilePhoto(); return false;">Save</button>
<div><button type="button" tabindex="2" onclick="Android.choosePhoto(); return false;" style="float: left;">Choose Photo</button><div id="preview" style="margin-left: 10px; float: left; color: #CCC; height: 44px; line-height: 44px;"></div></div>
<div style="clear: both"></div>
</form>
<p style="color: gray">*profile photos take a few minutes to update</p>
</section>
<?php
require('../include/footer.html');
finish();

