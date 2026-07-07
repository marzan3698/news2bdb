<?php
require 'vendor/autoload.php';

$text = "AI পরিচালিত 'জ্ঞান-সেতু'র ভোর রাতের উন্মোচন: শিক্ষায় বিপ্লবের পূর্বাভাস!";

$translator2 = new \ArNishan\BanglaConverter\Translate;
$nishan = $translator2->unicodeToBijoy($text);

echo "Nishan: " . $nishan . "\n";
