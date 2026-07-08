<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SourceToNewsController extends Controller
{
    /**
     * View for All source list
     */
    public function index()
    {
        $jagoStatus = Setting::where('key', 'jago1_source_status')->value('value') ?? '1';
        $prothomStatus = Setting::where('key', 'prothom1_source_status')->value('value') ?? '1';
        
        Setting::updateOrCreate(['key' => 'jago1_source_status'], ['value' => $jagoStatus]);
        Setting::updateOrCreate(['key' => 'prothom1_source_status'], ['value' => $prothomStatus]);
        
        return view('admin.source-to-news.index', compact('jagoStatus', 'prothomStatus'));
    }

    /**
     * Toggle the status of the fixed Jago 1 source
     */
    public function toggleStatus(Request $request)
    {
        $source = $request->input('source', 'jago1');
        $status = $request->input('status') ? '1' : '0';
        Setting::updateOrCreate(['key' => $source . '_source_status'], ['value' => $status]);
        
        return response()->json(['success' => true, 'message' => 'Source status updated.']);
    }

    /**
     * View for Add new Snews
     */
    public function snews()
    {
        $articles = Article::with('category', 'user')
            ->whereIn('source_name', ['jago 1', 'prothom 1'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $jagoStatus = \App\Models\Setting::where('key', 'jago1_source_status')->value('value') ?? '1';
        $prothomStatus = \App\Models\Setting::where('key', 'prothom1_source_status')->value('value') ?? '1';

        $activeSources = [];
        if ($jagoStatus == '1') $activeSources[] = 'jago 1';
        if ($prothomStatus == '1') $activeSources[] = 'prothom 1';
            
        return view('admin.source-to-news.snews', compact('articles', 'activeSources'));
    }

    /**
     * Fetch N news items from Jagonews24 RSS
     */
    public function fetchRssNews(Request $request)
    {
        $request->validate([
            'number_of_news' => 'required|integer|min:1|max:20'
        ]);

        $jagoStatus = Setting::where('key', 'jago1_source_status')->value('value') ?? '1';
        $prothomStatus = Setting::where('key', 'prothom1_source_status')->value('value') ?? '1';

        if ($jagoStatus === '0' && $prothomStatus === '0') {
            return response()->json(['success' => false, 'message' => 'All fixed sources are currently disabled.']);
        }

        $numToClone = (int) $request->input('number_of_news');
        $recentUrls = Article::pluck('source_url')->filter()->toArray();
        $itemsToProcess = [];

        $jagoItems = [];
        $prothomItems = [];

        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0'
        ];

        // Fetch from Jago
        if ($jagoStatus === '1') {
            $feedUrl = 'https://www.jagonews24.com/rss/rss.xml';
            $body = null;
            foreach ($userAgents as $ua) {
                try {
                    $resp = Http::timeout(15)
                        ->withHeaders([
                            'User-Agent' => $ua,
                            'Accept' => 'application/rss+xml, application/xml, text/xml, */*',
                        ])->get($feedUrl);
                    if ($resp->successful()) { $body = $resp->body(); break; }
                } catch (\Exception $e) { continue; }
            }

            if ($body) {
                $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
                if ($xml && isset($xml->channel->item)) {
                    foreach ($xml->channel->item as $item) {
                        $url = (string)($item->link ?? '');
                        if (empty($url) || in_array($url, $recentUrls)) continue;
                        $headline = (string)($item->title ?? '');
                        $content = strip_tags((string)($item->description ?? ''));
                        
                        $imageUrl = '';
                        $namespaces = $item->getNameSpaces(true);
                        $media = isset($namespaces['media']) ? $item->children($namespaces['media']) : null;
                        if ($media && isset($media->content)) {
                            $imageUrl = (string)$media->content->attributes()->url;
                        }
                        if (empty($imageUrl)) {
                            if (isset($item->enclosure) && isset($item->enclosure['url'])) {
                                $imageUrl = (string)$item->enclosure['url'];
                            } else {
                                $rawDesc = (string)($item->description ?? '');
                                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $rawDesc, $match)) {
                                    $imageUrl = $match[1];
                                }
                            }
                        }

                        if (empty($headline) && empty($content)) continue;
                        $jagoItems[] = [
                            'headline' => $headline,
                            'content' => $content,
                            'url' => $url,
                            'image_url' => $imageUrl,
                            'name' => 'jago 1',
                            'force_use_source' => true
                        ];
                        if (count($jagoItems) >= $numToClone) break;
                    }
                }
            }
        }

        // Fetch from Prothom Alo
        if ($prothomStatus === '1') {
            $feedUrl = 'https://www.prothomalo.com/stories.rss';
            $body = null;
            foreach ($userAgents as $ua) {
                try {
                    $resp = Http::timeout(15)
                        ->withHeaders([
                            'User-Agent' => $ua,
                            'Accept' => 'application/rss+xml, application/xml, text/xml, */*',
                        ])->get($feedUrl);
                    if ($resp->successful()) { $body = $resp->body(); break; }
                } catch (\Exception $e) { continue; }
            }

            if ($body) {
                $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
                if ($xml && isset($xml->channel->item)) {
                    foreach ($xml->channel->item as $item) {
                        $url = (string)($item->link ?? '');
                        if (empty($url) || in_array($url, $recentUrls)) continue;
                        $headline = (string)($item->title ?? '');
                        $content = strip_tags((string)($item->description ?? ''));
                        
                        $imageUrl = '';
                        $namespaces = $item->getNameSpaces(true);
                        $media = isset($namespaces['media']) ? $item->children($namespaces['media']) : null;
                        
                        if ($media && isset($media->content)) {
                            $imageUrl = (string)$media->content->attributes()->url;
                        } elseif ($media && isset($media->thumbnail)) {
                            $imageUrl = (string)$media->thumbnail->attributes()->url;
                        }

                        if (empty($headline) && empty($content)) continue;
                        $prothomItems[] = [
                            'headline' => $headline,
                            'content' => $content,
                            'url' => $url,
                            'image_url' => $imageUrl,
                            'name' => 'prothom 1',
                            'force_use_source' => true
                        ];
                        if (count($prothomItems) >= $numToClone) break;
                    }
                }
            }
        }

        // Interleave items
        $jagoIdx = 0;
        $prothomIdx = 0;
        $jagoLen = count($jagoItems);
        $prothomLen = count($prothomItems);

        while (count($itemsToProcess) < $numToClone) {
            $added = false;
            if ($prothomIdx < $prothomLen) { // Prefer Prothom Alo first as per request
                $itemsToProcess[] = $prothomItems[$prothomIdx++];
                $added = true;
                if (count($itemsToProcess) >= $numToClone) break;
            }
            if ($jagoIdx < $jagoLen) {
                $itemsToProcess[] = $jagoItems[$jagoIdx++];
                $added = true;
                if (count($itemsToProcess) >= $numToClone) break;
            }
            if (!$added) {
                break; // No more items in both arrays
            }
        }

        if (empty($itemsToProcess)) {
            return response()->json(['success' => false, 'message' => 'No new articles found to clone.']);
        }

        return response()->json([
            'success' => true,
            'items' => $itemsToProcess
        ]);
    }

    /**
     * Process a single news item
     */
    public function processNewsItem(Request $request)
    {
        $item = $request->input('item');
        if (empty($item) || empty($item['url'])) {
            return response()->json(['success' => false, 'message' => 'Invalid item data provided.']);
        }

        // Prevent concurrent duplicates
        if (Article::where('source_url', $item['url'])->exists()) {
            return response()->json(['success' => true, 'message' => 'Already exists']);
        }

        try {
            $service = new \App\Services\NewsGeneratorService();
            $result = $service->generate([], auth()->id(), $item);

            if (isset($result['success']) && $result['success']) {
                return response()->json(['success' => true, 'message' => 'Article processed successfully']);
            } else {
                return response()->json(['success' => false, 'message' => $result['message'] ?? 'Failed during AI generation']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
