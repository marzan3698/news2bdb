<?php
$url = 'https://www.bing.com/news/search?q=' . urlencode('বাংলাদেশ') . '&format=rss';
$xml = @simplexml_load_file($url);
if ($xml) {
    echo "Found: " . count($xml->channel->item) . " items\n";
    foreach ($xml->channel->item as $item) {
        echo $item->title . "\n" . $item->link . "\n\n";
        break;
    }
} else {
    echo "Failed";
}
