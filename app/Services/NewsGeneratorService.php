<?php

namespace App\Services;

use App\Models\Article;
use App\Models\AiSource;
use App\Models\Category;
use App\Models\Setting;
use App\Models\GenerationLog;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class NewsGeneratorService
{
    protected string $apiKey;
    protected string $geminiModel;
    protected string $imageMode;
    protected bool $useGrounding;

    /**
     * Map of 64 districts in Bangladesh to their 8 divisions (Bengali)
     */
    protected array $districtToDivision = [
        'ঢাকা' => 'ঢাকা', 'গাজীপুর' => 'ঢাকা', 'নারায়ণগঞ্জ' => 'ঢাকা', 'রাজবাড়ী' => 'ঢাকা', 'ফরিদপুর' => 'ঢাকা', 'গোপালগঞ্জ' => 'ঢাকা', 'মাদারীপুর' => 'ঢাকা', 'মানিকগঞ্জ' => 'ঢাকা', 'মুন্সিগঞ্জ' => 'ঢাকা', 'নরসিংদী' => 'ঢাকা', 'শরীয়তপুর' => 'ঢাকা', 'টাঙ্গাইল' => 'ঢাকা', 'কিশোরগঞ্জ' => 'ঢাকা',
        'চট্টগ্রাম' => 'চট্টগ্রাম', 'কক্সবাজার' => 'চট্টগ্রাম', 'কুমিল্লা' => 'চট্টগ্রাম', 'ফেনী' => 'চট্টগ্রাম', 'ব্রাহ্মণবাড়িয়া' => 'চট্টগ্রাম', 'রাঙ্গামাটি' => 'চট্টগ্রাম', 'বান্দরবান' => 'চট্টগ্রাম', 'খাগড়াছড়ি' => 'চট্টগ্রাম', 'নোয়াখালী' => 'চট্টগ্রাম', 'লক্ষ্মীপুর' => 'চট্টগ্রাম', 'চাঁদপুর' => 'চট্টগ্রাম',
        'রাজশাহী' => 'রাজশাহী', 'বগুড়া' => 'রাজশাহী', 'পাবনা' => 'রাজশাহী', 'সিরাজগঞ্জ' => 'রাজশাহী', 'নওগাঁ' => 'রাজশাহী', 'নাটোর' => 'রাজশাহী', 'জয়পুরহাট' => 'রাজশাহী', 'চাঁপাইনবাবগঞ্জ' => 'রাজশাহী',
        'খুলনা' => 'খুলনা', 'যশোর' => 'খুলনা', 'সাতক্ষীরা' => 'খুলনা', 'বাগেরহাট' => 'খুলনা', 'কুষ্টিয়া' => 'খুলনা', 'মেহেরপুর' => 'খুলনা', 'চুয়াডাঙ্গা' => 'খুলনা', 'ঝিনাইদহ' => 'খুলনা', 'মাগুরা' => 'খুলনা', 'নড়াইল' => 'খুলনা',
        'বরিশাল' => 'বরিশাল', 'পটুয়াখালী' => 'বরিশাল', 'ভোলা' => 'বরিশাল', 'পিরোজপুর' => 'বরিশাল', 'বরগুনা' => 'বরিশাল', 'ঝালকাঠি' => 'বরিশাল',
        'সিলেট' => 'সিলেট', 'মৌলভীবাজার' => 'সিলেট', 'হবিগঞ্জ' => 'সিলেট', 'সুনামগঞ্জ' => 'সিলেট',
        'রংপুর' => 'রংপুর', 'দিনাজপুর' => 'রংপুর', 'কুড়িগ্রাম' => 'রংপুর', 'গাইবান্ধা' => 'রংপুর', 'নীলফামারী' => 'রংপুর', 'লালমনিরহাট' => 'রংপুর', 'পঞ্চগড়' => 'রংপুর', 'ঠাকুরগাঁও' => 'রংপুর',
        'ময়মনসিংহ' => 'ময়মনসিংহ', 'নেত্রকোণা' => 'ময়মনসিংহ', 'শেরপুর' => 'ময়মনসিংহ', 'জামালপুর' => 'ময়মনসিংহ'
    ];

    public function __construct()
    {
        $this->apiKey = Setting::where('key', 'gemini_api_key')->value('value') ?? '';
        $this->geminiModel = Setting::where('key', 'gemini_model')->value('value') ?? 'gemini-2.5-flash';
        $this->imageMode = Setting::where('key', 'image_mode')->value('value') ?? 'real';
        $this->useGrounding = (bool)(Setting::where('key', 'use_grounding')->value('value') ?? '1');
    }

    /**
     * Main entry point — generates a single news article.
     *
     * @param array $categoryIds Target category IDs (empty = all, with rotation)
     * @param int|null $userId The user ID to associate with the article
     * @return array ['success' => bool, 'message' => string, 'article' => ?Article]
     */
    public function generate(array $categoryIds = [], ?int $userId = null, array $customSourceData = []): array
    {
        $startTime = microtime(true);

        if (empty($this->apiKey)) {
            return $this->fail('API Key not configured.', null, $startTime);
        }

        try {
            // 1. Determine target category (rotation logic)
            $targetCategory = $this->resolveTargetCategory($categoryIds);
            $categoryName = $targetCategory ? $targetCategory->name : 'জাতীয়';
            $categoryId = $targetCategory?->id;

            // 2. Fetch source content (multi-source intelligence or custom data)
            if (!empty($customSourceData) && (!empty($customSourceData['headline']) || !empty($customSourceData['content']))) {
                $sourceData = [
                    'headline'  => $customSourceData['headline'] ?? '',
                    'content'   => $customSourceData['content'] ?? '',
                    'url'       => $customSourceData['url'] ?? '',
                    'image_url' => $customSourceData['image_url'] ?? '',
                    'name'      => 'n8n Push',
                ];
            } else {
                $sourceData = $this->fetchFromSources($categoryId);
            }

            // 3. Get recent titles for duplicate prevention
            $recentTitles = $this->getRecentTitles(25);

            // 4. Build the prompt
            $prompt = $this->buildPrompt($categoryName, $sourceData, $recentTitles);

            // 5. Generate with Gemini
            $newsData = $this->generateWithGemini($prompt);
            if (!$newsData) {
                return $this->fail('Failed to generate from Gemini or invalid JSON returned.', $categoryId, $startTime);
            }

            // 6. Check duplicate
            $contentHash = $this->computeHash($newsData['title'], $sourceData['url'] ?? null);
            if ($this->isDuplicate($contentHash)) {
                return $this->fail('Duplicate content detected — skipped.', $categoryId, $startTime, 'skipped');
            }

            // 7. Process image
            $imageUrl = $this->processImage($newsData, $sourceData);

            // 8. Save article
            $article = $this->saveArticle($newsData, $categoryId, $userId, $imageUrl, $contentHash, $sourceData);

            // 9. Log success
            $this->log('success', $sourceData['name'] ?? null, $categoryId, $article->id, null, $startTime);

            return [
                'success' => true,
                'message' => 'Article auto-generated successfully!',
                'article' => $article->load('category'),
            ];
        } catch (\Exception $e) {
            return $this->fail($e->getMessage(), $categoryId ?? null, $startTime);
        }
    }

    // =========================================================================
    // CATEGORY ROTATION
    // =========================================================================

    protected function resolveTargetCategory(array $categoryIds): ?Category
    {
        if (empty($categoryIds)) {
            $categoryIds = Category::pluck('id')->toArray();
        }

        if (empty($categoryIds)) {
            return null;
        }

        $lastArticle = Article::orderBy('id', 'desc')->first();
        $lastCategoryId = $lastArticle?->category_id;

        if ($lastCategoryId && in_array($lastCategoryId, $categoryIds)) {
            $index = array_search($lastCategoryId, $categoryIds);
            $nextIndex = ($index + 1) % count($categoryIds);
            $targetCategoryId = $categoryIds[$nextIndex];
        } else {
            $targetCategoryId = $categoryIds[0];
        }

        return Category::find($targetCategoryId);
    }

    // =========================================================================
    // MULTI-SOURCE INTELLIGENCE
    // =========================================================================

    /**
     * Fetches source content from up to 3 sources, preferring category-matched ones.
     * Returns the freshest item found.
     */
    protected function fetchFromSources(?int $categoryId): array
    {
        $empty = ['headline' => null, 'content' => null, 'image_url' => null, 'name' => null, 'url' => null];

        // Priority: category-matched sources first, then any active source
        $sources = collect();

        if ($categoryId) {
            $matched = AiSource::where('status', true)
                ->where('category_id', $categoryId)
                ->inRandomOrder()
                ->take(2)
                ->get();
            $sources = $sources->merge($matched);
        }

        // Fill up to 3 with generic sources
        if ($sources->count() < 3) {
            $generic = AiSource::where('status', true)
                ->whereNotIn('id', $sources->pluck('id')->toArray())
                ->inRandomOrder()
                ->take(3 - $sources->count())
                ->get();
            $sources = $sources->merge($generic);
        }

        if ($sources->isEmpty()) {
            return $empty;
        }

        $bestResult = null;
        $bestDate = null;

        foreach ($sources as $source) {
            $result = $this->fetchSingleSource($source);
            if (!$result['headline']) continue;

            // If we haven't found anything yet, use this
            if (!$bestResult) {
                $bestResult = $result;
                $bestDate = $result['date'] ?? null;
                continue;
            }

            // Compare freshness: prefer newer items
            if (isset($result['date']) && $bestDate && $result['date']->greaterThan($bestDate)) {
                $bestResult = $result;
                $bestDate = $result['date'];
            }
        }

        return $bestResult ?? $empty;
    }

    /**
     * Fetches content from a single AiSource (RSS, Facebook, or Scraping).
     */
    protected function fetchSingleSource(AiSource $source): array
    {
        $result = [
            'headline' => null,
            'content' => null,
            'image_url' => null,
            'name' => $source->name,
            'url' => null,
            'date' => null,
        ];

        try {
            if ($source->type === 'rss') {
                return $this->fetchRss($source, $result);
            } elseif ($source->type === 'facebook') {
                return $this->fetchFacebook($source, $result);
            } elseif ($source->type === 'scraping') {
                return $this->fetchScraping($source, $result);
            }
        } catch (\Exception $e) {
            // Silently fail per-source; the system will try others
        }

        return $result;
    }

    /**
     * Fetches and parses an RSS feed, finding the freshest item (within 24h) with an image.
     */
    protected function fetchRss(AiSource $source, array $result): array
    {
        $response = Http::timeout(15)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; NewsBot/1.0)'])
            ->get($source->url);

        if (!$response->successful()) return $result;

        $xml = @simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
        if (!$xml || !isset($xml->channel->item)) return $result;

        // Search window: up to 6 hours for maximum freshness
        $cutoff = Carbon::now()->subHours(6);
        $selectedItem = null;
        $foundImage = null;
        $bestDate = null;

        foreach ($xml->channel->item as $item) {
            // ── FRESHNESS CHECK ──
            $pubDateStr = (string)($item->pubDate ?? '');
            if (empty($pubDateStr)) {
                $dcNs = $item->children('http://purl.org/dc/elements/1.1/');
                if ($dcNs && isset($dcNs->date)) {
                    $pubDateStr = (string)$dcNs->date;
                }
            }

            $parsedDate = null;
            if (!empty($pubDateStr)) {
                try {
                    $parsedDate = Carbon::parse($pubDateStr);
                    if ($parsedDate->lessThan($cutoff)) continue; // Older than 6h — skip
                } catch (\Throwable $e) {
                    // Unparseable date — allow through, but treat as low priority
                }
            }

            // ── IMAGE EXTRACTION (6 methods) ──
            $imgCandidate = $this->extractImageFromRssItem($item);
            $hasImage = $imgCandidate && filter_var($imgCandidate, FILTER_VALIDATE_URL);

            // ── SELECTION LOGIC ──
            // We want the absolute newest item.
            if (!$selectedItem) {
                $selectedItem = $item;
                $bestDate = $parsedDate;
                $foundImage = $hasImage ? $imgCandidate : null;
            } else {
                // If we found a newer item, replace the selected one
                if ($parsedDate && $bestDate && $parsedDate->greaterThan($bestDate)) {
                    $selectedItem = $item;
                    $bestDate = $parsedDate;
                    $foundImage = $hasImage ? $imgCandidate : null;
                }
            }
        }

        if ($selectedItem) {
            $result['headline'] = mb_convert_encoding((string)$selectedItem->title, 'UTF-8', 'UTF-8');
            $result['url'] = (string)($selectedItem->link ?? '');

            $contentNs = $selectedItem->children('http://purl.org/rss/1.0/modules/content/');
            $encodedContent = ($contentNs && isset($contentNs->encoded)) ? (string)$contentNs->encoded : '';
            $fullContent = strip_tags((string)$selectedItem->description . ' ' . $encodedContent);
            $result['content'] = mb_substr(mb_convert_encoding(trim($fullContent), 'UTF-8', 'UTF-8'), 0, 3000, 'UTF-8');

            if ($foundImage) {
                $result['image_url'] = $foundImage;
            }
            $result['date'] = $bestDate;
        }

        return $result;
    }

    /**
     * Extracts an image URL from an RSS item using 6 different methods.
     */
    protected function extractImageFromRssItem(\SimpleXMLElement $item): ?string
    {
        // Method 1: media:content (most common)
        $mediaChildren = $item->children('http://search.yahoo.com/mrss/');
        if ($mediaChildren && isset($mediaChildren->content)) {
            $url = (string)$mediaChildren->content->attributes()->url;
            if (!empty($url)) return $url;
        }

        // Method 2: media namespace shorthand
        $mediaShort = $item->children('media', true);
        if ($mediaShort && isset($mediaShort->content)) {
            $url = (string)$mediaShort->content->attributes()->url;
            if (!empty($url)) return $url;
        }
        if ($mediaShort && isset($mediaShort->thumbnail)) {
            $url = (string)$mediaShort->thumbnail->attributes()->url;
            if (!empty($url)) return $url;
        }

        // Method 3: enclosure tag
        if (isset($item->enclosure)) {
            $encAttrs = $item->enclosure->attributes();
            $encType = (string)($encAttrs->type ?? '');
            $encUrl = (string)($encAttrs->url ?? '');
            if (!empty($encUrl) && (str_contains($encType, 'image') || !empty($encUrl))) {
                return $encUrl;
            }
        }

        // Method 4: <description> HTML img tags
        $desc = (string)$item->description;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $desc, $match)) {
            return $match[1];
        }

        // Method 5: content:encoded HTML
        $contentNs = $item->children('http://purl.org/rss/1.0/modules/content/');
        if ($contentNs && isset($contentNs->encoded)) {
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', (string)$contentNs->encoded, $match)) {
                return $match[1];
            }
        }

        // Method 6: Regex fallback on raw XML
        $itemXml = $item->asXML();
        if (preg_match('/url=["\']([^"\']+\.(jpg|jpeg|png|webp))["\']/i', $itemXml, $match)) {
            return $match[1];
        }

        return null;
    }

    /**
     * Fetches content from a Facebook page feed.
     */
    protected function fetchFacebook(AiSource $source, array $result): array
    {
        $fbEnabled = Setting::where('key', 'facebook_enabled')->value('value') ?? '0';
        $pageToken = Setting::where('key', 'facebook_page_access_token')->value('value');

        if ($fbEnabled !== '1' || !$pageToken) return $result;

        $fbPageId = $source->url;
        $fbResponse = Http::timeout(10)->get("https://graph.facebook.com/v18.0/{$fbPageId}/feed", [
            'fields' => 'message,attachments,created_time',
            'limit' => 5,
            'access_token' => $pageToken
        ]);

        if (!$fbResponse->successful()) return $result;

        $feedData = $fbResponse->json();
        if (empty($feedData['data'])) return $result;

        foreach ($feedData['data'] as $post) {
            if (!empty($post['message'])) {
                $result['content'] = mb_convert_encoding($post['message'], 'UTF-8', 'UTF-8');
                $lines = explode("\n", $result['content']);
                $result['headline'] = trim($lines[0]);

                if (!empty($post['attachments']['data'][0]['media']['image']['src'])) {
                    $result['image_url'] = $post['attachments']['data'][0]['media']['image']['src'];
                }

                if (!empty($post['created_time'])) {
                    $result['date'] = Carbon::parse($post['created_time']);
                }
                break;
            }
        }

        return $result;
    }

    /**
     * Fetches content by scraping a web page.
     */
    protected function fetchScraping(AiSource $source, array $result): array
    {
        $response = Http::timeout(10)->get($source->url);
        if (!$response->successful()) return $result;

        $html = $response->body();

        if (preg_match('/<title>(.*?)<\/title>/si', $html, $matches)) {
            $result['headline'] = mb_convert_encoding(trim($matches[1]), 'UTF-8', 'UTF-8');
        }

        $cleanHtml = preg_replace('/<(script|style)\b[^>]*>(.*?)<\/\1>/is', '', $html);
        $result['content'] = mb_substr(
            mb_convert_encoding(preg_replace('/\s+/', ' ', trim(strip_tags($cleanHtml))), 'UTF-8', 'UTF-8'),
            0, 3000, 'UTF-8'
        );
        $result['url'] = $source->url;

        return $result;
    }

    // =========================================================================
    // PROMPT BUILDING
    // =========================================================================

    protected function buildPrompt(string $categoryName, array $sourceData, array $recentTitles): string
    {
        $currentDate = now()->format('l, d F Y, h:i A');
        $prompt = "Generate a short hot news article about Bangladesh. The news MUST belong to the category: \"{$categoryName}\".\n";
        $prompt .= "CRITICAL: Today's date and time is {$currentDate}. Ensure all references to time (like 'today', 'yesterday', 'this morning') are relative to this exact date and time. Do NOT invent dates from the past like 2024.\n";
        $prompt .= "CRITICAL: The news MUST BE ABOUT THE ABSOLUTE LATEST, MOST RECENT event (within the last 1-2 hours) happening in Bangladesh.\n";

        if (!empty($sourceData['headline']) && !empty($sourceData['content'])) {
            $sourceName = $sourceData['name'] ?? 'Unknown';
            $prompt .= "Ground the news article using the following source information from \"{$sourceName}\" (translate, rewrite, and format it, making it fresh, engaging, and unique):\n";
            $prompt .= "Source Headline: {$sourceData['headline']}\n";
            $prompt .= "Source Content:\n" . mb_substr($sourceData['content'], 0, 2000, 'UTF-8') . "\n\n";
            $prompt .= "CRITICAL: If the source news content DOES NOT fit/belong to the category \"{$categoryName}\", you MUST ignore the source news entirely and instead generate a fresh, unique news story about Bangladesh that belongs to the category \"{$categoryName}\".\n";
        }

        if (!empty($recentTitles)) {
            $titleList = '';
            foreach ($recentTitles as $i => $title) {
                $titleList .= ($i + 1) . ". " . $title . "\n";
            }
            $prompt .= "CRITICAL: Do NOT cover news/events that are similar to the following recent articles (avoid the same news stories or duplicating their angles):\n{$titleList}\n";
        }

        $prompt .= 'Return ONLY a valid JSON object with the structure below. Do not wrap the response in ```json markdown code blocks.
{
  "title": "Headline in Bengali (SEO-friendly, 60-80 characters)",
  "summary": "A concise SEO-friendly summary in Bengali (120-160 characters) for meta description and social sharing",
  "category": "Must be: ' . $categoryName . '",
  "content": "3-5 paragraphs of detailed news content in Bengali formatted in HTML (use <p> tags). Include quotes from relevant sources if applicable. Make it informative, factual, and engaging.",
  "tags": ["3-5 relevant Bengali tags for SEO, e.g. ঢাকা, রাজনীতি, অর্থনীতি"],
  "source_matched": true or false (set to true if you used the provided source news because it belongs to the category "' . $categoryName . '", or false if you ignored the source news because it did not belong to "' . $categoryName . '" and generated a fresh story instead),
  "district": "Name of the district in Bengali (e.g., ঢাকা, রাজবাড়ী, সিলেট, চট্টগ্রাম) if the news is specific to a location/district in Bangladesh, otherwise null or empty string",
  "division": "Name of the division in Bengali (e.g., ঢাকা, চট্টগ্রাম, সিলেট) if the news is specific to a location/division in Bangladesh, otherwise null or empty string",
  "image_prompt": "A highly detailed English prompt for generating an ultra-realistic, authentic journalistic news photograph of the news event. The prompt must specify professional editorial photography, DSLR camera, hyper-realistic, vivid colors, believable real-world news scene, and NO text in the image. E.g., A photorealistic news photograph of..."
}';

        return $prompt;
    }

    // =========================================================================
    // GEMINI API CALL (with Google Search Grounding)
    // =========================================================================

    protected function generateWithGemini(string $prompt): ?array
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->geminiModel}:generateContent?key={$this->apiKey}";

        $payload = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ]
        ];

        // Enable Google Search Grounding for real-time data accuracy
        if ($this->useGrounding) {
            $payload['tools'] = [
                ['googleSearch' => new \stdClass()]
            ];
        }

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
        ])->timeout(60)->post($url, $payload);

        $data = $response->json();

        if (!$response->successful() || !isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            return null;
        }

        $jsonText = trim($data['candidates'][0]['content']['parts'][0]['text']);

        // Remove markdown code block if present
        $jsonText = str_replace(['```json', '```'], '', $jsonText);
        $jsonText = trim($jsonText);

        $newsData = json_decode($jsonText, true);

        if (!$newsData || !isset($newsData['title'])) {
            return null;
        }

        return $newsData;
    }

    // =========================================================================
    // DUPLICATE DETECTION
    // =========================================================================

    protected function getRecentTitles(int $count = 25): array
    {
        return Article::orderBy('id', 'desc')
            ->take($count)
            ->pluck('title')
            ->toArray();
    }

    protected function computeHash(string $title, ?string $sourceUrl): string
    {
        $normalized = mb_strtolower(mb_substr(trim($title), 0, 50, 'UTF-8'), 'UTF-8');
        return md5($normalized . '|' . ($sourceUrl ?? ''));
    }

    protected function isDuplicate(string $hash): bool
    {
        return Article::where('content_hash', $hash)->exists();
    }

    // =========================================================================
    // IMAGE ENGINE
    // =========================================================================

    /**
     * Processes/generates an image based on the configured image mode.
     * Returns the local storage URL or null.
     */
    protected function processImage(array $newsData, array $sourceData): ?string
    {
        $sourceMatched = !isset($newsData['source_matched']) || $newsData['source_matched'] !== false;
        $realImgResult = null;
        $imageUrl = null;

        // ── Try real source image (unless animation-only mode) ──
        if ($this->imageMode !== 'animation') {
            $sourceImageUrl = $sourceData['image_url'] ?? null;
            $articlePageUrl = $sourceData['url'] ?? null;

            // Attempt 1: Direct RSS image URL
            if ($sourceMatched && !empty($sourceImageUrl) && filter_var($sourceImageUrl, FILTER_VALIDATE_URL)) {
                $realImgResult = $this->downloadImage($sourceImageUrl, $articlePageUrl);
            }

            // Attempt 2: Scrape OG image from the article page
            if (!$realImgResult && !empty($articlePageUrl) && filter_var($articlePageUrl, FILTER_VALIDATE_URL)) {
                $ogImage = $this->scrapeOgImage($articlePageUrl);
                if ($ogImage) {
                    $realImgResult = $this->downloadImage($ogImage, $articlePageUrl);
                }
            }
        }

        // ── Apply mode decision ──
        if ($this->imageMode === 'real') {
            // Real mode: use source image, fallback to AI if nothing found
            if ($realImgResult) {
                $processed = $this->applyGdProcessing($realImgResult['data'], $realImgResult['ext']);
                $imageUrl = $this->saveImageToStorage($processed, $realImgResult['ext']);
            } else {
                // Fallback: generate AI image so articles never go without images
                $aiResult = $this->generateGeminiImage($newsData['image_prompt'] ?? 'news event in bangladesh');
                if ($aiResult) {
                    $processed = $this->applyGdProcessing($aiResult['data'], $aiResult['ext']);
                    $imageUrl = $this->saveImageToStorage($processed, $aiResult['ext']);
                }
            }
        } elseif ($this->imageMode === 'auto') {
            if ($realImgResult) {
                $processed = $this->applyGdProcessing($realImgResult['data'], $realImgResult['ext']);
                $imageUrl = $this->saveImageToStorage($processed, $realImgResult['ext']);
            } else {
                $aiResult = $this->generateGeminiImage($newsData['image_prompt'] ?? 'news event in bangladesh');
                if ($aiResult) {
                    $processed = $this->applyGdProcessing($aiResult['data'], $aiResult['ext']);
                    $imageUrl = $this->saveImageToStorage($processed, $aiResult['ext']);
                }
            }
        } elseif ($this->imageMode === 'animation') {
            $aiResult = $this->generateGeminiImage($newsData['image_prompt'] ?? 'news event in bangladesh flat design vector art');
            if ($aiResult) {
                $processed = $this->applyGdProcessing($aiResult['data'], $aiResult['ext']);
                $imageUrl = $this->saveImageToStorage($processed, $aiResult['ext']);
            }
        }

        return $imageUrl;
    }

    /**
     * Scrapes the og:image meta tag from a given article URL.
     */
    protected function scrapeOgImage(string $url): ?string
    {
        try {
            $resp = Http::timeout(10)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                ])
                ->get($url);

            if (!$resp->successful()) return null;

            $html = $resp->body();

            // Try og:image first
            if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
                return $m[1];
            }
            // Try reverse attribute order
            if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/i', $html, $m)) {
                return $m[1];
            }
            // Try twitter:image
            if (preg_match('/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\']([^"\']+)["\']/i', $html, $m)) {
                return $m[1];
            }
            if (preg_match('/<meta[^>]+content=["\']([^"\']+)["\'][^>]+name=["\']twitter:image["\']/i', $html, $m)) {
                return $m[1];
            }
        } catch (\Throwable $e) {
            // Silent fail
        }

        return null;
    }

    protected function downloadImage(string $url, ?string $refererUrl = null): ?array
    {
        // Determine referer from the image URL domain
        $referer = $refererUrl;
        if (empty($referer)) {
            $parsedUrl = parse_url($url);
            $referer = ($parsedUrl['scheme'] ?? 'https') . '://' . ($parsedUrl['host'] ?? 'www.google.com') . '/';
        }

        // Try multiple user agents / referers for maximum compatibility
        $attempts = [
            [
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                'Accept' => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                'Referer' => $referer,
            ],
            [
                'User-Agent' => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
                'Accept' => 'image/*,*/*;q=0.8',
            ],
            [
                'User-Agent' => 'facebookexternalhit/1.1',
                'Accept' => '*/*',
            ],
        ];

        foreach ($attempts as $headers) {
            try {
                $resp = Http::timeout(20)
                    ->withHeaders($headers)
                    ->get($url);

                if (!$resp->successful()) continue;

                $body = $resp->body();
                if (strlen($body) < 3000) continue; // reject tiny/placeholder images

                $ct = $resp->header('Content-Type') ?? '';
                if (!str_contains($ct, 'image') && !preg_match('/\.(jpg|jpeg|png|webp|gif)(\?.*)?$/i', $url)) {
                    continue; // not an image
                }

                $ext = 'jpg';
                if (str_contains($ct, 'image/png')) $ext = 'png';
                elseif (str_contains($ct, 'image/webp')) $ext = 'webp';
                elseif (str_contains($url, '.png')) $ext = 'png';
                elseif (str_contains($url, '.webp')) $ext = 'webp';

                return ['data' => $body, 'ext' => $ext];
            } catch (\Throwable $e) {
                continue;
            }
        }

        return null;
    }

    protected function probeImageUrl(string $url): bool
    {
        try {
            $resp = Http::timeout(6)
                ->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; NewsBot/1.0)'])
                ->head($url);
            $ct = $resp->header('Content-Type') ?? '';
            return $resp->successful() &&
                   (str_contains($ct, 'image') ||
                    preg_match('/\.(jpg|jpeg|png|webp|gif)(\?.*)?$/i', $url));
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * GD Processing: 5% crop from all sides + 4-corner logo watermark.
     */
    protected function applyGdProcessing(string $rawData, string $ext): string
    {
        if (!function_exists('imagecreatefromstring')) return $rawData;

        try {
            $gdImg = @imagecreatefromstring($rawData);
            if ($gdImg === false) return $rawData;

            $w = imagesx($gdImg);
            $h = imagesy($gdImg);

            // Crop 5% from all 4 sides
            $cx = (int)($w * 0.05);
            $cy = (int)($h * 0.05);
            $cw = $w - $cx * 2;
            $ch = $h - $cy * 2;

            // Resize down if too large to save bandwidth (max width 800px)
            $maxWidth = 800;
            if ($cw > $maxWidth) {
                $ratio = $maxWidth / $cw;
                $finalW = $maxWidth;
                $finalH = (int)($ch * $ratio);
            } else {
                $finalW = $cw;
                $finalH = $ch;
            }

            $cropped = imagecreatetruecolor($finalW, $finalH);
            imagealphablending($cropped, false);
            imagesavealpha($cropped, true);
            imagecopyresampled($cropped, $gdImg, 0, 0, $cx, $cy, $finalW, $finalH, $cw, $ch);

            // 4-corner logo overlay
            $logoPath = Setting::where('key', 'site_logo')->value('value');
            if ($logoPath) {
                $logoFile = public_path($logoPath);
                if (file_exists($logoFile)) {
                    $logoRaw = @file_get_contents($logoFile);
                    if ($logoRaw !== false) {
                        $logoGd = @imagecreatefromstring($logoRaw);
                        if ($logoGd !== false) {
                            $lw = imagesx($logoGd);
                            $lh = imagesy($logoGd);
                            $newLw = 60;
                            $newLh = (int)($lh * ($newLw / $lw));
                            $scaled = imagecreatetruecolor($newLw, $newLh);
                            imagealphablending($scaled, false);
                            imagesavealpha($scaled, true);
                            $transparent = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
                            imagefill($scaled, 0, 0, $transparent);
                            imagecopyresampled($scaled, $logoGd, 0, 0, 0, 0, $newLw, $newLh, $lw, $lh);
                            imagealphablending($cropped, true);
                            $pad = 15;
                            imagecopy($cropped, $scaled, $pad, $pad, 0, 0, $newLw, $newLh);
                            imagecopy($cropped, $scaled, $finalW - $newLw - $pad, $pad, 0, 0, $newLw, $newLh);
                            imagecopy($cropped, $scaled, $pad, $finalH - $newLh - $pad, 0, 0, $newLw, $newLh);
                            imagecopy($cropped, $scaled, $finalW - $newLw - $pad, $finalH - $newLh - $pad, 0, 0, $newLw, $newLh);
                            imagedestroy($logoGd);
                            imagedestroy($scaled);
                        }
                    }
                }
            }

            ob_start();
            match ($ext) {
                'png' => imagepng($cropped),
                'webp' => imagewebp($cropped, null, 80),
                default => imagejpeg($cropped, null, 75),
            };
            $out = ob_get_clean();
            imagedestroy($gdImg);
            imagedestroy($cropped);
            return $out ?: $rawData;
        } catch (\Throwable $e) {
            return $rawData;
        }
    }

    protected function generateGeminiImage(string $prompt): ?array
    {
        try {
            // Using Pollinations.ai for reliable, free AI image generation without API key requirements
            $encodedPrompt = urlencode('A high quality realistic news photo for: ' . $prompt);
            $url = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=800&height=450&nologo=1&seed=" . rand(1, 99999);
            
            $resp = Http::timeout(30)->get($url);

            if ($resp->successful()) {
                $body = $resp->body();
                if (strlen($body) > 5000) {
                    return ['data' => $body, 'ext' => 'jpg'];
                }
            }
        } catch (\Throwable $e) {
            // Silent fail
        }
        return null;
    }

    protected function saveImageToStorage(string $rawData, string $ext): string
    {
        $filename = 'article_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
        if (!Storage::disk('public')->exists('articles')) {
            Storage::disk('public')->makeDirectory('articles');
        }
        Storage::disk('public')->put('articles/' . $filename, $rawData);
        return '/storage/articles/' . $filename;
    }

    // =========================================================================
    // ARTICLE SAVING
    // =========================================================================

    protected function saveArticle(
        array $newsData,
        ?int $categoryId,
        ?int $userId,
        ?string $imageUrl,
        string $contentHash,
        array $sourceData
    ): Article {
        $article = new Article();
        $article->title = mb_convert_encoding($newsData['title'], 'UTF-8', 'UTF-8');
        $article->slug = Str::slug($article->title) . '-' . time();

        if (empty(trim(Str::slug($article->title)))) {
            $article->slug = 'news-' . time() . '-' . rand(100, 999);
        }

        $article->content = mb_convert_encoding($newsData['content'], 'UTF-8', 'UTF-8');
        $article->summary = mb_convert_encoding($newsData['summary'] ?? '', 'UTF-8', 'UTF-8');
        $article->category_id = $categoryId ?: 1;
        $article->user_id = $userId ?? 1;
        $article->image_url = $imageUrl;
        $article->status = 'published';
        $article->content_hash = $contentHash;
        $article->source_url = $sourceData['url'] ?? null;
        $article->meta_description = $newsData['summary'] ?? null;
        $article->tags = $newsData['tags'] ?? null;

        // Source Name Tracking
        $sourceMatched = !isset($newsData['source_matched']) || $newsData['source_matched'] !== false;
        if (!empty($sourceData['name']) && $sourceMatched) {
            $article->source_name = $sourceData['name'];
        } else {
            $article->source_name = 'AI Generated';
        }

        // Location Tagging
        $district = !empty($newsData['district']) ? trim($newsData['district']) : null;
        $division = !empty($newsData['division']) ? trim($newsData['division']) : null;

        if ($district) {
            $article->district = $district;
            $article->division = empty($division) && isset($this->districtToDivision[$district])
                ? $this->districtToDivision[$district]
                : $division;
        } else {
            $article->district = null;
            $article->division = $division;
        }

        $article->save();

        return $article;
    }

    // =========================================================================
    // LOGGING
    // =========================================================================

    protected function log(
        string $status,
        ?string $sourceName,
        ?int $categoryId,
        ?int $articleId,
        ?string $error,
        float $startTime
    ): void {
        GenerationLog::create([
            'source_name' => $sourceName,
            'category_id' => $categoryId,
            'article_id' => $articleId,
            'status' => $status,
            'error_message' => $error,
            'response_time_ms' => (int)((microtime(true) - $startTime) * 1000),
            'gemini_model' => $this->geminiModel,
            'used_grounding' => $this->useGrounding,
        ]);
    }

    protected function fail(string $message, ?int $categoryId, float $startTime, string $status = 'failed'): array
    {
        $this->log($status, null, $categoryId, null, $message, $startTime);

        return [
            'success' => false,
            'message' => $message,
            'article' => null,
        ];
    }
}
