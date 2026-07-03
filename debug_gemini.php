<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Setting;
use Illuminate\Support\Facades\Http;

$apiKey = Setting::where('key', 'gemini_api_key')->value('value');

if (!$apiKey) {
    echo "ERROR: No API key found!\n";
    exit(1);
}

echo "API Key found (last 6): ..." . substr($apiKey, -6) . "\n";

// Test 1: Without grounding
echo "\n=== Test 1: WITHOUT Google Search Grounding ===\n";
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=' . $apiKey;

$payload = [
    'contents' => [
        ['parts' => [['text' => 'Return ONLY valid JSON: {"title": "Test", "status": "ok"}']]]
    ]
];

$resp = Http::withHeaders(['Content-Type' => 'application/json'])->timeout(30)->post($url, $payload);
echo "Status: " . $resp->status() . "\n";

$data = $resp->json();
if (isset($data['candidates'][0]['content']['parts'])) {
    echo "Parts count: " . count($data['candidates'][0]['content']['parts']) . "\n";
    foreach ($data['candidates'][0]['content']['parts'] as $i => $part) {
        echo "Part $i: " . json_encode(array_keys($part)) . "\n";
        if (isset($part['text'])) {
            echo "Text: " . substr($part['text'], 0, 500) . "\n";
        }
    }
} else {
    echo "No candidates. Error: " . json_encode($data['error'] ?? 'unknown', JSON_UNESCAPED_UNICODE) . "\n";
}

// Test 2: WITH grounding
echo "\n=== Test 2: WITH Google Search Grounding ===\n";
$payload2 = [
    'contents' => [
        ['parts' => [['text' => 'Return ONLY valid JSON: {"title": "Test", "status": "ok"}']]]
    ],
    'tools' => [
        ['googleSearch' => new stdClass()]
    ]
];

$resp2 = Http::withHeaders(['Content-Type' => 'application/json'])->timeout(30)->post($url, $payload2);
echo "Status: " . $resp2->status() . "\n";

$data2 = $resp2->json();
if (isset($data2['candidates'][0]['content']['parts'])) {
    echo "Parts count: " . count($data2['candidates'][0]['content']['parts']) . "\n";
    foreach ($data2['candidates'][0]['content']['parts'] as $i => $part) {
        echo "Part $i keys: " . json_encode(array_keys($part)) . "\n";
        if (isset($part['text'])) {
            echo "Text: " . substr($part['text'], 0, 500) . "\n";
        }
    }
    // Check for grounding metadata
    if (isset($data2['candidates'][0]['groundingMetadata'])) {
        echo "Grounding metadata present: YES\n";
    }
} else {
    echo "No candidates. Full response (first 1000):\n";
    echo substr(json_encode($data2, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE), 0, 1000) . "\n";
}
