<?php
$canvas = imagecreatetruecolor(400, 200);
$bg = imagecolorallocate($canvas, 255, 255, 255);
$black = imagecolorallocate($canvas, 0, 0, 0);
imagefill($canvas, 0, 0, $bg);
$font = __DIR__ . '/public/fonts/SutonnyMJ-Bold.ttf';
imagettftext($canvas, 30, 0, 50, 50, $black, $font, "q (113) -> ঙ or য়?");
imagettftext($canvas, 30, 0, 50, 100, $black, $font, "y (121) -> ঙ or য়?");
imagettftext($canvas, 30, 0, 50, 150, $black, $font, "q = " . chr(113) . " | y = " . chr(121));
imagepng($canvas, 'test_font.png');
imagedestroy($canvas);
