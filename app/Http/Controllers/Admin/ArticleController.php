<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use App\Models\Setting;
use App\Models\AiSource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class ArticleController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        return view('admin.articles.index', compact('categories'));
    }

    public function data()
    {
        $articles = Article::with('category', 'user')->orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $articles]);
    }

    public function create()
    {
        $categories = Category::all();
        return view('admin.articles.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'content' => 'required'
        ]);

        $article = new Article();
        $article->title = $request->title;
        $article->slug = Str::slug($request->title) . '-' . time();
        $article->content = $request->content;
        $article->category_id = $request->category_id;
        $article->user_id = auth()->id();
        $article->status = 'published';
        $article->save();

        return redirect()->route('admin.articles.index')->with('success', 'Article created successfully.');
    }

    public function destroy($id)
    {
        $article = Article::findOrFail($id);

        // Delete local image if stored in storage
        if ($article->image_url && str_starts_with($article->image_url, '/storage/articles/')) {
            $path = str_replace('/storage/', '', $article->image_url);
            \Illuminate\Support\Facades\Storage::disk('public')->delete($path);
        }

        $article->delete();
        return response()->json(['success' => true, 'message' => 'Article deleted.']);
    }

    public function autoGenerate(Request $request)
    {
        $apiKey = Setting::where('key', 'gemini_api_key')->value('value');
        if (!$apiKey) {
            return response()->json(['success' => false, 'message' => 'API Key not configured.']);
        }

        try {
            // Get selected categories from request
            $categoryIds = $request->input('category_ids', []);
            if (empty($categoryIds)) {
                $categoryIds = Category::pluck('id')->toArray();
            }

            // Category Rotation Logic
            $targetCategoryId = null;
            if (!empty($categoryIds)) {
                $lastArticle = Article::orderBy('id', 'desc')->first();
                $lastCategoryId = $lastArticle ? $lastArticle->category_id : null;

                if ($lastCategoryId && in_array($lastCategoryId, $categoryIds)) {
                    $index = array_search($lastCategoryId, $categoryIds);
                    $nextIndex = ($index + 1) % count($categoryIds);
                    $targetCategoryId = $categoryIds[$nextIndex];
                } else {
                    $targetCategoryId = $categoryIds[0];
                }
            }

            $targetCategory = null;
            $categoryName = 'জাতীয়';
            if ($targetCategoryId) {
                $targetCategory = Category::find($targetCategoryId);
                if ($targetCategory) {
                    $categoryName = $targetCategory->name;
                }
            }

            // Fetch live source if configured
            $source = AiSource::where('status', true)->inRandomOrder()->first();
            $sourceContentText = null;
            $sourceHeadline = null;
            $sourceImageUrl = null;
            $sourceName = null;

            if ($source) {
                $sourceName = $source->name;
                if ($source->type === 'rss') {
                    try {
                        $rssResponse = Http::timeout(15)->withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; NewsBot/1.0)'])->get($source->url);
                        if ($rssResponse->successful()) {
                            $rawXml = $rssResponse->body();

                            // Register common RSS namespaces
                            $xml = simplexml_load_string($rawXml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_NOERROR);

                            if ($xml && isset($xml->channel->item)) {
                                // Try multiple items to find one with an image AND within the last 24 hours
                                $items = $xml->channel->item;
                                $selectedItem = null;
                                $foundImage = null;

                                // Cutoff: 24 hours ago from current server time
                                $cutoff = Carbon::now()->subHours(24);

                                foreach ($items as $item) {
                                    // ── FRESHNESS CHECK ───────────────────────────
                                    // Parse pubDate / dc:date / atom:updated from item
                                    $pubDateStr = (string)($item->pubDate ?? '');
                                    if (empty($pubDateStr)) {
                                        // Try dc:date namespace
                                        $dcNs = $item->children('http://purl.org/dc/elements/1.1/');
                                        if ($dcNs && isset($dcNs->date)) {
                                            $pubDateStr = (string)$dcNs->date;
                                        }
                                    }

                                    if (!empty($pubDateStr)) {
                                        try {
                                            $itemDate = Carbon::parse($pubDateStr);
                                            if ($itemDate->lessThan($cutoff)) {
                                                // This item is older than 24 hours — skip it
                                                continue;
                                            }
                                        } catch (\Throwable $dateEx) {
                                            // Unparseable date — allow the item through (don't skip)
                                        }
                                    }
                                    // ─────────────────────────────────────────────

                                    $imgCandidate = null;

                                    // Method 1: media:content (most common: prothomalo, bdnews24, etc)
                                    $mediaChildren = $item->children('http://search.yahoo.com/mrss/');
                                    if (!$imgCandidate && $mediaChildren && isset($mediaChildren->content)) {
                                        $attrs = $mediaChildren->content->attributes();
                                        if (!empty((string)$attrs->url)) {
                                            $imgCandidate = (string)$attrs->url;
                                        }
                                    }

                                    // Method 2: media namespace shorthand 'media'
                                    if (!$imgCandidate) {
                                        $mediaShort = $item->children('media', true);
                                        if ($mediaShort && isset($mediaShort->content)) {
                                            $attrs = $mediaShort->content->attributes();
                                            if (!empty((string)$attrs->url)) {
                                                $imgCandidate = (string)$attrs->url;
                                            }
                                        }
                                        // Also try media:thumbnail
                                        if (!$imgCandidate && $mediaShort && isset($mediaShort->thumbnail)) {
                                            $attrs = $mediaShort->thumbnail->attributes();
                                            if (!empty((string)$attrs->url)) {
                                                $imgCandidate = (string)$attrs->url;
                                            }
                                        }
                                    }

                                    // Method 3: enclosure tag (common in many RSS feeds)
                                    if (!$imgCandidate && isset($item->enclosure)) {
                                        $encAttrs = $item->enclosure->attributes();
                                        $encType = (string)($encAttrs->type ?? '');
                                        if (str_contains($encType, 'image')) {
                                            $imgCandidate = (string)($encAttrs->url ?? '');
                                        } elseif (!empty((string)($encAttrs->url ?? ''))) {
                                            $imgCandidate = (string)$encAttrs->url;
                                        }
                                    }

                                    // Method 4: Parse <description> HTML for <img> tags
                                    if (!$imgCandidate) {
                                        $desc = (string)$item->description;
                                        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $desc, $imgMatch)) {
                                            $imgCandidate = $imgMatch[1];
                                        }
                                    }

                                    // Method 5: content:encoded HTML
                                    if (!$imgCandidate) {
                                        $contentNs = $item->children('http://purl.org/rss/1.0/modules/content/');
                                        if ($contentNs && isset($contentNs->encoded)) {
                                            $encodedHtml = (string)$contentNs->encoded;
                                            if (preg_match('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $encodedHtml, $imgMatch)) {
                                                $imgCandidate = $imgMatch[1];
                                            }
                                        }
                                    }

                                    // Method 6: Regex fallback on raw XML for this item (last resort)
                                    if (!$imgCandidate) {
                                        $itemXml = $item->asXML();
                                        if (preg_match('/url=["\']([^"\']+\.(jpg|jpeg|png|webp))["\']/i', $itemXml, $imgMatch)) {
                                            $imgCandidate = $imgMatch[1];
                                        }
                                    }

                                    if ($imgCandidate && filter_var($imgCandidate, FILTER_VALIDATE_URL)) {
                                        $foundImage = $imgCandidate;
                                        $selectedItem = $item;
                                        break;
                                    }

                                    // Fresh item but no image — keep as fallback item
                                    if (!$selectedItem) {
                                        $selectedItem = $item;
                                    }
                                }

                                // Only proceed if a fresh item was found
                                if ($selectedItem) {
                                    $item = $selectedItem;
                                    $sourceHeadline = mb_convert_encoding((string)$item->title, 'UTF-8', 'UTF-8');
                                    $contentNs = $item->children('http://purl.org/rss/1.0/modules/content/');
                                    $encodedContent = $contentNs && isset($contentNs->encoded) ? (string)$contentNs->encoded : '';
                                    $sourceContentText = mb_convert_encoding(strip_tags((string)$item->description . ' ' . $encodedContent), 'UTF-8', 'UTF-8');
                                    $sourceContentText = mb_substr(trim($sourceContentText), 0, 3000, 'UTF-8');

                                    if ($foundImage) {
                                        $sourceImageUrl = $foundImage;
                                    }
                                }
                                // If $selectedItem is null → all items were older than 24h
                                // $sourceHeadline and $sourceContentText stay null
                                // Gemini will generate a fresh story without source reference
                            }
                        }
                    } catch (\Exception $rssEx) {
                        // ignore and fallback
                    }
                } elseif ($source->type === 'facebook') {
                    $fbEnabled = Setting::where('key', 'facebook_enabled')->value('value') ?? '0';
                    $pageToken = Setting::where('key', 'facebook_page_access_token')->value('value');

                    if ($fbEnabled === '1' && $pageToken) {
                        try {
                            $fbPageId = $source->url;
                            $fbResponse = Http::timeout(10)->get("https://graph.facebook.com/v18.0/{$fbPageId}/feed", [
                                'fields' => 'message,attachments,created_time',
                                'limit' => 5,
                                'access_token' => $pageToken
                            ]);

                            if ($fbResponse->successful()) {
                                $feedData = $fbResponse->json();
                                if (!empty($feedData['data'])) {
                                    foreach ($feedData['data'] as $post) {
                                        if (!empty($post['message'])) {
                                            $sourceContentText = mb_convert_encoding($post['message'], 'UTF-8', 'UTF-8');
                                            $lines = explode("\n", $sourceContentText);
                                            $sourceHeadline = trim($lines[0]);
                                            
                                            if (!empty($post['attachments']['data'][0])) {
                                                $attachment = $post['attachments']['data'][0];
                                                if (isset($attachment['media']['image']['src'])) {
                                                    $sourceImageUrl = $attachment['media']['image']['src'];
                                                }
                                            }
                                            break;
                                        }
                                    }
                                }
                            }
                        } catch (\Exception $fbEx) {
                            // ignore and fallback
                        }
                    }
                } elseif ($source->type === 'scraping') {
                    try {
                        $scrapResponse = Http::timeout(10)->get($source->url);
                        if ($scrapResponse->successful()) {
                            $html = $scrapResponse->body();
                            if (preg_match('/<title>(.*?)<\/title>/si', $html, $matches)) {
                                $sourceHeadline = mb_convert_encoding(trim($matches[1]), 'UTF-8', 'UTF-8');
                            }
                            $cleanHtml = preg_replace('/<(script|style)\b[^>]*>(.*?)<\/\1>/is', '', $html);
                            $sourceContentText = trim(strip_tags($cleanHtml));
                            $sourceContentText = preg_replace('/\s+/', ' ', $sourceContentText);
                            $sourceContentText = mb_convert_encoding($sourceContentText, 'UTF-8', 'UTF-8');
                            $sourceContentText = mb_substr($sourceContentText, 0, 3000, 'UTF-8');
                        }
                    } catch (\Exception $scrapEx) {
                        // ignore and fallback
                    }
                }
            }

            // Duplicate Prevention (Fetch last 15 article titles)
            $recentArticles = Article::orderBy('id', 'desc')->take(15)->get();
            $recentTitles = $recentArticles->pluck('title')->toArray();
            $recentTitlesList = "";
            foreach ($recentTitles as $i => $title) {
                $recentTitlesList .= ($i + 1) . ". " . $title . "\n";
            }

            // Map of 64 districts in Bangladesh to their 8 divisions (Bengali)
            $districtToDivision = [
                'ঢাকা' => 'ঢাকা', 'গাজীপুর' => 'ঢাকা', 'নারায়ণগঞ্জ' => 'ঢাকা', 'রাজবাড়ী' => 'ঢাকা', 'ফরিদপুর' => 'ঢাকা', 'গোপালগঞ্জ' => 'ঢাকা', 'মাদারীপুর' => 'ঢাকা', 'মানিকগঞ্জ' => 'ঢাকা', 'মুন্সিগঞ্জ' => 'ঢাকা', 'নরসিংদী' => 'ঢাকা', 'শরীয়তপুর' => 'ঢাকা', 'টাঙ্গাইল' => 'ঢাকা', 'কিশোরগঞ্জ' => 'ঢাকা',
                'চট্টগ্রাম' => 'চট্টগ্রাম', 'কক্সবাজার' => 'চট্টগ্রাম', 'কুমিল্লা' => 'চট্টগ্রাম', 'ফেনী' => 'চট্টগ্রাম', 'ব্রাহ্মণবাড়িয়া' => 'চট্টগ্রাম', 'রাঙ্গামাটি' => 'চট্টগ্রাম', 'বান্দরবান' => 'চট্টগ্রাম', 'খাগড়াছড়ি' => 'চট্টগ্রাম', 'নোয়াখালী' => 'চট্টগ্রাম', 'লক্ষ্মীপুর' => 'চট্টগ্রাম', 'চাঁদপুর' => 'চট্টগ্রাম',
                'রাজশাহী' => 'রাজশাহী', 'বগুড়া' => 'রাজশাহী', 'পাবনা' => 'রাজশাহী', 'সিরাজগঞ্জ' => 'রাজশাহী', 'নওগাঁ' => 'রাজশাহী', 'নাটোর' => 'রাজশাহী', 'জয়পুরহাট' => 'রাজশাহী', 'চাঁপাইনবাবগঞ্জ' => 'রাজশাহী',
                'খুলনা' => 'খুলনা', 'যশোর' => 'খুলনা', 'সাতক্ষীরা' => 'খুলনা', 'বাগেরহাট' => 'খুলনা', 'কুষ্টিয়া' => 'খুলনা', 'মেহেরপুর' => 'খুলনা', 'চুয়াডাঙ্গা' => 'খুলনা', 'ঝিনাইদহ' => 'খুলনা', 'মাগুরা' => 'খুলনা', 'নড়াইল' => 'খুলনা',
                'বরিশাল' => 'বরিশাল', 'পটুয়াখালী' => 'বরিশাল', 'ভোলা' => 'বরিশাল', 'পিরোজপুর' => 'বরিশাল', 'বরগুনা' => 'বরিশাল', 'ঝালকাঠি' => 'বরিশাল',
                'সিলেট' => 'সিলেট', 'মৌলভীবাজার' => 'সিলেট', 'হবিগঞ্জ' => 'সিলেট', 'সুনামগঞ্জ' => 'সিলেট',
                'রংপুর' => 'রংপুর', 'দিনাজপুর' => 'রংপুর', 'কুড়িগ্রাম' => 'রংপুর', 'গাইবান্ধা' => 'রংপুর', 'নীলফামারী' => 'রংপুর', 'লালমনিরহাট' => 'রংপুর', 'পঞ্চগড়' => 'রংপুর', 'ঠাকুরগাঁও' => 'রংপুর',
                'ময়মনসিংহ' => 'ময়মনসিংহ', 'নেত্রকোণা' => 'ময়মনসিংহ', 'শেরপুর' => 'ময়মনসিংহ', 'জামালপুর' => 'ময়মনসিংহ'
            ];

            // Build dynamic prompt
            $prompt = "Generate a short hot news article about Bangladesh. The news MUST belong to the category: \"{$categoryName}\".\n";
            
            if ($sourceHeadline && $sourceContentText) {
                $prompt .= "Ground the news article using the following source information from \"{$sourceName}\" (translate, rewrite, and format it, making it fresh, engaging, and unique):\n";
                $prompt .= "Source Headline: {$sourceHeadline}\n";
                $prompt .= "Source Content:\n" . mb_substr($sourceContentText, 0, 2000, 'UTF-8') . "\n\n";
                $prompt .= "CRITICAL: If the source news content DOES NOT fit/belong to the category \"{$categoryName}\", you MUST ignore the source news entirely and instead generate a fresh, unique news story about Bangladesh that belongs to the category \"{$categoryName}\".\n";
            }

            if (!empty($recentTitles)) {
                $prompt .= "CRITICAL: Do NOT cover news/events that are similar to the following recent articles (avoid the same news stories or duplicating their angles):\n{$recentTitlesList}\n";
            }
            
            $prompt .= 'Return ONLY a valid JSON object with the structure below. Do not wrap the response in ```json markdown code blocks.
{
  "title": "Headline in Bengali",
  "category": "Must be: ' . $categoryName . '",
  "content": "2-3 paragraphs of news content in Bengali formatted in HTML (use <p> tags)",
  "source_matched": true or false (set to true if you used the provided source news because it belongs to the category "' . $categoryName . '", or false if you ignored the source news because it did not belong to "' . $categoryName . '" and generated a fresh story instead),
  "district": "Name of the district in Bengali (e.g., ঢাকা, রাজবাড়ী, সিলেট, চট্টগ্রাম) if the news is specific to a location/district in Bangladesh, otherwise null or empty string",
  "division": "Name of the division in Bengali (e.g., ঢাকা, চট্টগ্রাম, সিলেট) if the news is specific to a location/division in Bangladesh, otherwise null or empty string",
  "image_prompt": "A highly detailed English prompt for generating an ultra-realistic, authentic journalistic news photograph of the news event. The prompt must specify professional editorial photography, DSLR camera, hyper-realistic, vivid colors, believable real-world news scene, and NO text in the image. E.g., A photorealistic news photograph of..."
}';

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=" . $apiKey, [
                'contents' => [
                    [
                        'parts' => [['text' => $prompt]]
                    ]
                ]
            ]);

            $data = $response->json();

            if ($response->successful() && isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                $jsonText = trim($data['candidates'][0]['content']['parts'][0]['text']);
                
                // Remove markdown code block if present
                $jsonText = str_replace(['```json', '```'], '', $jsonText);
                
                $newsData = json_decode(trim($jsonText), true);

                if (!$newsData || !isset($newsData['title'])) {
                    return response()->json(['success' => false, 'message' => 'Invalid JSON returned from AI.', 'raw' => $jsonText]);
                }

                // =============================================
                // IMAGE ENGINE (3-MODE: real / auto / animation)
                // =============================================
                $imageMode = Setting::where('key', 'image_mode')->value('value') ?? 'real';
                $imageUrl   = null;
                $imageData  = null;
                $extension  = 'jpg';

                // ── Helper: Download image bytes from a URL ──────────────────
                $downloadImage = function (string $url): ?array {
                    try {
                        $resp = Http::timeout(20)
                            ->withHeaders([
                                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
                                'Accept'     => 'image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
                                'Referer'    => 'https://www.prothomalo.com/',
                            ])
                            ->get($url);

                        if (!$resp->successful()) return null;

                        $body = $resp->body();
                        if (strlen($body) < 5120) return null; // reject tiny/placeholder images

                        $ct  = $resp->header('Content-Type') ?? '';
                        $ext = 'jpg';
                        if (str_contains($ct, 'image/png'))  $ext = 'png';
                        elseif (str_contains($ct, 'image/webp')) $ext = 'webp';
                        elseif (str_contains($url, '.png'))  $ext = 'png';
                        elseif (str_contains($url, '.webp')) $ext = 'webp';

                        return ['data' => $body, 'ext' => $ext];
                    } catch (\Throwable $e) {
                        return null;
                    }
                };

                // ── Helper: Probe source URL accessibility (fast HEAD) ───────
                $probeSource = function (string $url): bool {
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
                };

                // ── Helper: Apply GD crop + logo watermark ───────────────────
                $processGd = function (string $rawData, string $ext): string {
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

                        $cropped = imagecreatetruecolor($cw, $ch);
                        imagealphablending($cropped, false);
                        imagesavealpha($cropped, true);
                        imagecopyresampled($cropped, $gdImg, 0, 0, $cx, $cy, $cw, $ch, $cw, $ch);

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
                                        imagecopy($cropped, $scaled, $cw - $newLw - $pad, $pad, 0, 0, $newLw, $newLh);
                                        imagecopy($cropped, $scaled, $pad, $ch - $newLh - $pad, 0, 0, $newLw, $newLh);
                                        imagecopy($cropped, $scaled, $cw - $newLw - $pad, $ch - $newLh - $pad, 0, 0, $newLw, $newLh);
                                        imagedestroy($logoGd);
                                        imagedestroy($scaled);
                                    }
                                }
                            }
                        }

                        ob_start();
                        match ($ext) {
                            'png'  => imagepng($cropped),
                            'webp' => imagewebp($cropped),
                            default => imagejpeg($cropped, null, 90),
                        };
                        $out = ob_get_clean();
                        imagedestroy($gdImg);
                        imagedestroy($cropped);
                        return $out ?: $rawData;
                    } catch (\Throwable $e) {
                        return $rawData;
                    }
                };

                // ── Helper: Save image bytes to /storage/articles/ ───────────
                $saveImage = function (string $rawData, string $ext) use (&$imageUrl): void {
                    $filename = 'article_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    if (!\Illuminate\Support\Facades\Storage::disk('public')->exists('articles')) {
                        \Illuminate\Support\Facades\Storage::disk('public')->makeDirectory('articles');
                    }
                    \Illuminate\Support\Facades\Storage::disk('public')->put('articles/' . $filename, $rawData);
                    $imageUrl = '/storage/articles/' . $filename;
                };

                // ── Helper: Generate Image using Gemini API (nano-banana-pro-preview) ─
                $generateGeminiImage = function (string $prompt) use ($apiKey): ?array {
                    try {
                        $url = 'https://generativelanguage.googleapis.com/v1beta/models/nano-banana-pro-preview:generateContent?key=' . $apiKey;
                        $payload = [
                            'contents' => [
                                [
                                    'parts' => [
                                        ['text' => 'Generate a highly detailed and realistic news photo or professional illustration for this topic: ' . $prompt]
                                    ]
                                ]
                            ]
                        ];
                        
                        $resp = Http::timeout(45)->post($url, $payload);
                        
                        if ($resp->successful()) {
                            $data = $resp->json();
                            if (isset($data['candidates'][0]['content']['parts'][0]['inlineData'])) {
                                $inlineData = $data['candidates'][0]['content']['parts'][0]['inlineData'];
                                $base64 = $inlineData['data'];
                                $ext = 'jpg';
                                $mime = $inlineData['mimeType'] ?? 'image/jpeg';
                                if (str_contains($mime, 'png')) $ext = 'png';
                                elseif (str_contains($mime, 'webp')) $ext = 'webp';
                                
                                $decoded = base64_decode($base64);
                                if ($decoded !== false && strlen($decoded) > 1000) {
                                    return ['data' => $decoded, 'ext' => $ext];
                                }
                            }
                        }
                    } catch (\Throwable $e) {
                        return null;
                    }
                    return null;
                };

                // ── STEP 1: Determine target image based on mode ─────────────
                $sourceMatched = !isset($newsData['source_matched']) || $newsData['source_matched'] !== false;
                $realImgResult  = null;
                $aiImgResult    = null;

                if ($imageMode !== 'animation') {
                    // Try real source image
                    if ($sourceMatched && !empty($sourceImageUrl) && filter_var($sourceImageUrl, FILTER_VALIDATE_URL)) {
                        if ($probeSource($sourceImageUrl)) {
                            $realImgResult = $downloadImage($sourceImageUrl);
                        }

                        // If HEAD probe failed or download was too small, try direct GET anyway
                        if (!$realImgResult) {
                            $realImgResult = $downloadImage($sourceImageUrl);
                        }
                    }
                }

                // ── STEP 2: Apply mode decision ──────────────────────────────
                if ($imageMode === 'real') {
                    // REAL only — use source or skip (no AI fallback)
                    if ($realImgResult) {
                        $processed = $processGd($realImgResult['data'], $realImgResult['ext']);
                        $saveImage($processed, $realImgResult['ext']);
                    }
                    // If still null, article saves with no image (acceptable for real mode)

                } elseif ($imageMode === 'auto') {
                    // AUTO — real first, AI Advanced Image as fallback
                    if ($realImgResult) {
                        $processed = $processGd($realImgResult['data'], $realImgResult['ext']);
                        $saveImage($processed, $realImgResult['ext']);
                    } else {
                        // Fallback: AI Image using Gemini Nano Banana Pro
                        $imagePrompt = $newsData['image_prompt'] ?? 'news event in bangladesh flat design vector art';
                        $aiImgResult = $generateGeminiImage($imagePrompt);
                        if ($aiImgResult) {
                            $processed = $processGd($aiImgResult['data'], $aiImgResult['ext']);
                            $saveImage($processed, $aiImgResult['ext']);
                        }
                    }

                } elseif ($imageMode === 'animation') {
                    // ANIMATION only — always AI Image using Gemini Nano Banana Pro
                    $imagePrompt = $newsData['image_prompt'] ?? 'news event in bangladesh flat design vector art';
                    $aiImgResult = $generateGeminiImage($imagePrompt);
                    if ($aiImgResult) {
                        $processed = $processGd($aiImgResult['data'], $aiImgResult['ext']);
                        $saveImage($processed, $aiImgResult['ext']);
                    }
                }
                // =============================================
                // END IMAGE ENGINE
                // =============================================


                // Create Article
                $article = new Article();
                $article->title = mb_convert_encoding($newsData['title'], 'UTF-8', 'UTF-8');
                $article->slug = Str::slug($article->title) . '-' . time();
                if (empty(trim(Str::slug($article->title)))) {
                    $article->slug = 'news-' . time() . '-' . rand(100,999);
                }
                $article->content = mb_convert_encoding($newsData['content'], 'UTF-8', 'UTF-8');
                $article->category_id = $targetCategoryId ?: 1;
                $article->user_id = auth()->id() ?? 1;
                $article->image_url = $imageUrl;
                $article->status = 'published';

                // Source Name Tracking
                $sourceMatched2 = !isset($newsData['source_matched']) || $newsData['source_matched'] !== false;
                if ($sourceName && $sourceMatched2) {
                    $article->source_name = $sourceName;
                } elseif ($sourceName && !$sourceMatched2) {
                    $article->source_name = 'AI Generated'; // source existed but category didn't match
                } else {
                    $article->source_name = 'AI Generated'; // no source configured
                }

                // Optional Location Tagging
                $district = !empty($newsData['district']) ? trim($newsData['district']) : null;
                $division = !empty($newsData['division']) ? trim($newsData['division']) : null;

                if ($district) {
                    $article->district = $district;
                    if (empty($division) && isset($districtToDivision[$district])) {
                        $article->division = $districtToDivision[$district];
                    } else {
                        $article->division = $division;
                    }
                } else {
                    $article->district = null;
                    $article->division = $division;
                }

                $article->save();

                // Reload relation
                $article->load('category');

                return response()->json(['success' => true, 'message' => 'Article auto-generated successfully!', 'article' => $article]);
            }

            return response()->json(['success' => false, 'message' => 'Failed to generate from Gemini.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
