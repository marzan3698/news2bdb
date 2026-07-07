<?php
$xml = simplexml_load_file('https://www.jagonews24.com/rss/rss.xml', 'SimpleXMLElement', LIBXML_NOCDATA);
if ($xml && isset($xml->channel->item[0])) {
    print_r($xml->channel->item[0]);
} else {
    echo "Failed to parse XML";
}
