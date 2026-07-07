<?php
require __DIR__ . '/vendor/autoload.php';

$url = 'https://news.google.com/rss/articles/CBMiU0FVX3lxTFBEcXpqdE9iWG5wX0FzRjVrbWRJTE8wN3RPZzhKWHhRdk4tU0p2T2xXQkh0VldKSEZfV0c5WThZLWpHUmd2b1RkVmNfbjdsX25xUQ?oc=5';
$client = new \GuzzleHttp\Client([
    'allow_redirects' => true,
    'cookies' => true,
    'headers' => [
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64)'
    ]
]);

try {
    $response = $client->get($url);
    echo "Status: " . $response->getStatusCode() . "\n";
    echo "Effective URL: " . $response->getHeaderLine('X-Guzzle-Effective-Url') . "\n";
    $body = (string) $response->getBody();
    
    // Google uses an intermediate page with a JS redirect or a <meta refresh>
    if (preg_match('/<a[^>]+href=["\']([^"\']+)["\']/i', $body, $m)) {
        echo "Found link in body: " . $m[1] . "\n";
    }
    if (preg_match('/data-n-v="([^"]+)"/i', $body, $m)) {
        echo "Found data-n-v: " . $m[1] . "\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
