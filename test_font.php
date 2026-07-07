<?php
$font = __DIR__ . '/public/fonts/HindSiliguri-Bold.ttf';
$img = imagecreatetruecolor(800, 200);
$red = imagecolorallocate($img, 153, 0, 0);
$white = imagecolorallocate($img, 255, 255, 255);
imagefill($img, 0, 0, $red);
imagettftext($img, 24, 0, 50, 100, $white, $font, '২০২৬ বিশ্বকাপের আসরে মেসি ৮ গোলে এগিয়ে');
imagejpeg($img, 'public/test_banner.jpg');
echo 'Success';
