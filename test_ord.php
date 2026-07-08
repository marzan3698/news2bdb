<?php
require 'vendor/autoload.php';
$t = new \ArNishan\BanglaConverter\Translate();
$s = $t->unicodeToBijoy('নায়ক');
for($i=0; $i<strlen($s); $i++) {
    echo ord($s[$i]) . ' ';
}
echo "\n$s\n";
