<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\VideoNews;
use App\Models\Setting;
use App\Models\Article;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoNewsController extends Controller
{
    public function index()
    {
        $videos = VideoNews::latest()->get();
        return view('admin.video-news.index', compact('videos'));
    }

    public function create()
    {
        return view('admin.video-news.create');
    }

    public function trigger(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
        ]);

        $n8n_webhook_url = Setting::where('key', 'n8n_video_webhook_url')->value('value');
        
        if (!$n8n_webhook_url) {
            return back()->with('error', 'n8n Video Webhook URL is not set in AI Video Setup.');
        }

        // Get past concepts to avoid duplicates
        $pastConcepts = VideoNews::where('category', $request->category)
            ->whereNotNull('concept_title')
            ->pluck('concept_title')
            ->toArray();

        // Create initial pending record
        $videoNews = VideoNews::create([
            'category' => $request->category,
            'status' => 'processing',
        ]);

        try {
            $response = Http::post($n8n_webhook_url, [
                'video_news_id' => $videoNews->id,
                'category' => $request->category,
                'exclude_topics' => $pastConcepts,
                'callback_url' => url('/api/n8n/video-callback')
            ]);

            if ($response->successful()) {
                // If n8n returns an execution ID, we can save it
                $data = $response->json();
                if (isset($data['execution_id'])) {
                    $videoNews->update(['n8n_execution_id' => $data['execution_id']]);
                }
                
                return redirect()->route('admin.video-news.index')->with('success', 'Video generation triggered successfully. It is processing in n8n.');
            } else {
                $videoNews->update(['status' => 'failed']);
                return back()->with('error', 'Failed to trigger n8n workflow. Response: ' . $response->body());
            }
        } catch (\Exception $e) {
            $videoNews->update(['status' => 'failed']);
            return back()->with('error', 'Exception triggering n8n: ' . $e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        // n8n will send back the video_news_id, concept_title, facebook_video_url, and maybe an image_url for the banner
        $request->validate([
            'video_news_id' => 'required|integer',
            'concept_title' => 'required|string',
            'facebook_video_url' => 'required|url',
            'banner_image_url' => 'nullable|url',
        ]);

        $videoNews = VideoNews::find($request->video_news_id);
        
        if (!$videoNews) {
            return response()->json(['success' => false, 'error' => 'VideoNews record not found.'], 404);
        }

        $videoNews->update([
            'concept_title' => $request->concept_title,
            'facebook_video_url' => $request->facebook_video_url,
            'status' => 'completed',
        ]);

        // Automatically create a news article on the site
        $article = new Article();
        $article->title = $request->concept_title;
        // Basic slug generation
        $article->slug = \Illuminate\Support\Str::slug($request->concept_title) . '-' . time();
        $article->category = $videoNews->category;
        
        // Embed the facebook video
        // Facebook video embed uses iframe
        $fbEmbed = '<div class="video-container" style="position:relative; padding-bottom:177.77%; height:0; overflow:hidden;"><iframe src="https://www.facebook.com/plugins/video.php?href='.urlencode($request->facebook_video_url).'&show_text=false&width=315" width="315" height="560" style="position:absolute; top:0; left:0; width:100%; height:100%; border:none; overflow:hidden" scrolling="no" frameborder="0" allowfullscreen="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" allowFullScreen="true"></iframe></div>';
        
        $article->content = "<p>নতুন ভিডিও প্রকাশ হয়েছে! নিচে ভিডিওটি দেখুন।</p><br>" . $fbEmbed;
        
        // Handle banner image
        if ($request->banner_image_url) {
            $article->image_url = $request->banner_image_url;
        } else {
            // Fallback generic image
            $article->image_url = 'https://image.pollinations.ai/prompt/'.urlencode($request->concept_title).'?width=800&height=450&nologo=true';
        }
        
        $article->source_name = 'AI Video News';
        $article->save();

        return response()->json(['success' => true, 'article_id' => $article->id]);
    }
}
