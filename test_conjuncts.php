<?php
$f = 'public/fonts/SutonnyMJ-Bold.ttf';
$i = imagecreatetruecolor(800, 200);
$w = imagecolorallocate($i, 255,255,255);
$b = imagecolorallocate($i, 0,0,0);
imagefill($i,0,0,$w);

// "বিপ্লবের" variations
$text1 = "wec".chr(173)."\x87ei"; // 173 = ­, 135 = ‡
$text2 = "wec".chr(172)."\x87ei"; // 172 = ¬

// "পূর্বাভাস" variations
$text3 = "c~e".chr(169)."vfvm"; // 169 = ©
$text4 = "c~e".chr(174)."vfvm"; // 174 = ®

imagettftext($i, 24, 0, 50, 50, $b, $f, $text1);
imagettftext($i, 24, 0, 350, 50, $b, $f, $text2);
imagettftext($i, 24, 0, 50, 100, $b, $f, $text3);
imagettftext($i, 24, 0, 350, 100, $b, $f, $text4);

imagejpeg($i, 'public/test_conjuncts.jpg');
echo "Done";
