<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';
require '../../include/extract_hashtags.php';
require '../../include/image.php';
require '../../include/filter.php';
require '../../include/aws/aws-autoloader.php';

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\CloudFront\CloudFrontClient;

userLogin();
connectDatabase();

$latitude = $_POST['latitude'] ?? 0.0;
$longitude = $_POST['longitude'] ?? 0.0;
$post = $_POST['post'] ?? '';
$photo = false;
$message = null;

settype($latitude, 'float');
settype($longitude, 'float');
settype($post, 'string');

if ($latitude < -90.0 || $latitude > 90.0) {
    $message = 'Invalid location';
} elseif ($longitude < -180.0 || $longitude > 180.0) {
    $message = 'Invalid location';
} elseif (strlen($post) > 300) {
    $message = 'Post must be up to 300 characters';
} else {
    $post = trim(preg_replace('/\s+/', ' ', $post));
    if (strlen($post) < 20) {
        $message = 'Post must be at least 20 characters';
    }
}
if (strpos($post, '@') !== false) {
    $message = 'Use # instead of @ for hashtags';
}

// photo
if (!$message) {
    $photoFile = $_FILES['photo']['tmp_name'] ?? '';
    $photoWidth = 0;
    $photoHeight = 0;
    $photoType = 0;

    if ($photoFile != '') {
        $photo = true;
        if ($_FILES['photo']['error'] != 0) {
            $photo = false;
            $message = 'Photo error';
        } elseif ($_FILES['photo']['size'] < 4096) {
            $photo = false;
            $message = 'Photo too small';
        } elseif ($_FILES['photo']['size'] > 8388608) {
            $photo = false;
            $message = 'Photo too large';
        } elseif (!in_array(exif_imagetype($_FILES['photo']['tmp_name']),
          [IMAGETYPE_PNG, IMAGETYPE_JPEG])) {
            $photo = false;
            $message = 'Photo must be JPEG or PNG';
        } else {
            list($photoWidth, $photoHeight, $photoType) = getimagesize($photoFile);
            $photoType = exif_imagetype($_FILES['photo']['tmp_name']);
            $photoImage = imageInput($photoFile, $photoWidth, $photoHeight, $photoType);
            if ($photoImage == null) {
                $photo = false;
                $message = 'Photo error';
            }
        }
    }
}

if (!$message) {
    try {
        $res = $db->query('SELECT posts FROM users WHERE uid = '.
          $_SESSION['_UID']. ' AND state IN (1, 2)');
        if (!($user = $res->fetch(PDO::FETCH_ASSOC))) {
            $message = 'Invalid user';
        }
        $res = null;
    } catch(PDOException $e) {
        $message = 'Error';
    }
}

if (!$message) {
    $tids = getHashtags($post);
    if ($tids == null) {
        $message = 'Post must have between 2 and 5 hashtags';
    } elseif (!checkFilter(FILTER_POST)) {
        $message = 'Too many posts in one day';
    } else {
        newFilter(FILTER_POST);
        $pid = insertPost($latitude, $longitude, $post, $tids);
        if ($pid < 1 || $pid > 4294967295) {
            $photo = false;
            $message = 'Error creating post';
        }
        $user['posts']++;
    }
}

// photo
if (!$message && $photo) {
    imageThumb($photoImage, $photoFile. '_p1', $photoWidth, $photoHeight, 50);
    imageResize($photoImage, $photoFile. '_p2', $photoWidth, $photoHeight, 1024);

    $s3 = S3Client::factory([
      'version' => 'latest',
      'region' => AMAZON_REGION,
      'credentials' => ['key' => AMAZON_KEY, 'secret' => AMAZON_SECRET],
    ]);

    if ($s3 == null) {
        $photo = false;
    }
}

if (!$message && $photo) {
    try {
        $res2 = $s3->putObject([
          'Bucket'      => AMAZON_BUCKET,
          'Key'         => 'p1/'. $_SESSION['_UID']. '_'. $pid. '.jpg',
          'SourceFile'  => $photoFile. '_p1',
          'ContentType' => 'image/jpeg',
          'ACL'         => 'public-read'
        ]);

        $res2 = $s3->putObject([
          'Bucket'      => AMAZON_BUCKET,
          'Key'         => 'p2/'. $_SESSION['_UID']. '_'. $pid. '.jpg',
          'SourceFile'  => $photoFile. '_p2',
          'ContentType' => 'image/jpeg',
          'ACL'         => 'public-read'
        ]);
    } catch (\Aws\S3\Exception\S3Exception $e) {
        $photo = false;
    }
}

if (!$message && $photo) {
    try {
        $db->exec('UPDATE posts SET type = 2 WHERE pid = '. $pid. ' AND uid = '.
          $_SESSION['_UID']);
        $db->exec('UPDATE users SET photos = photos + 1 WHERE uid = '.
          $_SESSION['_UID']. ' AND photos < 4294967295');
    } catch(PDOException $e) {}
}

if ($photoFile != '') {
    if (file_exists($photoFile)) {
        unlink($photoFile);
    }
    if (file_exists($photoFile. '_p1')) {
        unlink($photoFile. '_p1');
    }
    if (file_exists($photoFile. '_p2')) {
        unlink($photoFile. '_p2');
    }
}

if ($message) {
    echo 'error'. "\n". $message;
} else {
    // uid pid posts
    echo 'ok'. "\n". $_SESSION['_UID']. "\t". $pid. "\t". $user['posts'];
}

finish();

