<?php
require 'vendor/autoload.php';
$url = 'https://news.google.com/rss/articles/CBMiVkFVX3lxTE93UERuc2xVTFhiZFFoTUdobklPUkZ6N1hYSGd2UVdxbkpRYWJrLVpZbkNtcHNWV1J5SnRRb2t6R1JFeVJ4ang2ems3S0JlSnZnYjdsb2R3?oc=5';
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)');
$html = curl_exec($ch);
$finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
curl_close($ch);
echo "Final URL: " . $finalUrl . "\n";
if (preg_match('/<meta property="og:image" content="([^"]+)"/i', $html, $m)) {
    echo "Image: " . $m[1] . "\n";
} else {
    echo "No image found\n";
}
