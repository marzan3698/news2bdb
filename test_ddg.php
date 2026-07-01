<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://html.duckduckgo.com/html/?q=" . urlencode('বাংলাদেশ') . "&df=h");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
$res = curl_exec($ch);
curl_close($ch);
if (preg_match_all('/<a class="result__url" href="([^"]+)">/i', $res, $m)) {
    foreach ($m[1] as $url) {
        $url = urldecode(str_replace('//duckduckgo.com/l/?uddg=', '', $url));
        $url = preg_replace('/&rut=.*/', '', $url);
        echo $url . "\n";
    }
} else {
    echo "No results\n";
}
