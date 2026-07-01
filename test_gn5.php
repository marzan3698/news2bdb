<?php
$url = 'https://news.google.com/rss/articles/CBMiVkFVX3lxTE93UERuc2xVTFhiZFFoTUdobklPUkZ6N1hYSGd2UVdxbkpRYWJrLVpZbkNtcHNWV1J5SnRRb2t6R1JFeVJ4ang2ems3S0JlSnZnYjdsb2R3?oc=5';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0); // Don't follow, just get headers
curl_setopt($ch, CURLOPT_HEADER, 1);
$response = curl_exec($ch);
curl_close($ch);
echo substr($response, 0, 1000);
