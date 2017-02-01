<?php
declare(strict_types = 1);

require '../include/library.php';

userLogin();

require '../include/header.php';
?>
<div style="height: 100%; padding-right: 180px; position: fixed; width: 100%;">
<div class="menu2" style="padding-top: 20px;" onclick="Android.loadUrl('https://findastranger.com/edit_profile.php', 1); return false;"><span class="menu3">&gt;</span><img class="menu4" src="<?=AMAZON_URL?>res/user.png" alt="" />Edit Profile</div><hr />
<div class="menu2" onclick="Android.loadUrl('https://findastranger.com/profile_photo.php', 1); return false;"><span class="menu3">&gt;</span><img class="menu4" src="<?=AMAZON_URL?>res/camera.png" alt="" />Profile Photo</div><hr />
<div class="menu2" onclick="Android.loadUrl('https://findastranger.com/change_email.php', 1); return false;"><span class="menu3">&gt;</span><img class="menu4" src="<?=AMAZON_URL?>res/settings.png" alt="" />Recovery E-mail</div><hr />
<div class="menu2" onclick="Android.loadUrl('https://findastranger.com/change_password.php', 1); return false;"><span class="menu3">&gt;</span><img class="menu4" src="<?=AMAZON_URL?>res/settings.png" alt="" />Change Password</div><hr />
<div class="menu2" onclick="location.href = 'logout.php'; return false;"><span class="menu3">&gt;</span><img class="menu4" src="<?=AMAZON_URL?>res/logout.png" alt="" />Logout</div><hr />
<div class="menu2">&nbsp;</div><hr />
<?php
if ($_SESSION['_STATE'] == 2) {
?>
<div class="menu2" style="text-shadow: 0 0 10px #FE4" onclick="Android.loadUrl('https://findastranger.com/referral.php', 1); return false;"><span class="menu3">&gt;</span><img class="menu4" src="<?=AMAZON_URL?>res/user.png" alt="" />Who Referred You?</div><hr />
<?php
}
?>
<div class="menu2" onclick="Android.loadUrl('https://findastranger.com/users.php', 1); return false;"><span class="menu3">&gt;</span><img class="menu4" src="<?=AMAZON_URL?>res/user.png" alt="" />Interactions</div><hr />
<div class="menu2" onclick="Android.loadUrl('https://findastranger.com/user_lookup.php', 1); return false;"><span class="menu3">&gt;</span><img class="menu4" src="<?=AMAZON_URL?>res/search.png" alt="" />User Lookup</div><hr />
<div class="menu2">&nbsp;</div><hr />
<div class="menu2" onclick="Android.loadUrl('https://findastranger.com/delete_account.php', 1); return false;"><span class="menu3">&gt;</span><img class="menu4" src="<?=AMAZON_URL?>res/delete.png" alt="" />Delete Account</div><hr />
<div class="menu2" onclick="Android.loadUrl('https://findastranger.com/help.php', 1); return false;"><span class="menu3">&gt;</span><img class="menu4" src="<?=AMAZON_URL?>res/help.png" alt="" />Help</div><hr />
</div>
<div style="background: #EEE; height: 100%; position: fixed; right: 0; width: 180px;"></div>
<div style="bottom: 0; position: fixed; right: 0;">
<div class="menu1" onclick="Android.loadUrl('https://findastranger.com/new_post.php', 1); return false;">New Post</div>
<div class="menu1" onclick="Android.loadUrl('https://findastranger.com/posts.php?uid=<?=$_SESSION['_UID']?>', 1); return false;">My Posts</div>
<div class="menu1" onclick="Android.loadUrl('https://findastranger.com/posts.php', 1); return false;">Related</div>
</div>
<?php
require '../include/footer.html';
