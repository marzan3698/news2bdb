<?php
$url = 'https://news.google.com/rss/search?q=' . urlencode('বাংলাদেশ when:1h') . '&hl=bn&gl=BD&ceid=BD:bn';
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
