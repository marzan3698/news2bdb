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
        $jagoStatus = Setting::where('key', 'jago1_source_status')->value('value');
        // If setting doesn't exist, default to active (1)
        if ($jagoStatus === null) {
            $jagoStatus = 1;
            Setting::updateOrCreate(['key' => 'jago1_source_status'], ['value' => '1']);
        }
        
        return view('admin.source-to-news.index', compact('jagoStatus'));
    }

    /**
     * Toggle the status of the fixed Jago 1 source
     */
    public function toggleStatus(Request $request)
    {
        $status = $request->input('status') ? '1' : '0';
        Setting::updateOrCreate(['key' => 'jago1_source_status'], ['value' => $status]);
        
        return response()->json(['success' => true, 'message' => 'Source status updated.']);
    }

    /**
     * View for Add new Snews
     */
    public function snews()
    {
        $articles = Article::with('category', 'user')
            ->where('source_name', 'jago 1')
            ->orderBy('created_at', 'desc')
            ->get();
            
        return view('admin.source-to-news.snews', compact('articles'));
    }

    /**
     * Clone N news from Jagonews24 RSS
     */
    public function cloneNews(Request $request)
    {
        $request->validate([
            'number_of_news' => 'required|integer|min:1|max:20'
        ]);

        $jagoStatus = Setting::where('key', 'jago1_source_status')->value('value');
        if ($jagoStatus === '0') {
            return response()->json(['success' => false, 'message' => 'Source is currently disabled.']);
        }

        $numToClone = (int) $request->input('number_of_news');
        $feedUrl = 'https://www.jagonews24.com/rss/rss.xml';
        
        // Use the same User-Agent bypass technique from NewsGeneratorService
        $userAgents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Safari/605.1.15',
            'Mozilla/5.0 (X11; Linux x86_64; rv:128.0) Gecko/20100101 Firefox/128.0'
        ];
        
        $body = null;
        foreach ($userAgents as $ua) {
            try {
                $resp = Http::timeout(15)
                    ->withHeaders([
                        'User-Agent' => $ua,
                        'Accept' => 'application/rss+xml, application/xml, text/xml, */*',
                    ])
                    ->get($feedUrl);

                if ($resp->successful()) {
                    $body = $resp->body();
                    break;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        if (!$body) {
            return response()->json(['success' => false, 'message' => 'Could not fetch RSS feed from Jagonews24.']);
        }

        $xml = @simplexml_load_string($body, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);
        if (!$xml || !isset($xml->channel->item)) {
            return response()->json(['success' => false, 'message' => 'Invalid RSS XML structure.']);
        }

        $service = new \App\Services\NewsGeneratorService();
        $recentUrls = Article::pluck('source_url')->filter()->toArray();
        $userId = auth()->id();
        
        $clonedCount = 0;
        $failedCount = 0;

        foreach ($xml->channel->item as $item) {
            if ($clonedCount >= $numToClone) {
                break;
            }

            $url = (string)($item->link ?? '');
            if (empty($url) || in_array($url, $recentUrls)) {
                continue; // Skip if already cloned
            }

            $headline = (string)($item->title ?? '');
            $content = strip_tags((string)($item->description ?? ''));
            
            // Extract Image
            $imageUrl = '';
            // Jagonews usually puts image in <enclosure> or inside <description> as <img src="...">
            // Let's check enclosure first
            if (isset($item->enclosure) && isset($item->enclosure['url'])) {
                $imageUrl = (string)$item->enclosure['url'];
            } 
            // Fallback: regex search in raw description (if we didn't strip tags)
            if (empty($imageUrl)) {
                $rawDesc = (string)($item->description ?? '');
                if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $rawDesc, $match)) {
                    $imageUrl = $match[1];
                }
            }

            if (empty($headline) && empty($content)) {
                continue;
            }

            $customSourceData = [
                'headline' => $headline,
                'content' => $content,
                'url' => $url,
                'image_url' => $imageUrl,
                'name' => 'jago 1'
            ];

            // Send to the NewsGeneratorService to rewrite with Gemini and generate Image
            $result = $service->generate([], $userId, $customSourceData);
            
            if (isset($result['success']) && $result['success']) {
                $clonedCount++;
            } else {
                $failedCount++;
            }
        }

        return response()->json([
            'success' => true, 
            'message' => "Cloned $clonedCount news items successfully. " . ($failedCount > 0 ? "($failedCount failed/skipped)" : "")
        ]);
    }
}
