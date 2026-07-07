<?php
$f = 'public/fonts/SutonnyMJ-Bold.ttf';
$i = imagecreatetruecolor(800, 800);
$w = imagecolorallocate($i, 255,255,255);
$b = imagecolorallocate($i, 0,0,0);
imagefill($i,0,0,$w);

$y = 50;
for ($c = 160; $c <= 255; $c++) {
    imagettftext($i, 16, 0, 50 + (($c % 10) * 50), $y + floor(($c-160)/10)*50, $b, $f, chr($c));
}
imagejpeg($i, 'public/test_chars.jpg');
echo "Done";
