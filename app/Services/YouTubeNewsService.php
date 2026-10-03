<?php

namespace App\Services;

use App\Models\AiSource;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class YouTubeNewsService
{
    /**
     * User-Agent rotation pool for YouTube requests.
     */
    protected array $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0',
    ];

    /**
     * Top Bangladeshi television and leading digital news channels on YouTube.
     */
    protected array $defaultChannels = [
        'যমুনা টিভি'         => ['id' => 'UC2qb5FD5IRnXEP4CBdt7PvA', 'handle' => '@JamunaTVbd'],
        'সময় টিভি'          => ['id' => 'UCxHoBXkY88Tb8z1Ssj6CWsQ', 'handle' => '@somoytvnetupdate'],
        'চ্যানেল ২৪'         => ['id' => 'UCHLqIOMPk20w-6cFgkA90jw', 'handle' => '@channel24digital'],
        'ইনডিপেনডেন্ট টিভি'  => ['id' => 'UCATUkaOHwO9EP_W87zCiPbA', 'handle' => '@IndependentTelevision'],
        'একাত্তর টিভি'       => ['id' => 'UCtqvtAVmad5zywaziN6CbfA', 'handle' => '@EkattorTelevision'],
        'আরটিভি নিউজ'        => ['id' => 'UC2P5Fd5g41Gtdqf0Uzh8Qaw', 'handle' => '@RtvNews'],
        'নিউজ২৪ টিভি'        => ['id' => 'UCHKvg7FqyEqmOkYx0y4KtlA', 'handle' => '@NEWS24tvbd'],
        'বিবিসি নিউজ বাংলা'  => ['id' => 'UChPD2BQ2dJ-w00klQQkc-Qg', 'handle' => '@BBCBangla'],
        'প্রথম আলো ভিডিও'    => ['id' => 'UCeG7m5-AJ4I0H4EIleIty4Q', 'handle' => '@prothomalo'],
    ];

    /**
     * Get the default TV news channels.
     */
    public function getDefaultChannels(): array
    {
        return $this->defaultChannels;
    }

    /**
     * Resolve any YouTube channel handle, URL, or ID into a verified 24-char Channel ID (UC...).
     */
    public function resolveChannelId(string $input): ?string
    {
        $input = trim($input);
        if (empty($input)) {
            return null;
        }

        // Direct channel ID
        if (preg_match('/^UC[a-zA-Z0-9_-]{22}$/', $input)) {
            return $input;
        }

        // Channel ID inside RSS Feed URL
        if (preg_match('/channel_id=(UC[a-zA-Z0-9_-]{22})/i', $input, $m)) {
            return $m[1];
        }

        // Channel ID inside standard URL path (/channel/UC...)
        if (preg_match('/\/channel\/(UC[a-zA-Z0-9_-]{22})/i', $input, $m)) {
            return $m[1];
        }

        // Check cache for resolved handles/URLs
        $cacheKey = 'yt_cid_' . md5($input);
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Extract handle if present
        $url = $input;
        if (!str_starts_with($input, 'http://') && !str_starts_with($input, 'https://')) {
            $handle = str_starts_with($input, '@') ? $input : '@' . $input;
            $url = "https://www.youtube.com/{$handle}";
        }

        try {
            $response = Http::timeout(8)
                ->withHeaders([
                    'User-Agent' => $this->userAgents[0],
                    'Accept-Language' => 'en-US,en;q=0.9,bn;q=0.8',
                ])
                ->get($url);

            if ($response->successful()) {
                $html = $response->body();

                if (preg_match('/"externalId":"(UC[a-zA-Z0-9_-]{22})"/i', $html, $m)) {
                    Cache::put($cacheKey, $m[1], now()->addDays(7));
                    return $m[1];
                }
                if (preg_match('/"channelId":"(UC[a-zA-Z0-9_-]{22})"/i', $html, $m)) {
                    Cache::put($cacheKey, $m[1], now()->addDays(7));
                    return $m[1];
                }
                if (preg_match('/browse_id=(UC[a-zA-Z0-9_-]{22})/i', $html, $m)) {
                    Cache::put($cacheKey, $m[1], now()->addDays(7));
                    return $m[1];
                }
                if (preg_match('/<meta\s+itemprop=["\']channelId["\']\s+content=["\'](UC[a-zA-Z0-9_-]{22})["\']/i', $html, $m)) {
                    Cache::put($cacheKey, $m[1], now()->addDays(7));
                    return $m[1];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("YouTubeNewsService: Failed to resolve channel ID for {$input}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Fetch latest videos from a specific YouTube channel.
     */
    public function fetchChannelVideos(string $channelInput, int $limit = 5, ?string $channelLabel = null): array
    {
        $channelId = $this->resolveChannelId($channelInput);
        if (!$channelId) {
            return [];
        }

        $rssUrl = "https://www.youtube.com/feeds/videos.xml?channel_id={$channelId}";
        $items = [];

        try {
            $response = Http::timeout(8)
                ->withHeaders(['User-Agent' => $this->userAgents[0]])
                ->get($rssUrl);

            if (!$response->successful()) {
                return [];
            }

            $xml = @simplexml_load_string($response->body(), 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
            if (!$xml || !isset($xml->entry)) {
                return [];
            }

            $channelTitle = $channelLabel ?: trim((string)($xml->author->name ?? $xml->title ?? 'YouTube TV'));

            $count = 0;
            foreach ($xml->entry as $entry) {
                if ($count >= $limit) break;

                $rawTitle = trim((string)$entry->title);
                $link = (string)($entry->link->attributes()->href ?? '');
                $publishedStr = (string)$entry->published;
                $publishedAt = !empty($publishedStr) ? Carbon::parse($publishedStr) : now();

                // Video ID
                $ytNs = $entry->children('http://www.youtube.com/xml/schemas/2015');
                $videoId = (string)($ytNs->videoId ?? '');
                if (empty($videoId) && preg_match('/v=([a-zA-Z0-9_-]+)/', $link, $m)) {
                    $videoId = $m[1];
                }

                if (empty($videoId)) {
                    continue;
                }

                // Media group (description, thumbnail, statistics)
                $media = $entry->children('http://search.yahoo.com/mrss/');
                $thumbnail = null;
                $description = '';
                $views = 0;

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

                // Fallback thumbnail - maxres or hqdefault
                if (empty($thumbnail) && $videoId) {
                    $thumbnail = "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg";
                }

                $cleanedTitle = $this->cleanHeadline($rawTitle);

                $items[] = [
                    'video_id'       => $videoId,
                    'title'          => $cleanedTitle,
                    'raw_title'      => $rawTitle,
                    'channel_id'     => $channelId,
                    'channel_name'   => $channelTitle,
                    'url'            => $link ?: "https://www.youtube.com/watch?v={$videoId}",
                    'embed_url'      => "https://www.youtube.com/embed/{$videoId}",
                    'image_url'      => $thumbnail,
                    'description'    => $description,
                    'views'          => $views,
                    'traffic'        => $views > 0 ? number_format($views) . ' ভিউ' : 'ইউটিউব ব্রেকিং',
                    'published_at'   => $publishedAt,
                ];

                $count++;
            }
        } catch (\Throwable $e) {
            Log::warning("YouTubeNewsService: Failed to fetch channel {$channelInput}: " . $e->getMessage());
        }

        return $items;
    }

    /**
     * Fetch top breaking video bulletins across all default and custom configured TV channels.
     */
    public function fetchTopBangladeshiNews(int $limitPerChannel = 4): array
    {
        $allChannels = $this->defaultChannels;

        // Merge active YouTube channels from database (AiSource)
        try {
            $customSources = AiSource::where('type', 'youtube')
                ->where('status', true)
                ->get();

            foreach ($customSources as $cs) {
                if (!isset($allChannels[$cs->name])) {
                    $allChannels[$cs->name] = [
                        'id'     => $this->resolveChannelId($cs->url),
                        'handle' => $cs->url,
                        'name'   => $cs->name,
                    ];
                }
            }
        } catch (\Throwable $e) {
            // DB might be unavailable during tests
        }

        $items = [];
        foreach ($allChannels as $channelName => $info) {
            $cid = $info['id'] ?? null;
            if (!$cid && !empty($info['handle'])) {
                $cid = $this->resolveChannelId($info['handle']);
            }
            if (!$cid) continue;

            $videos = $this->fetchChannelVideos($cid, $limitPerChannel, $channelName);
            foreach ($videos as $v) {
                $v['source_type'] = 'YouTube News TV';
                $v['source_name'] = $channelName;
                $items[] = $v;
            }
        }

        return $items;
    }

    /**
     * Clean headline by stripping channel names, anchors, hashtags, and bulletin tags.
     */
    public function cleanHeadline(string $rawTitle): string
    {
        $title = trim($rawTitle);

        // Remove hashtags
        $title = preg_replace('/#[a-zA-Z0-9_\x{0980}-\x{09FF}]+/u', '', $title);

        // Split by pipes or dashes common in TV titles (e.g. "খবর | Somoy TV")
        if (str_contains($title, ' | ')) {
            $parts = explode(' | ', $title);
            $first = trim($parts[0]);
            // If first part has good length, take it
            if (mb_strlen($first, 'UTF-8') >= 15) {
                $title = $first;
            }
        }

        // Clean up common channel tags at the end
        $removePatterns = [
            '/\s*-\s*(যমুনা টিভি|সময় টিভি|চ্যানেল ২৪|একাত্তর টিভি|আরটিভি|Jamuna TV|Somoy TV|Channel 24|Independent TV|Ekattor TV|Rtv|News24|BBC Bangla|Prothom Alo)$/ui',
            '/\s*\|\s*(যমুনা টিভি|সময় টিভি|চ্যানেল ২৪|একাত্তর টিভি|আরটিভি|Jamuna TV|Somoy TV|Channel 24|Independent TV|Ekattor TV|Rtv|News24|BBC Bangla|Prothom Alo)$/ui',
        ];

        foreach ($removePatterns as $pattern) {
            $title = preg_replace($pattern, '', $title);
        }

        return trim($title);
    }
}
