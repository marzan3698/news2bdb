<?php
require 'vendor/autoload.php';

$text = "গভীর রাতে বাংলাদেশের 'গ্রিন সুকুক বন্ড' বিশ্ব বাজারে আলোড়ন: ট্রিলিয়ন টাকা বিনিয়োগের দ্বার উন্মোচন!";

$translator1 = new \MirazMac\BanglaString\Translator\AvroToBijoy\Translator();
$miraz = $translator1->translate($text);

$translator2 = new \ArNishan\BanglaConverter\Translate;
$nishan = $translator2->unicodeToBijoy($text);

echo "Unicode: $text\n\n";
echo "Miraz: $miraz\n\n";
echo "Nishan: $nishan\n\n";
