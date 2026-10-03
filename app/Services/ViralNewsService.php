<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ViralNewsService
{
    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0',
    ];

    /**
     * Get live trending topics and viral news in Bangladesh.
     * Combines YouTube top news channels, Google Trends, Google Top Stories, and national leads.
     * Cached for 8 minutes unless forced refresh.
     */
    public function getLiveTrends(bool $forceRefresh = false): array
    {
        $cacheKey = 'bdbnews_live_viral_trends';

        if (!$forceRefresh && Cache::has($cacheKey)) {
            $cached = Cache::get($cacheKey);
            if (is_array($cached) && !empty($cached)) {
                return $this->attachArticleStatus($cached);
            }
        }

        $trends = [];

        // 1. Fetch YouTube Top News Channels (Jamuna, Somoy, Channel 24, Independent, Ekattor)
        $youtubeNews = $this->fetchYouTubeNewsChannels();
        $trends = array_merge($trends, $youtubeNews);

        // 2. Fetch Google Trends BD (Live Search Trends)
        $googleTrends = $this->fetchGoogleTrendsBd();
        $trends = array_merge($trends, $googleTrends);

        // 3. Fetch Google News Top Stories BD (Live Algorithmic Top News)
        $googleNewsTop = $this->fetchGoogleNewsTopStoriesBd();
        $trends = array_merge($trends, $googleNewsTop);

        // 4. Fetch Jagonews24 Lead RSS (National Top Buzz)
        $jagoLeads = $this->fetchJagoNewsLeads();
        $trends = array_merge($trends, $jagoLeads);

        // Deduplicate trends by similar title
        $uniqueTrends = $this->deduplicateTrends($trends);

        // Check if already posted
        $uniqueTrends = $this->attachArticleStatus($uniqueTrends);

        // Cache for 8 minutes
        Cache::put($cacheKey, $uniqueTrends, now()->addMinutes(8));

        return $uniqueTrends;
    }

    /**
     * Get the highest priority unpublished viral trend for auto generation.
     */
    public function getTopUnpublishedTrend(): ?array
    {
        $trends = $this->getLiveTrends(false);
        foreach ($trends as $trend) {
            if (empty($trend['is_posted'])) {
                return $trend;
            }
        }
        return null;
    }

    /**
     * Fetch latest breaking video bulletins from top Bangladeshi TV news channels on YouTube.
     */
    protected function fetchYouTubeNewsChannels(): array
    {
        $items = [];
        $channels = [
            'যমুনা টিভি' => ['id' => 'UC2qb5FD5IRnXEP4CBdt7PvA', 'handle' => '@JamunaTVbd'],
            'সময় টিভি' => ['id' => 'UCxHoBXkY88Tb8z1Ssj6CWsQ', 'handle' => '@somoytvnetupdate'],
            'চ্যানেল ২৪' => ['id' => 'UCHLqIOMPk20w-6cFgkA90jw', 'handle' => '@channel24digital'],
            'ইনডিপেনডেন্ট টিভি' => ['id' => 'UCATUkaOHwO9EP_W87zCiPbA', 'handle' => '@IndependentTelevision'],
            'একাত্তর টিভি' => ['id' => 'UCtqvtAVmad5zywaziN6CbfA', 'handle' => '@EkattorTelevision'],
            'আরটিভি নিউজ' => ['id' => 'UC2P5Fd5g41Gtdqf0Uzh8Qaw', 'handle' => '@RtvNews'],
        ];

        foreach ($channels as $channelName => $info) {
            $rssUrl = "https://www.youtube.com/feeds/videos.xml?channel_id={$info['id']}";
            try {
                $response = Http::timeout(8)
                    ->withHeaders(['User-Agent' => $this->userAgents[0]])
                    ->get($rssUrl);

                if (!$response->successful()) continue;

                $xml = @simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
                if (!$xml || !isset($xml->entry)) continue;

                $count = 0;
                foreach ($xml->entry as $entry) {
                    if ($count >= 4) break; // Top 4 latest per channel

                    $title = trim((string)$entry->title);
                    $link = (string)($entry->link->attributes()->href ?? '');
                    $publishedStr = (string)$entry->published;
                    $publishedAt = !empty($publishedStr) ? Carbon::parse($publishedStr) : now();

                    // Extract Video ID
                    $ytNs = $entry->children('http://www.youtube.com/xml/schemas/2015');
                    $videoId = (string)($ytNs->videoId ?? '');
                    if (empty($videoId) && preg_match('/v=([a-zA-Z0-9_-]+)/', $link, $m)) {
                        $videoId = $m[1];
                    }

                    // Media NS for thumbnail & description & view count
                    $media = $entry->children('http://search.yahoo.com/mrss/');
                    $thumbnail = null;
                    $description = '';
                    $views = null;

                    if ($media && isset($media->group)) {
                        if (isset($media->group->thumbnail)) {
                            $thumbAttrs = $media->group->thumbnail->attributes();
                            $thumbnail = (string)($thumbAttrs['url'] ?? '');
                        }
                        if (isset($media->group->description)) {
                            $description = strip_tags((string)$media->group->description);
                        }
                        if (isset($media->group->community->statistics)) {
                            $statAttrs = $media->group->community->statistics->attributes();
                            $views = (int)($statAttrs['views'] ?? 0);
                        }
                    }

                    // Fallback thumbnail
                    if (empty($thumbnail) && $videoId) {
                        $thumbnail = "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";
                    }

                    $isPolitical = $this->isPoliticalText($title . ' ' . $description);
                    $trafficText = $views > 0 ? number_format($views) . ' ভিউ' : 'ইউটিউব ব্রেকিং';

                    $badge = $isPolitical ? '🏛️ রাজনৈতিক ব্রেকিং' : '📺 ইউটিউব নিউজ';

                    $items[] = [
                        'id'            => 'yt_' . ($videoId ?: md5($title . $link)),
                        'title'         => $title,
                        'query'         => $title,
                        'source_type'   => 'YouTube News TV',
                        'source_name'   => $channelName,
                        'source_url'    => $link,
                        'image_url'     => $thumbnail,
                        'video_id'      => $videoId,
                        'embed_url'     => $videoId ? "https://www.youtube.com/embed/{$videoId}" : null,
                        'snippet'       => $description ? mb_substr($description, 0, 250, 'UTF-8') : "{$channelName}-এর ইউটিউব ভিডিও বুলেটিন: {$title}",
                        'traffic'       => $trafficText,
                        'traffic_raw'   => $views ?: 5000,
                        'is_hot'        => true,
                        'is_political'  => $isPolitical,
                        'badge'         => $badge,
                        'published_at'  => $publishedAt->toISOString(),
                        'time_ago'      => $publishedAt->locale('bn')->diffForHumans(),
                        'category_guess'=> $isPolitical ? 'রাজনীতি' : $this->guessCategory($title . ' ' . $description),
                    ];
                    $count++;
                }
            } catch (\Throwable $e) {
                Log::warning("ViralNewsService: Failed to fetch YouTube channel {$channelName}: " . $e->getMessage());
            }
        }

        return $items;
    }

    /**
     * Fetch Google Trends BD RSS
     */
    protected function fetchGoogleTrendsBd(): array
    {
        $items = [];
        $url = 'https://trends.google.com/trending/rss?geo=BD';

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => $this->userAgents[0]])
                ->get($url);

            if (!$response->successful()) {
                return $items;
            }

            $xml = @simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
            if (!$xml || !isset($xml->channel->item)) {
                return $items;
            }

            foreach ($xml->channel->item as $item) {
                $query = trim((string)$item->title);
                $pubDateStr = (string)($item->pubDate ?? '');
                $publishedAt = !empty($pubDateStr) ? Carbon::parse($pubDateStr) : now();

                $ht = $item->children('ht', true);
                $traffic = (string)($ht->approx_traffic ?? '100+');
                $picture = (string)($ht->picture ?? '');

                $headline = $query;
                $snippet = '';
                $newsUrl = (string)($item->link ?? '');
                $sourcePortal = 'Google Trends BD';

                if (isset($ht->news_item)) {
                    foreach ($ht->news_item as $newsItem) {
                        $newsTitle = (string)($newsItem->news_item_title ?? '');
                        $newsSnippet = (string)($newsItem->news_item_snippet ?? '');
                        $itemUrl = (string)($newsItem->news_item_url ?? '');
                        $itemSource = (string)($newsItem->news_item_source ?? '');

                        if (!empty($newsTitle)) {
                            $headline = html_entity_decode(strip_tags($newsTitle), ENT_QUOTES, 'UTF-8');
                        }
                        if (!empty($newsSnippet)) {
                            $snippet = html_entity_decode(strip_tags($newsSnippet), ENT_QUOTES, 'UTF-8');
                        }
                        if (!empty($itemUrl)) {
                            $newsUrl = $itemUrl;
                        }
                        if (!empty($itemSource)) {
                            $sourcePortal = $itemSource;
                        }
                        break;
                    }
                }

                $isPolitical = $this->isPoliticalText($headline . ' ' . $query . ' ' . $snippet);
                $badge = $isPolitical ? '🏛️ রাজনৈতিক ট্রেন্ড' : '🔥 ট্রেন্ডিং সার্চ';

                $items[] = [
                    'id'            => 'gt_' . md5($headline . $query),
                    'title'         => $headline,
                    'query'         => $query,
                    'source_type'   => 'Google Trends',
                    'source_name'   => $sourcePortal,
                    'source_url'    => $newsUrl,
                    'image_url'     => $picture ?: null,
                    'video_id'      => null,
                    'embed_url'     => null,
                    'snippet'       => $snippet ?: "বাংলাদেশে গুগলে বর্তমানে ট্রেন্ডিং অনুসন্ধান: {$query}",
                    'traffic'       => $traffic . ' অনুসন্ধান',
                    'traffic_raw'   => $this->parseTrafficNumber($traffic),
                    'is_hot'        => true,
                    'is_political'  => $isPolitical,
                    'badge'         => $badge,
                    'published_at'  => $publishedAt->toISOString(),
                    'time_ago'      => $publishedAt->locale('bn')->diffForHumans(),
                    'category_guess'=> $isPolitical ? 'রাজনীতি' : $this->guessCategory($headline . ' ' . $query),
                ];
            }
        } catch (\Throwable $e) {
            Log::warning('ViralNewsService: Failed to fetch Google Trends BD: ' . $e->getMessage());
        }

        return $items;
    }

    /**
     * Fetch Google News Top Stories BD RSS
     */
    protected function fetchGoogleNewsTopStoriesBd(): array
    {
        $items = [];
        $url = 'https://news.google.com/rss?hl=bn&gl=BD&ceid=BD:bn';

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => $this->userAgents[1]])
                ->get($url);

            if (!$response->successful()) {
                return $items;
            }

            $xml = @simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
            if (!$xml || !isset($xml->channel->item)) {
                return $items;
            }

            $count = 0;
            foreach ($xml->channel->item as $item) {
                if ($count >= 15) break;

                $rawTitle = trim((string)$item->title);
                $pubDateStr = (string)($item->pubDate ?? '');
                $publishedAt = !empty($pubDateStr) ? Carbon::parse($pubDateStr) : now();

                $headline = $rawTitle;
                $sourcePortal = 'Google News BD';

                if (str_contains($rawTitle, ' - ')) {
                    $parts = explode(' - ', $rawTitle);
                    $sourcePortal = trim(array_pop($parts));
                    $headline = trim(implode(' - ', $parts));
                }

                $link = (string)($item->link ?? '');
                $description = strip_tags((string)($item->description ?? ''));

                $isPolitical = $this->isPoliticalText($headline . ' ' . $description);
                $badge = $isPolitical ? '🏛️ রাজনৈতিক টপ স্টোরি' : '🚀 শীর্ষ সংবাদ';

                $items[] = [
                    'id'            => 'gn_' . md5($headline . $link),
                    'title'         => $headline,
                    'query'         => $headline,
                    'source_type'   => 'Google Top Stories',
                    'source_name'   => $sourcePortal,
                    'source_url'    => $link,
                    'image_url'     => null,
                    'video_id'      => null,
                    'embed_url'     => null,
                    'snippet'       => $description ?: $headline,
                    'traffic'       => 'শীর্ষ সংবাদ',
                    'traffic_raw'   => 5000,
                    'is_hot'        => true,
                    'is_political'  => $isPolitical,
                    'badge'         => $badge,
                    'published_at'  => $publishedAt->toISOString(),
                    'time_ago'      => $publishedAt->locale('bn')->diffForHumans(),
                    'category_guess'=> $isPolitical ? 'রাজনীতি' : $this->guessCategory($headline),
                ];
                $count++;
            }
        } catch (\Throwable $e) {
            Log::warning('ViralNewsService: Failed to fetch Google News Top Stories BD: ' . $e->getMessage());
        }

        return $items;
    }

    /**
     * Fetch Jagonews24 Lead News RSS
     */
    protected function fetchJagoNewsLeads(): array
    {
        $items = [];
        $url = 'https://www.jagonews24.com/rss/rss.xml';

        try {
            $response = Http::timeout(10)
                ->withHeaders(['User-Agent' => $this->userAgents[2]])
                ->get($url);

            if (!$response->successful()) {
                return $items;
            }

            $xml = @simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
            if (!$xml || !isset($xml->channel->item)) {
                return $items;
            }

            $count = 0;
            foreach ($xml->channel->item as $item) {
                if ($count >= 6) break;

                $headline = trim((string)$item->title);
                $pubDateStr = (string)($item->pubDate ?? '');
                $publishedAt = !empty($pubDateStr) ? Carbon::parse($pubDateStr) : now();
                $link = (string)($item->link ?? '');
                $description = strip_tags((string)($item->description ?? ''));

                $imageUrl = null;
                $namespaces = $item->getNameSpaces(true);
                $media = isset($namespaces['media']) ? $item->children($namespaces['media']) : null;
                if ($media && isset($media->content)) {
                    $imageUrl = (string)$media->content->attributes()->url;
                }
                if (empty($imageUrl) && isset($item->enclosure) && isset($item->enclosure['url'])) {
                    $imageUrl = (string)$item->enclosure['url'];
                }

                $isPolitical = $this->isPoliticalText($headline . ' ' . $description);
                $badge = $isPolitical ? '🏛️ রাজনৈতিক লিড' : '⚡ লিড নিউজ';

                $items[] = [
                    'id'            => 'jago_' . md5($headline . $link),
                    'title'         => $headline,
                    'query'         => $headline,
                    'source_type'   => 'জাতীয় লিড',
                    'source_name'   => 'Jagonews24',
                    'source_url'    => $link,
                    'image_url'     => $imageUrl,
                    'video_id'      => null,
                    'embed_url'     => null,
                    'snippet'       => $description,
                    'traffic'       => 'আলোচিত লিড',
                    'traffic_raw'   => 3000,
                    'is_hot'        => false,
                    'is_political'  => $isPolitical,
                    'badge'         => $badge,
                    'published_at'  => $publishedAt->toISOString(),
                    'time_ago'      => $publishedAt->locale('bn')->diffForHumans(),
                    'category_guess'=> $isPolitical ? 'রাজনীতি' : $this->guessCategory($headline . ' ' . $description),
                ];
                $count++;
            }
        } catch (\Throwable $e) {
            Log::warning('ViralNewsService: Failed to fetch Jagonews24 leads: ' . $e->getMessage());
        }

        return $items;
    }

    /**
     * Deduplicate trends based on headline similarity.
     */
    protected function deduplicateTrends(array $trends): array
    {
        $unique = [];
        $seenTitles = [];

        foreach ($trends as $trend) {
            $normalized = mb_strtolower(preg_replace('/[^\p{L}\p{N}\s]/u', '', $trend['title']));
            $isDuplicate = false;

            foreach ($seenTitles as $seen) {
                similar_text($normalized, $seen, $percent);
                if ($percent > 70) {
                    $isDuplicate = true;
                    break;
                }
            }

            if (!$isDuplicate) {
                $seenTitles[] = $normalized;
                $unique[] = $trend;
            }
        }

        return $unique;
    }

    /**
     * Check which trends have already been published in the articles table.
     */
    protected function attachArticleStatus(array $trends): array
    {
        $recentArticles = Article::select('id', 'title', 'slug', 'source_url', 'created_at')
            ->where('created_at', '>=', now()->subDays(3))
            ->get();

        foreach ($trends as &$trend) {
            $isPosted = false;
            $matchedArticle = null;

            foreach ($recentArticles as $art) {
                if (!empty($trend['source_url']) && !empty($art->source_url) && $trend['source_url'] === $art->source_url) {
                    $isPosted = true;
                    $matchedArticle = $art;
                    break;
                }

                // Check title similarity
                similar_text($trend['title'], $art->title, $pct);
                if ($pct >= 70) {
                    $isPosted = true;
                    $matchedArticle = $art;
                    break;
                }
            }

            $trend['is_posted'] = $isPosted;
            $trend['article_id'] = $matchedArticle?->id;
            $trend['article_slug'] = $matchedArticle?->slug;
            $trend['article_url'] = $matchedArticle ? route('news.show', $matchedArticle->slug) : null;
        }

        return $trends;
    }

    /**
     * Generate an article from a specific viral trend item using NewsGeneratorService.
     */
    public function generateFromTrend(array $trendItem, ?int $userId = null): array
    {
        $generator = new NewsGeneratorService();

        // Custom source payload designed specifically for viral & trending items
        $customSourceData = [
            'headline'         => $trendItem['title'] ?? '',
            'content'          => ($trendItem['snippet'] ?? '') . "\n\n(টপিক: " . ($trendItem['query'] ?? '') . ", উৎস: " . ($trendItem['source_name'] ?? '') . ", অনুসন্ধান মাত্রা: " . ($trendItem['traffic'] ?? 'উচ্চ') . ")",
            'url'              => $trendItem['source_url'] ?? '',
            'image_url'        => $trendItem['image_url'] ?? '',
            'name'             => 'ভাইরাল ট্রেন্ড: ' . ($trendItem['source_name'] ?? 'Google Trends'),
            'force_use_source' => true,
            'video_id'         => $trendItem['video_id'] ?? null,
            'is_political'     => $trendItem['is_political'] ?? false,
        ];

        // Match category
        $categoryIds = [];
        $targetCategoryName = $trendItem['is_political'] ? 'রাজনীতি' : ($trendItem['category_guess'] ?? 'জাতীয়');
        $cat = Category::where('name', $targetCategoryName)->first();
        if ($cat) {
            $categoryIds = [$cat->id];
        }

        $result = $generator->generate($categoryIds, $userId ?? auth()->id() ?? 1, $customSourceData);

        if ($result['success'] && isset($result['article'])) {
            // Update cache so this item is marked as posted immediately
            $cacheKey = 'bdbnews_live_viral_trends';
            if (Cache::has($cacheKey)) {
                $cached = Cache::get($cacheKey);
                foreach ($cached as &$item) {
                    if (($item['id'] ?? '') === ($trendItem['id'] ?? '')) {
                        $item['is_posted'] = true;
                        $item['article_id'] = $result['article']->id;
                        $item['article_slug'] = $result['article']->slug;
                        $item['article_url'] = route('news.show', $result['article']->slug);
                        break;
                    }
                }
                Cache::put($cacheKey, $cached, now()->addMinutes(8));
            }
        }

        return $result;
    }

    /**
     * Check if a news title or description represents political news in Bangladesh.
     */
    public function isPoliticalText(string $text): bool
    {
        $text = mb_strtolower($text);
        return (bool)preg_match('/(রাজনীতি|সরকার|উপদেষ্টা|প্রধান\s*উপদেষ্টা|ইউনুস|ড\.\s*ইউনুস|মন্ত্রী|বিমানমন্ত্রী|স্বরাষ্ট্রমন্ত্রী|আইনমন্ত্রী|মন্ত্রণালয়|বিএনপি|তারেক\s*রহমান|খালেদা\s*জিয়া|মির্জা\s*ফখরুল|জামায়াত|শিবির|আওয়ামী|আওয়ামী|শেখ\s*হাসিনা|হাসিনা|আন্দোলন|বৈষম্যবিরোধী|সমন্বয়ক|সমন্বয়ক|সাদিক\s*কায়েম|হাসনাত|সারজিস|নাহিদ\s*ইসলাম|নির্বাচন|সংসদ|ইসি|আদালত|বিচারপতি|হাইকোর্ট|সুপ্রিম\s*কোর্ট|পুলিশ|র‍্যাব|ডিবি|গ্রেফতার|রিমান্ড|আইনশৃঙ্খলা|সচিবালয়|দলীয়|সমাবেশ|হরতাল|বিক্ষোভ|স্মারকলিপি|বন্দর\s*ইজারা|জ্বালানি\s*সংকট|লোডশেডিং|দলীয়)/u', $text);
    }

    /**
     * Guess category from title and text.
     */
    protected function guessCategory(string $text): string
    {
        if ($this->isPoliticalText($text)) {
            return 'রাজনীতি';
        }

        $text = mb_strtolower($text);

        if (preg_match('/(খেলা|ক্রিকেট|ফুটবল|ম্যাচ|রান|উইকেট|বিশ্বকাপ|মেসি|রোনালদো|গোল|সিরিজ|বিপিএল|আইপিএল|ind vs|ban vs|icc|লিটন|শান্ত|সাকিব|রোহিত)/u', $text)) {
            return 'খেলাধুলা';
        }
        if (preg_match('/(সিনেমা|চলচ্চিত্র|গান|নাটক|অভিনেতা|অভিনেত্রী|নায়ক|নায়িকা|শাকিব|বুবলী|অপু|হলিউড|বলিউড|কনসার্ট|মডেল|তারকা)/u', $text)) {
            return 'বিনোদন';
        }
        if (preg_match('/(বাজেট|ব্যাংক|টাকা|মূল্যস্ফীতি|ডলার|বাজার|দাম|শেয়ারবাজার|অর্থনীতি|রাজস্ব|মুদ্রা|বাণিজ্য)/u', $text)) {
            return 'অর্থনীতি';
        }
        if (preg_match('/(ইসরায়েল|ফিলিস্তিন|গাজা|যুক্তরাষ্ট্র|আমেরিকা|রাশিয়া|ইউক্রেন|চীন|ইরান|ভারত|ট্রাম্প|বাইডেন|জাতিসংঘ|আন্তর্জাতিক)/u', $text)) {
            return 'আন্তর্জাতিক';
        }
        if (preg_match('/(বিজ্ঞান|প্রযুক্তি|এআই|স্মার্টফোন|মহাকাশ|নাসা|ইন্টারনেট|সাইবার|সফটওয়্যার)/u', $text)) {
            return 'প্রযুক্তি';
        }

        return 'জাতীয়';
    }

    /**
     * Parse numeric traffic string (e.g. "50K+", "200+") to integer.
     */
    protected function parseTrafficNumber(string $traffic): int
    {
        $clean = str_replace(['+', ',', ' '], '', $traffic);
        if (stripos($clean, 'K') !== false) {
            return (int)((float)str_ireplace('K', '', $clean) * 1000);
        }
        if (stripos($clean, 'M') !== false) {
            return (int)((float)str_ireplace('M', '', $clean) * 1000000);
        }
        return (int)$clean ?: 100;
    }
}
