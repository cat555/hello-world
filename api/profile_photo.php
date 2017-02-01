<?php
declare(strict_types = 1);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    exit;
}

require '../../include/library.php';
require '../../include/image.php';
require '../../include/aws/aws-autoloader.php';

use Aws\S3\Exception\S3Exception;
use Aws\S3\S3Client;
use Aws\CloudFront\CloudFrontClient;

userLogin();

$message = null;
$photoFile = $_FILES['photo']['tmp_name'] ?? '';
$photoWidth = 0;
$photoHeight = 0;
$photoType = 0;

if ($photoFile != '') {
    if ($_FILES['photo']['error'] != 0) {
        $message = 'Photo error';
    } elseif ($_FILES['photo']['size'] < 4096) {
        $message = 'Photo too small';
    } elseif ($_FILES['photo']['size'] > 2097152) {
        $message = 'Photo too large';
    } elseif (!in_array(exif_imagetype($_FILES['photo']['tmp_name']),
      [IMAGETYPE_PNG, IMAGETYPE_JPEG])) {
        $message = 'Photo must be JPEG or PNG';
    } else {
        list($photoWidth, $photoHeight, $photoType) = getimagesize($photoFile);
        $photoType = exif_imagetype($_FILES['photo']['tmp_name']);
        $photoImage = imageInput($photoFile, $photoWidth, $photoHeight, $photoType);
        if ($photoImage == null) {
            $message = 'Photo error';
        }
    }
} else {
    $message = 'No photo selected';
}

if (!$message) {
//    imageThumb($photoImage, $photoFile. '_u1', $photoWidth, $photoHeight, 25);
    imageThumb($photoImage, $photoFile. '_u', $photoWidth, $photoHeight, 75);

    $s3 = S3Client::factory([
      'version' => 'latest',
      'region' => AMAZON_REGION,
      'credentials' => ['key' => AMAZON_KEY, 'secret' => AMAZON_SECRET],
    ]);

    if ($s3 == null) {
        $message = 'Photo update error';
    }
}

// Amazon S3, user photos upload
if (!$message) {
    try {
/*
        $res2 = $s3->putObject([
          'Bucket'      => AMAZON_BUCKET,
          'Key'         => 'u1/'. $_SESSION['_UID']. '.jpg',
          'SourceFile'  => $photoFile. '_u1',
          'ContentType' => 'image/jpeg',
          'ACL'         => 'public-read'
        ]);
*/
        $res2 = $s3->putObject([
          'Bucket'      => AMAZON_BUCKET,
          'Key'         => 'u/'. $_SESSION['_UID']. '.jpg',
          'SourceFile'  => $photoFile. '_u',
          'ContentType' => 'image/jpeg',
          'ACL'         => 'public-read'
        ]);
    } catch (\Aws\S3\Exception\S3Exception $e) {
        $message = 'Photo update error';
    }
}

// Amazon CloudFront, invalidate cached user photos
if (!$message && !in_array('photo', $_SESSION['_NOTIFICATIONS'])) {
    $cdn = CloudFrontClient::factory([
      'version' => 'latest',
      'region' => 'us-east-1',
      'credentials' => [
        'key' => AMAZON_KEY,
        'secret' => AMAZON_SECRET
      ]
    ]);

    if ($cdn != null) {
        try {
            $res3 = $cdn->createInvalidation([
              'DistributionId' => AMAZON_DISTRIBUTION_ID,
              'InvalidationBatch' => [
                'Paths' => [
                  'Quantity' => 1,
                  'Items' => ['/'. AMAZON_BUCKET. '/p1/', $_SESSION['_UID']. '.jpg']
                ],
                'CallerReference' => time(),
              ]
            ]);
        } catch (Exception $e) {}
    }
}

// clear photo notification
if (!$message && in_array('photo', $_SESSION['_NOTIFICATIONS'])) {
    $_SESSION['_NOTIFICATIONS'] = array_diff($_SESSION['_NOTIFICATIONS'],
      ['photo']);
}

if ($photoFile != '') {
    if (file_exists($photoFile)) {
        unlink($photoFile);
    }
/*
    if (file_exists($photoFile. '_u1')) {
        unlink($photoFile. '_u1');
    }
*/
    if (file_exists($photoFile. '_u')) {
        unlink($photoFile. '_u');
    }
}

if ($message) {
    echo 'error'. "\n". $message;
} else {
    echo 'ok';
}

finish();
