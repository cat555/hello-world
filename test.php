<?php
declare(strict_types = 1);

$dir = 'tmp';
$files = scandir($dir);
shuffle($files);
$files = array_slice($files, 0, 185);
foreach ($files as $key => $value) {
    if ($value[0] != '.') {
        $url = $dir. '/'. $value;
        echo '<a href="'. $url. '" target="_blank"><img src="'. $url. '" height="100px" /></a>';
    }
}
