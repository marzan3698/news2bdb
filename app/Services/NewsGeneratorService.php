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
            // 1. Determine target category (rotation logic) unless force_use_source is active
            $isForcedSource = !empty($customSourceData['force_use_source']);
            
            if ($isForcedSource) {
                $targetCategory = null;
                $categoryName = '';
                $categoryId = null;
            } else {
                $targetCategory = $this->resolveTargetCategory($categoryIds);
                $categoryName = $targetCategory ? $targetCategory->name : 'জাতীয়';
                $categoryId = $targetCategory?->id;
            }

            // 3. Get recent titles and URLs for duplicate prevention
            $recentTitles = $this->getRecentTitles(25);
            $recentUrls = $this->getRecentUrls(100); // New method to get recently used URLs

            // 2. Fetch source content (multi-source intelligence or custom data)
            if (!empty($customSourceData) && (!empty($customSourceData['headline']) || !empty($customSourceData['content']))) {
                $sourceData = [
                    'headline'  => $customSourceData['headline'] ?? '',
                    'content'   => $customSourceData['content'] ?? '',
                    'url'       => $customSourceData['url'] ?? '',
                    'image_url' => $customSourceData['image_url'] ?? '',
                    'name'      => $customSourceData['name'] ?? 'n8n Push',
                    'force_use_source' => $customSourceData['force_use_source'] ?? false,
                ];
            } else {
                $sourceData = $this->fetchFromSources($categoryId, $recentUrls);
            }

            if (empty($sourceData['headline']) && empty($sourceData['content']) && ($sourceData['tier'] ?? 0) !== 5) {
                // If nothing found and not tier 5, just let tier 5 handle it
            }

            // 4. Build the prompt
            $prompt = $this->buildPrompt($categoryName, $sourceData, $recentTitles);

            // 5. Generate with Gemini
            $newsData = $this->generateWithGemini($prompt);
            if (!$newsData) {
                return $this->fail('Failed to generate from Gemini or invalid JSON returned.', $categoryId, $startTime);
            }

            // Clean up boolean source_matched from string "false"
            if (isset($newsData['source_matched'])) {
                if (is_string($newsData['source_matched']) && strtolower($newsData['source_matched']) === 'false') {
                    $newsData['source_matched'] = false;
                }
            }

            // Assign category ID from AI response if it was dynamic
            if (empty($categoryId) && !empty($newsData['category'])) {
                $matchedCat = Category::where('name', trim($newsData['category']))->first();
                if ($matchedCat) {
                    $categoryId = $matchedCat->id;
                } else {
                    $categoryId = Category::first()->id ?? 1;
                }
            }

            // 6. Check duplicate by hash
            $contentHash = $this->computeHash($newsData['title'], $sourceData['url'] ?? null);
            if ($this->isDuplicate($contentHash)) {
                return $this->fail('Duplicate content detected — skipped.', $categoryId ?? null, $startTime, 'skipped');
            }

            // 7. Process image
            $imageUrl = $this->processImage($newsData, $sourceData);

            // 8. Save article
            $article = $this->saveArticle($newsData, $categoryId, $userId, $imageUrl, $contentHash, $sourceData);

            // 9. Log success
            $logSourceName = $sourceData['name'] ?? null;
            if ($logSourceName && isset($sourceData['tier'])) {
                $logSourceName .= ' (Tier ' . $sourceData['tier'] . ')';
            }
            $this->log('success', $logSourceName, $categoryId, $article->id, null, $startTime);

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
    // 5-TIER INTELLIGENCE ENGINE
    // =========================================================================

    /**
     * Category-to-keyword mapping for Google News RSS queries.
     */
    protected array $categoryKeywords = [
        'জাতীয়'      => 'বাংলাদেশ সর্বশেষ খবর',
        'সারাবাংলা'   => 'বাংলাদেশ জেলা খবর',
        'রাজনীতি'     => 'বাংলাদেশ রাজনীতি',
        'আন্তর্জাতিক' => 'আন্তর্জাতিক খবর বাংলা',
        'অর্থনীতি'    => 'বাংলাদেশ অর্থনীতি ব্যবসা',
        'খেলাধুলা'    => 'বাংলাদেশ ক্রিকেট খেলাধুলা',
        'বিনোদন'      => 'বাংলাদেশ বিনোদন চলচ্চিত্র',
        'প্রযুক্তি'   => 'বাংলাদেশ প্রযুক্তি তথ্যপ্রযুক্তি',
    ];

    /**
     * User-Agent rotation pool for bypassing Cloudflare and bot detection.
     */
    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0',
        'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
        'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
    ];

    /**
     * 5-TIER FAILSAFE SOURCE FETCHING
     *
     * Tier 1: BBC Bengali RSS (always open, never blocks bots)
     * Tier 2: Google News RSS (category-targeted, always available)
     * Tier 3: Database sources with User-Agent rotation
     * Tier 4: Google Trends Bangladesh (trending topics)
     * Tier 5: Pure Gemini Grounding (AI generates from Google Search)
     */
    protected function fetchFromSources(?int $categoryId, array $recentUrls = []): array
    {
        $empty = ['headline' => null, 'content' => null, 'image_url' => null, 'name' => null, 'url' => null];

        // Resolve category name for keyword-based searches
        $categoryName = 'জাতীয়';
        if ($categoryId) {
            $cat = Category::find($categoryId);
            if ($cat) $categoryName = $cat->name;
        }

        // ── TIER 1: BBC Bengali RSS ──
        $result = $this->fetchBbcBengali($recentUrls);
        if (!empty($result['headline'])) {
            $result['tier'] = 1;
            return $result;
        }

        // ── TIER 2: Google News RSS (category-targeted) ──
        $result = $this->fetchGoogleNewsRss($categoryName, $recentUrls);
        if (!empty($result['headline'])) {
            $result['tier'] = 2;
            return $result;
        }

        // ── TIER 3: Database sources with UA rotation ──
        $result = $this->fetchFromDbSources($categoryId, $recentUrls);
        if (!empty($result['headline'])) {
            $result['tier'] = 3;
            return $result;
        }

        // ── TIER 4: Google Trends Bangladesh ──
        $result = $this->fetchGoogleTrends($recentUrls);
        if (!empty($result['headline'])) {
            $result['tier'] = 4;
            return $result;
        }

        // ── TIER 5: Return empty — Gemini Grounding will handle it ──
        $empty['tier'] = 5;
        $empty['name'] = 'Gemini Grounding (no source available)';
        return $empty;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TIER 1: BBC BENGALI RSS
    // ─────────────────────────────────────────────────────────────────────────

    protected function fetchBbcBengali(array $recentUrls = []): array
    {
        $reliableFeeds = [
            ['url' => 'https://feeds.bbci.co.uk/bengali/rss.xml', 'name' => 'BBC Bengali'],
            ['url' => 'https://feeds.feedburner.com/dwbengali', 'name' => 'DW Bengali'],
        ];

        foreach ($reliableFeeds as $feed) {
            $result = $this->parseRssUrl($feed['url'], $feed['name'], $recentUrls);
            if (!empty($result['headline'])) {
                return $result;
            }
        }

        return ['headline' => null, 'content' => null, 'image_url' => null, 'name' => null, 'url' => null];
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TIER 2: GOOGLE NEWS RSS
    // ─────────────────────────────────────────────────────────────────────────

    protected function fetchGoogleNewsRss(string $categoryName, array $recentUrls = []): array
    {
        $keyword = $this->categoryKeywords[$categoryName] ?? 'বাংলাদেশ সর্বশেষ খবর';
        $encodedKeyword = urlencode($keyword);
        $url = "https://news.google.com/rss/search?q={$encodedKeyword}&hl=bn&gl=BD&ceid=BD:bn";

        $result = $this->parseRssUrl($url, 'Google News (' . $categoryName . ')', $recentUrls);

        if (!empty($result['url']) && str_contains($result['url'], 'news.google.com')) {
            $realUrl = $this->resolveGoogleNewsUrl($result['url']);
            if ($realUrl) {
                $result['url'] = $realUrl;
            }
        }

        return $result;
    }

    protected function resolveGoogleNewsUrl(string $googleUrl): ?string
    {
        if (preg_match('/url=([^&]+)/', $googleUrl, $m)) {
            return urldecode($m[1]);
        }

        try {
            $resp = Http::timeout(8)
                ->withHeaders(['User-Agent' => $this->userAgents[0]])
                ->withOptions(['allow_redirects' => ['max' => 3, 'track_redirects' => true]])
                ->get($googleUrl);

            if ($resp->successful()) {
                $redirects = $resp->header('X-Guzzle-Redirect-History');
                if ($redirects) {
                    $urls = explode(', ', $redirects);
                    return end($urls);
                }
                $body = $resp->body();
                if (preg_match('/url=(https?:\/\/[^\s"\']+)/i', $body, $m)) {
                    return $m[1];
                }
            }
        } catch (\Throwable $e) {}

        return null;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TIER 3: DATABASE SOURCES WITH UA ROTATION
    // ─────────────────────────────────────────────────────────────────────────

    protected function fetchFromDbSources(?int $categoryId, array $recentUrls = []): array
    {
        $empty = ['headline' => null, 'content' => null, 'image_url' => null, 'name' => null, 'url' => null];

        $sources = collect();

        if ($categoryId) {
            $matched = AiSource::where('status', true)
                ->where('category_id', $categoryId)
                ->inRandomOrder()
                ->take(3)
                ->get();
            $sources = $sources->merge($matched);
        }

        if ($sources->count() < 5) {
            $generic = AiSource::where('status', true)
                ->whereNotIn('id', $sources->pluck('id')->toArray())
                ->inRandomOrder()
                ->take(5 - $sources->count())
                ->get();
            $sources = $sources->merge($generic);
        }

        if ($sources->isEmpty()) {
            return $empty;
        }

        $bestResult = null;
        $bestDate = null;

        foreach ($sources as $source) {
            $result = $this->fetchSingleSource($source, $recentUrls);
            if (!$result['headline']) continue;

            if (!$bestResult) {
                $bestResult = $result;
                $bestDate = $result['date'] ?? null;
                continue;
            }

            if (isset($result['date']) && $bestDate && $result['date']->greaterThan($bestDate)) {
                $bestResult = $result;
                $bestDate = $result['date'];
            }
        }

        return $bestResult ?? $empty;
    }

    // ─────────────────────────────────────────────────────────────────────────
    // TIER 4: GOOGLE TRENDS
    // ─────────────────────────────────────────────────────────────────────────

    protected function fetchGoogleTrends(array $recentUrls = []): array
    {
        $empty = ['headline' => null, 'content' => null, 'image_url' => null, 'name' => null, 'url' => null];

        try {
            $url = 'https://trends.google.com/trending/rss?geo=BD';
            $resp = Http::timeout(10)
                ->withHeaders(['User-Agent' => $this->userAgents[0]])
                ->get($url);

            if (!$resp->successful()) return $empty;

            $xml = @simplexml_load_string($resp->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
            if (!$xml || !isset($xml->channel->item)) return $empty;

            $items = [];
            $count = 0;
            foreach ($xml->channel->item as $item) {
                $items[] = $item;
                $count++;
                if ($count >= 5) break;
            }

            if (empty($items)) return $empty;
            $item = $items[array_rand($items)];

            $title = (string)$item->title;
            $description = strip_tags((string)($item->description ?? ''));

            $newsUrl = null;
            $newsImage = null;
            $htNs = $item->children('ht', true);
            if ($htNs && isset($htNs->news_item)) {
                foreach ($htNs->news_item as $newsItem) {
                    if (isset($newsItem->news_item_url)) {
                        $newsUrl = (string)$newsItem->news_item_url;
                    }
                    if (isset($newsItem->news_item_picture)) {
                        $newsImage = (string)$newsItem->news_item_picture;
                    }
                    break;
                }
            }

            return [
                'headline'  => $title,
                'content'   => !empty($description) ? $description : 'Trending topic in Bangladesh: ' . $title,
                'image_url' => $newsImage,
                'name'      => 'Google Trends BD',
                'url'       => $newsUrl ?? (string)($item->link ?? ''),
                'date'      => null,
            ];
            
            if (in_array($result['url'], $recentUrls)) {
                return $empty;
            }
            return $result;
        } catch (\Throwable $e) {
            return $empty;
        }
    }

    // ─────────────────────────────────────────────────────────────────────────
    // SHARED RSS PARSER (used by Tier 1, 2, and 3)
    // ─────────────────────────────────────────────────────────────────────────

    protected function parseRssUrl(string $feedUrl, string $sourceName, array $recentUrls = []): array
    {
        $empty = ['headline' => null, 'content' => null, 'image_url' => null, 'name' => $sourceName, 'url' => null];

        $body = null;
        foreach ($this->userAgents as $ua) {
            try {
                $resp = Http::timeout(12)
                    ->withHeaders([
                        'User-Agent' => $ua,
                        'Accept' => 'application/rss+xml, application/xml, text/xml, */*',
                        'Accept-Language' => 'bn-BD,bn;q=0.9,en;q=0.5',
                    ])
                    ->get($feedUrl);

                if ($resp->successful()) {
                    $responseBody = $resp->body();
                    if (str_contains($responseBody, '<rss') || str_contains($responseBody, '<feed') || str_contains($responseBody, '<?xml')) {
                        $body = $responseBody;
                        break;
                    }
                }
            } catch (\Throwable $e) {
                continue;
            }
        }

        if (!$body) return $empty;

        $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);

        if ($xml && isset($xml->channel->item)) {
            return $this->extractBestRssItem($xml->channel->item, $sourceName, $recentUrls);
        } elseif ($xml && $xml->getName() === 'feed') {
            return $this->extractBestAtomEntry($xml, $sourceName, $recentUrls);
        }

        return $empty;
    }

    protected function extractBestRssItem($items, string $sourceName, array $recentUrls = []): array
    {
        $empty = ['headline' => null, 'content' => null, 'image_url' => null, 'name' => $sourceName, 'url' => null];

        $cutoff = Carbon::now()->subHours(12);
        $selectedItem = null;
        $foundImage = null;
        $bestDate = null;

        foreach ($items as $item) {
            $itemLink = (string)($item->link ?? '');
            if (in_array($itemLink, $recentUrls)) {
                continue; // Skip already used items
            }

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
                    if ($parsedDate->lessThan($cutoff)) continue;
                } catch (\Throwable $e) {}
            }

            $imgCandidate = $this->extractImageFromRssItem($item);
            $hasImage = $imgCandidate && filter_var($imgCandidate, FILTER_VALIDATE_URL);

            if (!$selectedItem) {
                $selectedItem = $item;
                $bestDate = $parsedDate;
                $foundImage = $hasImage ? $imgCandidate : null;
            } else {
                if ($parsedDate && $bestDate && $parsedDate->greaterThan($bestDate)) {
                    $selectedItem = $item;
                    $bestDate = $parsedDate;
                    $foundImage = $hasImage ? $imgCandidate : null;
                }
            }
        }

        if ($selectedItem) {
            $contentNs = $selectedItem->children('http://purl.org/rss/1.0/modules/content/');
            $encodedContent = ($contentNs && isset($contentNs->encoded)) ? (string)$contentNs->encoded : '';
            $fullContent = strip_tags((string)$selectedItem->description . ' ' . $encodedContent);

            return [
                'headline'  => mb_convert_encoding((string)$selectedItem->title, 'UTF-8', 'UTF-8'),
                'content'   => mb_substr(mb_convert_encoding(trim($fullContent), 'UTF-8', 'UTF-8'), 0, 3000, 'UTF-8'),
                'image_url' => $foundImage,
                'name'      => $sourceName,
                'url'       => (string)($selectedItem->link ?? ''),
                'date'      => $bestDate,
            ];
        }

        return $empty;
    }

    protected function extractBestAtomEntry($feed, string $sourceName, array $recentUrls = []): array
    {
        $empty = ['headline' => null, 'content' => null, 'image_url' => null, 'name' => $sourceName, 'url' => null];

        $entries = $feed->entry ?? [];

        foreach ($entries as $entry) {
            $title = (string)($entry->title ?? '');
            $content = strip_tags((string)($entry->content ?? $entry->summary ?? ''));
            $link = '';

            foreach ($entry->link as $l) {
                $attrs = $l->attributes();
                if ((string)$attrs->rel === 'alternate' || empty((string)$attrs->rel)) {
                    $link = (string)$attrs->href;
                    break;
                }
            }

            if (!empty($title) && !in_array($link, $recentUrls)) {
                return [
                    'headline'  => mb_convert_encoding($title, 'UTF-8', 'UTF-8'),
                    'content'   => mb_substr(mb_convert_encoding(trim($content), 'UTF-8', 'UTF-8'), 0, 3000, 'UTF-8'),
                    'image_url' => null,
                    'name'      => $sourceName,
                    'url'       => $link,
                    'date'      => isset($entry->updated) ? Carbon::parse((string)$entry->updated) : null,
                ];
            }
        }

        return $empty;
    }

    protected function fetchSingleSource(AiSource $source, array $recentUrls = []): array
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
                return $this->parseRssUrl($source->url, $source->name, $recentUrls);
            } elseif ($source->type === 'facebook') {
                return $this->fetchFacebook($source, $result, $recentUrls);
            } elseif ($source->type === 'scraping') {
                return $this->fetchScraping($source, $result, $recentUrls);
            }
        } catch (\Exception $e) {}

        return $result;
    }

    protected function extractImageFromRssItem(\SimpleXMLElement $item): ?string
    {
        $mediaChildren = $item->children('http://search.yahoo.com/mrss/');
        if ($mediaChildren && isset($mediaChildren->content)) {
            $url = (string)$mediaChildren->content->attributes()->url;
            if (!empty($url)) return $url;
        }

        $mediaShort = $item->children('media', true);
        if ($mediaShort && isset($mediaShort->content)) {
            $url = (string)$mediaShort->content->attributes()->url;
            if (!empty($url)) return $url;
        }
        if ($mediaShort && isset($mediaShort->thumbnail)) {
            $url = (string)$mediaShort->thumbnail->attributes()->url;
            if (!empty($url)) return $url;
        }

        if (isset($item->enclosure)) {
            $encAttrs = $item->enclosure->attributes();
            $encType = (string)($encAttrs->type ?? '');
            $encUrl = (string)($encAttrs->url ?? '');
            if (!empty($encUrl) && (str_contains($encType, 'image') || !empty($encUrl))) {
                return $encUrl;
            }
        }

        $desc = (string)$item->description;
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $desc, $match)) {
            return $match[1];
        }

        $contentNs = $item->children('http://purl.org/rss/1.0/modules/content/');
        if ($contentNs && isset($contentNs->encoded)) {
            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', (string)$contentNs->encoded, $match)) {
                return $match[1];
            }
        }

        $itemXml = $item->asXML();
        if (preg_match('/url=["\']([^"\']+\.(jpg|jpeg|png|webp))["\'](?!\s*type=["\']text)/i', $itemXml, $match)) {
            return $match[1];
        }

        return null;
    }

    protected function fetchFacebook(AiSource $source, array $result, array $recentUrls = []): array
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
                $postUrl = "https://facebook.com/{$fbPageId}/posts/" . ($post['id'] ?? '');
                if (in_array($postUrl, $recentUrls)) continue;
                $result['url'] = $postUrl;

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

    protected function fetchScraping(AiSource $source, array $result, array $recentUrls = []): array
    {
        if (in_array($source->url, $recentUrls)) return $result;

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
        $currentDate = now('Asia/Dhaka')->format('l, d F Y, h:i A');
        
        // --- DYNAMIC CLONING LOGIC ---
        if (!empty($sourceData['force_use_source'])) {
            $categoriesList = Category::pluck('name')->implode(', ');
            $prompt = "Generate a highly engaging, SEO-friendly news article about Bangladesh in Bengali. You MUST completely translate, rewrite and adapt the provided source news accurately. DO NOT ignore the source.\n";
            $prompt .= "CRITICAL: Today's date and time is {$currentDate}. Ensure all references to time are relative to this exact date.\n";
            $prompt .= "Source Headline: {$sourceData['headline']}\n";
            $prompt .= "Source Content:\n" . mb_substr($sourceData['content'], 0, 2000, 'UTF-8') . "\n\n";
            $prompt .= "You must classify this news into ONE of the following EXACT categories: [{$categoriesList}].\n";
            
            $prompt .= 'Return ONLY a valid JSON object with the structure below. Do not wrap the response in ```json markdown code blocks.
{
  "title": "Headline in Bengali (Highly engaging, SEO-friendly, 60-80 characters)",
  "summary": "A concise SEO-friendly summary in Bengali (120-160 characters)",
  "category": "Pick exactly one category from the allowed list: ' . $categoriesList . '",
  "content": "3-5 paragraphs of detailed news content in Bengali formatted in HTML (use <p> tags). Include quotes or insights. Make it deeply informative and extremely engaging.",
  "tags": ["3-5 relevant Bengali tags for SEO"],
  "source_matched": true,
  "district": "Name of the district in Bengali (e.g., ঢাকা), otherwise null",
  "division": "Name of the division in Bengali (e.g., ঢাকা), otherwise null",
  "image_prompt": "A highly detailed English prompt for generating an ULTRA-HIGHLY ENERGETIC banner image. CRITICAL: NEVER ask to draw specific real-world people\'s faces. Instead, generate a highly dramatic, symbolic scene representing the core theme with vivid colors. NO text in the image."
}';
            return $prompt;
        }

        // --- REGULAR AUTOPILOT/ROTATION LOGIC ---
        $prompt = "Generate a highly engaging, deeply researched news article about Bangladesh. The news MUST belong to the category: \"{$categoryName}\".\n";
        $prompt .= "CRITICAL: Today's date and time is {$currentDate}. Ensure all references to time (like 'today', 'yesterday', 'this morning') are relative to this exact date and time. Do NOT invent dates from the past like 2024.\n";
        $prompt .= "CRITICAL: The news MUST BE ABOUT THE ABSOLUTE LATEST, MOST RECENT event (within the last 1-2 hours) happening in Bangladesh.\n";

        // Special Category Logic
        if (mb_stripos($categoryName, 'রাজনীতি') !== false || mb_stripos($categoryName, 'politics') !== false) {
            $prompt .= "SPECIAL RULE (POLITICAL SCIENCE): This is a Politics category. You MUST write this from a 'Political Science of Bangladesh' perspective. Do not just report the event; provide deep analysis, background context, and insights that make people eager to read and understand the political mechanics behind the news.\n";
        } elseif (mb_stripos($categoryName, 'সায়েন্স') !== false || mb_stripos($categoryName, 'science') !== false) {
            $prompt .= "SPECIAL RULE (SCIENCE): This is a Science-related category. You MUST focus on new, unknown, and groundbreaking scientific phenomena or facts related to the topic. The content should be extremely attractive and spark curiosity in the reader's mind about the wonders of science.\n";
        } else {
            $prompt .= "GENERAL RULE: Your writing style must be heavily research-based, highly attractive, and curiosity-inducing. Make the reader want to know more.\n";
        }

        if (!empty($sourceData['headline']) && !empty($sourceData['content'])) {
            $sourceName = $sourceData['name'] ?? 'Unknown';
            $prompt .= "Ground the news article using the following source information from \"{$sourceName}\" (translate, rewrite, and format it, making it fresh, engaging, and unique):\n";
            $prompt .= "Source Headline: {$sourceData['headline']}\n";
            $prompt .= "Source Content:\n" . mb_substr($sourceData['content'], 0, 2000, 'UTF-8') . "\n\n";
            $prompt .= "CRITICAL: If the source news content DOES NOT fit/belong to the category \"{$categoryName}\", you MUST ignore the source news entirely and instead generate a fresh, unique, and deeply researched story about Bangladesh that belongs to the category \"{$categoryName}\".\n";
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
  "title": "Headline in Bengali (Highly engaging, SEO-friendly, 60-80 characters)",
  "summary": "A concise SEO-friendly summary in Bengali (120-160 characters) that sparks high curiosity",
  "category": "Must be: ' . $categoryName . '",
  "content": "3-5 paragraphs of detailed, research-based news content in Bengali formatted in HTML (use <p> tags). Include quotes or insights. Make it deeply informative, factual, and extremely engaging.",
  "tags": ["3-5 relevant Bengali tags for SEO, e.g. ঢাকা, রাজনীতি, অর্থনীতি"],
  "source_matched": true or false (set to true if you used the provided source news because it belongs to the category "' . $categoryName . '", or false if you ignored the source news because it did not belong to "' . $categoryName . '" and generated a fresh story instead),
  "district": "Name of the district in Bengali (e.g., ঢাকা, রাজবাড়ী, সিলেট, চট্টগ্রাম) if the news is specific to a location/district in Bangladesh, otherwise null or empty string",
  "division": "Name of the division in Bengali (e.g., ঢাকা, চট্টগ্রাম, সিলেট) if the news is specific to a location/division in Bangladesh, otherwise null or empty string",
  "image_prompt": "A highly detailed English prompt for generating an ULTRA-HIGHLY ENERGETIC, EYE-CATCHING, and CURIOSITY-INDUCING cinematic banner image. CRITICAL: NEVER ask to draw specific real-world people\'s faces (e.g. politicians, cricketers). Instead, generate a highly dramatic, symbolic, or abstract scene representing the core theme with vivid colors, dynamic lighting, and action. NO text in the image."
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

    protected function getRecentUrls(int $count = 100): array
    {
        return Article::whereNotNull('source_url')
            ->orderBy('id', 'desc')
            ->take($count)
            ->pluck('source_url')
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
                $processed = $this->applyGdProcessing($realImgResult['data'], $realImgResult['ext'], $newsData['title'] ?? '');
                $imageUrl = $this->saveImageToStorage($processed, $realImgResult['ext']);
            } else {
                // Fallback: generate AI image so articles never go without images
                $aiResult = $this->generateGeminiImage($newsData['image_prompt'] ?? 'news event in bangladesh');
                if ($aiResult) {
                    $processed = $this->applyGdProcessing($aiResult['data'], $aiResult['ext'], $newsData['title'] ?? '');
                    $imageUrl = $this->saveImageToStorage($processed, $aiResult['ext']);
                }
            }
        } elseif ($this->imageMode === 'auto') {
            if ($realImgResult) {
                $processed = $this->applyGdProcessing($realImgResult['data'], $realImgResult['ext'], $newsData['title'] ?? '');
                $imageUrl = $this->saveImageToStorage($processed, $realImgResult['ext']);
            } else {
                $aiResult = $this->generateGeminiImage($newsData['image_prompt'] ?? 'news event in bangladesh');
                if ($aiResult) {
                    $processed = $this->applyGdProcessing($aiResult['data'], $aiResult['ext'], $newsData['title'] ?? '');
                    $imageUrl = $this->saveImageToStorage($processed, $aiResult['ext']);
                }
            }
        } elseif ($this->imageMode === 'animation') {
            $aiResult = $this->generateGeminiImage($newsData['image_prompt'] ?? 'news event in bangladesh flat design vector art');
            if ($aiResult) {
                $processed = $this->applyGdProcessing($aiResult['data'], $aiResult['ext'], $newsData['title'] ?? '');
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
        if (str_contains(parse_url($url, PHP_URL_HOST) ?? '', 'google.com')) {
            return null; // Do not scrape Google redirect pages for images
        }

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
     * Helper to wrap TTF text
     */
    protected function wrapTtfText($fontSize, $fontFace, $string, $width) {
        $ret = "";
        $arr = explode(' ', $string);
        foreach ($arr as $word) {
            $teststring = $ret . ' ' . $word;
            $testbox = @imagettfbbox($fontSize, 0, $fontFace, $teststring);
            if ($testbox && ($testbox[2] - $testbox[0]) > $width) {
                $ret .= ($ret == "" ? "" : "\n") . $word;
            } else {
                $ret .= ($ret == "" ? "" : ' ') . $word;
            }
        }
        return $ret;
    }

    /**
     * GD Processing: 5% crop from all sides + Red Title Banner + Top-left Logo watermark.
     */
    protected function applyGdProcessing(string $rawData, string $ext, string $title = ''): string
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

            // Banner configuration
            $bannerHeight = (int)($finalH * 0.40);
            $totalHeight = $finalH + $bannerHeight;

            $canvas = imagecreatetruecolor($finalW, $totalHeight);
            imagealphablending($canvas, true);
            imagesavealpha($canvas, true);

            // Background gradient for banner (Dark Red to darker red/black)
            for ($y = $finalH; $y < $totalHeight; $y++) {
                $progress = ($y - $finalH) / $bannerHeight; // 0.0 to 1.0
                $r = (int)(180 - (100 * $progress)); // 180 down to 80
                $color = imagecolorallocate($canvas, $r, 0, 0);
                imageline($canvas, 0, $y, $finalW, $y, $color);
            }

            // Top border for the banner
            $topBorderColor = imagecolorallocate($canvas, 255, 100, 100); // Lighter red/pinkish border
            imageline($canvas, 0, $finalH, $finalW, $finalH, $topBorderColor);
            imageline($canvas, 0, $finalH + 1, $finalW, $finalH + 1, $topBorderColor);

            // Light shade (drop shadow) just below the border
            for ($i = 0; $i < 6; $i++) {
                $shadowAlpha = 90 + ($i * 6); // Fading out shadow
                $shadowColor = imagecolorallocatealpha($canvas, 0, 0, 0, $shadowAlpha);
                imageline($canvas, 0, $finalH + 2 + $i, $finalW, $finalH + 2 + $i, $shadowColor);
            }

            // Copy the resized image to the top of the canvas
            imagecopyresampled($canvas, $gdImg, 0, 0, $cx, $cy, $finalW, $finalH, $cw, $ch);

            // Top-left logo overlay (Only one logo now)
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
                            $newLw = 80; // slightly larger for top-left
                            $newLh = (int)($lh * ($newLw / $lw));
                            
                            $scaled = imagecreatetruecolor($newLw, $newLh);
                            imagealphablending($scaled, false);
                            imagesavealpha($scaled, true);
                            $transparent = imagecolorallocatealpha($scaled, 0, 0, 0, 127);
                            imagefill($scaled, 0, 0, $transparent);
                            imagecopyresampled($scaled, $logoGd, 0, 0, 0, 0, $newLw, $newLh, $lw, $lh);
                            
                            imagealphablending($canvas, true);
                            $pad = 15;
                            // Only copy to top-left
                            imagecopy($canvas, $scaled, $pad, $pad, 0, 0, $newLw, $newLh);
                            
                            imagedestroy($logoGd);
                            imagedestroy($scaled);
                        }
                    }
                }
            }

            // Write Title on Red Banner
            if (!empty($title)) {
                $bengaliFontPath = public_path('fonts/SutonnyMJ-Bold.ttf');
                $englishFontPath = public_path('fonts/HindSiliguri-Bold.ttf'); // Assuming this exists
                
                if (file_exists($bengaliFontPath)) {
                    $fontSize = 28; // Smaller text to prevent overflow
                    $whiteColor = imagecolorallocate($canvas, 255, 255, 255);
                    $shadowColor = imagecolorallocatealpha($canvas, 0, 0, 0, 60); // Text shadow
                    $yellowColor = imagecolorallocate($canvas, 255, 204, 0); // Website link color
                    
                    try {
                        $translator = new \ArNishan\BanglaConverter\Translate();
                        $bijoyTitle = $translator->unicodeToBijoy($title);
                    } catch (\Throwable $e) {
                        $bijoyTitle = $title;
                    }

                    // Wrap text to fit inside the banner (with some padding)
                    $wrappedOriginalText = $this->wrapTtfText($fontSize, $englishFontPath, $title, $finalW - 40);
                    $originalLines = explode("\n", $wrappedOriginalText);
                    $totalLines = count($originalLines);
                    
                    $lineHeight = $fontSize * 1.6;
                    $totalTextHeight = $totalLines * $lineHeight;
                    
                    // Center the text in the top part of the banner, leaving space for the website link
                    $titleAreaHeight = $bannerHeight - 40; 
                    
                    // Calculate starting Y and ensure it doesn't overflow above the banner
                    $calculatedStartY = $finalH + (int)(($titleAreaHeight - $totalTextHeight) / 2) + $fontSize;
                    $minStartY = $finalH + $fontSize + 15; // At least 15px below the border
                    $startY = max($minStartY, $calculatedStartY);

                    foreach ($originalLines as $line) {
                        // Split line into English/Number tokens and Bengali/Symbol tokens
                        // We capture words of A-Z, a-z, 0-9
                        $tokens = preg_split('/([a-zA-Z0-9]+)/u', $line, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
                        
                        // First pass to calculate line width for centering
                        $lineWidth = 0;
                        $tokenData = [];
                        foreach ($tokens as $token) {
                            if (preg_match('/^[a-zA-Z0-9]+$/', $token)) {
                                $font = $englishFontPath;
                                $text = $token;
                            } else {
                                $font = $bengaliFontPath;
                                $text = $translator->unicodeToBijoy($token);
                                
                                // FIX MISSING GLYPHS in SutonnyMJ-Bold.ttf
                                // "ল-ফলা" generated as ASCII 173 (­), change to 172 (¬)
                                $text = str_replace(chr(173), chr(172), $text);
                                // Removed the "রেফ" replacement to fix Reph rendering issues
                            }
                            
                            $bbox = @imagettfbbox($fontSize, 0, $font, $text);
                            $width = 0;
                            if ($bbox) {
                                $width = abs($bbox[2] - $bbox[0]);
                            }
                            
                            $tokenData[] = [
                                'font' => $font,
                                'text' => $text,
                                'width' => $width
                            ];
                            $lineWidth += $width;
                        }
                        
                        // Draw tokens horizontally centered
                        $startX = (int)(($finalW - $lineWidth) / 2);
                        foreach ($tokenData as $td) {
                            // Draw text shadow
                            @imagettftext($canvas, $fontSize, 0, $startX + 2, $startY + 2, $shadowColor, $td['font'], $td['text']);
                            // Draw main text
                            @imagettftext($canvas, $fontSize, 0, $startX, $startY, $whiteColor, $td['font'], $td['text']);
                            $startX += $td['width'];
                        }
                        
                        $startY += $lineHeight;
                    }
                    
                    // Add Website URL at the left bottom
                    $websiteText = "www.bdbnews.com";
                    $websiteFontSize = 14;
                    $websiteY = $totalHeight - 15; // 15px from bottom
                    @imagettftext($canvas, $websiteFontSize, 0, 25, $websiteY, $yellowColor, $englishFontPath, $websiteText);
                    
                    // Add AI Powered at the right bottom
                    $aiText = "AI Powered";
                    $aiBox = @imagettfbbox($websiteFontSize, 0, $englishFontPath, $aiText);
                    $aiWidth = $aiBox ? abs($aiBox[2] - $aiBox[0]) : 100;
                    $aiX = $finalW - $aiWidth - 25;
                    @imagettftext($canvas, $websiteFontSize, 0, $aiX, $websiteY, $yellowColor, $englishFontPath, $aiText);
                }
            }

            ob_start();
            match ($ext) {
                'png' => imagepng($canvas),
                'webp' => imagewebp($canvas, null, 80),
                default => imagejpeg($canvas, null, 75),
            };
            $outputData = ob_get_clean();

            imagedestroy($gdImg);
            imagedestroy($canvas);

            return $outputData !== false ? $outputData : $rawData;
        } catch (\Throwable $e) {
            return $rawData;
        }
    }


    protected function generateGeminiImage(string $prompt): ?array
    {
        try {
            $safePrompt = mb_substr('A high quality realistic news photo for: ' . $prompt, 0, 900, 'UTF-8');
            $openAiKey = \App\Models\Setting::where('key', 'openai_api_key')->value('value');
            $provider = \App\Models\Setting::where('key', 'ai_image_provider')->value('value') ?? 'pollinations';

            if ($provider === 'openai' && !empty($openAiKey)) {
                // Try OpenAI DALL-E 3
                $aiResponse = Http::timeout(60)
                    ->withHeaders([
                        'Authorization' => 'Bearer ' . $openAiKey,
                        'Content-Type' => 'application/json',
                    ])
                    ->post('https://api.openai.com/v1/images/generations', [
                        'model' => 'dall-e-3',
                        'prompt' => $safePrompt,
                        'n' => 1,
                        'size' => '1024x1024'
                    ]);

                if ($aiResponse->successful()) {
                    $imageUrl = $aiResponse->json('data.0.url');
                    if ($imageUrl) {
                        $imgData = Http::timeout(30)->get($imageUrl)->body();
                        if (strlen($imgData) > 5000) {
                            return ['data' => $imgData, 'ext' => 'jpg'];
                        }
                    }
                }
            }

            // Fallback: Using Pollinations.ai for free AI image generation
            $encodedPrompt = urlencode($safePrompt);
            $url = "https://image.pollinations.ai/prompt/{$encodedPrompt}?width=800&height=450&nologo=1&seed=" . rand(1, 99999);
            
            $resp = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36'
                ])
                ->get($url);

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
