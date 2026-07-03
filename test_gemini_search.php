<?php
$c = curl_init();
curl_setopt($c, CURLOPT_URL, 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=AIzaSyDcU66Bs5myHz3YBbCwetJEG6INzOgyFio');
curl_setopt($c, CURLOPT_POST, true);
curl_setopt($c, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($c, CURLOPT_RETURNTRANSFER, true);
curl_setopt($c, CURLOPT_POSTFIELDS, json_encode([
    'contents' => [
        [
            'parts' => [
                ['text' => 'Give me the URL of the most recent news article about Bangladesh from the last 1 hour. Only return the raw URL, nothing else.']
            ]
        ]
    ],
    'tools' => [
        ['googleSearch' => new stdClass()]
    ]
]));
$resp = curl_exec($c);
curl_close($c);
echo $resp;
