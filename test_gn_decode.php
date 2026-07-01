<?php
$str = 'CBMiVkFVX3lxTE93UERuc2xVTFhiZFFoTUdobklPUkZ6N1hYSGd2UVdxbkpRYWJrLVpZbkNtcHNWV1J5SnRRb2t6R1JFeVJ4ang2ems3S0JlSnZnYjdsb2R3';
$decoded = base64_decode(strtr($str, '-_', '+/'));
echo "Decoded:\n" . $decoded . "\n\n";
// The URL is usually embedded in this binary protobuf string.
if (preg_match('/(https?:\/\/[^\s\x00-\x1F]+)/', $decoded, $m)) {
    echo "Found URL: " . $m[1] . "\n";
} else {
    echo "No URL found in protobuf\n";
}
