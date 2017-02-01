<?php
session_start();
$_SESSION['_UID'] = 0;
$_SESSION['_STATE'] = 0;
$_SESSION['_USERNAME'] = '';
unset($_SESSION['_UID']);
unset($_SESSION['_STATE']);
unset($_SESSION['_USERNAME']);
$_SESSION = array();
session_destroy();
header('Location: /login.php');
