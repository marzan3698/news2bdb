<?php
require 'vendor/autoload.php';
$t = new \ArNishan\BanglaConverter\Translate();
$s = $t->unicodeToBijoy('চ্যাম্পিয়ন');
echo "String: $s\n";
for($i=0; $i<strlen($s); $i++) { echo ord($s[$i]) . ' '; }
