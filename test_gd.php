<?php
require 'vendor/autoload.php';
$f = 'public/fonts/SutonnyMJ-Bold.ttf';
$i = imagecreatetruecolor(800, 200);
$r = imagecolorallocate($i, 153,0,0);
$w = imagecolorallocate($i, 255,255,255);
imagefill($i,0,0,$r);
$t = (new \MirazMac\BanglaString\Translator\AvroToBijoy\Translator())->translate('ফুটবলে রাতজাগা বতকি: VAR সদ্ধিানতে বদল গলে');
imagettftext($i, 24, 0, 50, 100, $w, $f, $t);
imagejpeg($i, 'public/test2.jpg');
echo 'Success';
