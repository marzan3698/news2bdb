<?php
$lines = explode("\n", wordwrap("AI পরিচালিত 'জ্ঞান-সেতু'র ভোর রাতের উন্মোচন: শিক্ষায় বিপ্লবের পূর্বাভাস!", 55, "\n", true)); 
foreach ($lines as $line) { 
    $tokens = preg_split('/([a-zA-Z0-9]+)/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY); 
    print_r($tokens); 
}
