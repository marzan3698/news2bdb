<?php
$url = 'https://news.google.com/rss/articles/CBMiVkFVX3lxTE93UERuc2xVTFhiZFFoTUdobklPUkZ6N1hYSGd2UVdxbkpRYWJrLVpZbkNtcHNWV1J5SnRRb2t6R1JFeVJ4ang2ems3S0JlSnZnYjdsb2R3?oc=5';
$html = file_get_contents($url);
file_put_contents('test_gn.html', $html);
