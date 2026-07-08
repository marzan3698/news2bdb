<?php
require 'vendor/autoload.php';
$t = new \ArNishan\BanglaConverter\Translate();
$y = "\u{09AF}\u{09BC}"; // য + ়
$word = "না" . $y . "ক";
echo "Word: $word\n";
$s = $t->unicodeToBijoy($word);
echo "Bijoy: $s\n";
for($i=0; $i<mb_strlen($s, 'UTF-8'); $i++) {
    $char = mb_substr($s, $i, 1, 'UTF-8');
    echo "$char (" . ord($char) . ") | ";
}
echo "\n";
