<?php
require 'vendor/autoload.php';
$t = new \ArNishan\BanglaConverter\Translate();
echo "Unicode: বিশ্বচ্যাম্পিয়ন এবং নায়ক\n";
echo "Bijoy: " . $t->unicodeToBijoy('বিশ্বচ্যাম্পিয়ন এবং নায়ক') . "\n";
