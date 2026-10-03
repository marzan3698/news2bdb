<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Models\VideoWorkshopItem;
use App\Services\VideoWorkshopService;
use App\Services\YouTubeNewsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VideoWorkshopController extends Controller
{
    protected VideoWorkshopService $workshopService;
    protected YouTubeNewsService $youtubeNewsService;

    public function __construct(VideoWorkshopService $workshopService, YouTubeNewsService $youtubeNewsService)
    {
        $this->workshopService = $workshopService;
        $this->youtubeNewsService = $youtubeNewsService;
    }

    /**
     * Display Video Workshop Dashboard & Studio
     */
    public function index(Request $request)
    {
        $categories = Category::all();
        
        $query = VideoWorkshopItem::with(['user', 'article'])->latest();
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        $videos = $query->paginate(15);

        // Summary counts
        $totalCount = VideoWorkshopItem::count();
        $completedCount = VideoWorkshopItem::where('status', 'completed')->count();
        $facebookCount = VideoWorkshopItem::where('facebook_status', 'posted')->count();
        $processingCount = VideoWorkshopItem::whereIn('status', ['downloading', 'processing'])->count();

        // Defaults from settings
        $defaultTrimStart = (int)(Setting::where('key', 'video_workshop_trim_start')->value('value') ?? 5);
        $defaultTrimEnd = (int)(Setting::where('key', 'video_workshop_trim_end')->value('value') ?? 10);
        $defaultBrandingType = Setting::where('key', 'video_workshop_branding_type')->value('value') ?? 'both';
        $defaultAutoFb = Setting::where('key', 'video_workshop_auto_fb')->value('value') === '1';

        // Fetch top recent YouTube news bulletins for quick 1-click import
        $liveNewsBulletins = [];
        try {
            $liveNewsBulletins = $this->youtubeNewsService->fetchTopBangladeshiNews(8);
        } catch (\Throwable $e) {
            // Silently fallback if network issue
        }

        return view('admin.video-workshop.index', compact(
            'videos',
            'categories',
            'totalCount',
            'completedCount',
            'facebookCount',
            'processingCount',
            'defaultTrimStart',
            'defaultTrimEnd',
            'defaultBrandingType',
            'defaultAutoFb',
            'liveNewsBulletins'
        ));
    }

    /**
     * AJAX endpoint to fetch YouTube video info (Title, Thumbnail, Duration)
     */
    public function fetchInfo(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
        ]);

        $meta = $this->workshopService->fetchYouTubeMetadata($request->url);

        $minutes = floor($meta['duration'] / 60);
        $seconds = $meta['duration'] % 60;
        $meta['formatted_duration'] = sprintf('%02d:%02d', $minutes, $seconds);

        return response()->json([
            'success' => true,
            'data'    => $meta,
        ]);
    }

    /**
     * Store a new video workshop task and optionally process it immediately.
     */
    public function store(Request $request)
    {
        $request->validate([
            'source_url'          => 'required|url',
            'title'               => 'required|string|max:255',
            'category'            => 'nullable|string|max:100',
            'trim_start'          => 'required|integer|min:0|max:300',
            'trim_end'            => 'required|integer|min:0|max:300',
            'branding_type'       => 'required|in:watermark,banner,both,none',
            'watermark_position'  => 'required|in:top_right,top_left,bottom_right,bottom_left',
            'branding_text'       => 'nullable|string|max:255',
            'auto_post_facebook'  => 'nullable|boolean',
            'duration_seconds'    => 'nullable|integer',
            'thumbnail_url'       => 'nullable|string',
            'channel_name'        => 'nullable|string',
        ]);

        $videoId = $this->workshopService->extractYouTubeId($request->source_url);

        $item = VideoWorkshopItem::create([
            'title'               => $request->title,
            'category'            => $request->category ?: 'জাতীয়',
            'source_type'         => 'youtube',
            'source_url'          => $request->source_url,
            'youtube_video_id'    => $videoId,
            'channel_name'        => $request->channel_name ?: 'YouTube',
            'thumbnail_url'       => $request->thumbnail_url ?: ($videoId ? "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg" : null),
            'duration_seconds'    => $request->duration_seconds ?: 120,
            'trim_start'          => $request->trim_start,
            'trim_end'            => $request->trim_end,
            'branding_type'       => $request->branding_type,
            'watermark_position'  => $request->watermark_position,
            'branding_text'       => $request->branding_text ?: 'BDB NEWS • সত্যের সন্ধানে সার্বক্ষণিক',
            'auto_post_facebook'  => $request->boolean('auto_post_facebook'),
            'status'              => 'pending',
            'status_message'      => 'টাস্ক প্রস্তুত করা হয়েছে',
            'created_by'          => auth()->id(),
        ]);

        if ($request->boolean('run_immediately', true)) {
            // Execute download & process
            $res = $this->workshopService->runFullWorkflow($item);

            if ($request->ajax()) {
                return response()->json([
                    'success' => $res['success'],
                    'message' => $res['message'] ?? $item->status_message,
                    'item'    => $item->fresh(),
                ]);
            }

            if ($res['success']) {
                return redirect()->route('admin.video-workshop.index')
                    ->with('success', 'ভিডিও সফলভাবে ডাউনলোড, ট্রিম ও বিডিবি নিউজ ব্র্যান্ডিং সম্পন্ন হয়েছে!');
            } else {
                return redirect()->route('admin.video-workshop.index')
                    ->with('warning', 'ভিডিও টাস্ক সংরক্ষিত হয়েছে, কিন্তু প্রসেসিংয়ে সমস্যা হয়েছে: ' . ($res['message'] ?? ''));
            }
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'ভিডিও টাস্ক সফলভাবে তৈরি হয়েছে।',
                'item'    => $item,
            ]);
        }

        return redirect()->route('admin.video-workshop.index')->with('success', 'ভিডিও টাস্ক সফলভাবে যুক্ত করা হয়েছে।');
    }

    /**
     * Re-run or run processing for a video item
     */
    public function process($id)
    {
        $item = VideoWorkshopItem::findOrFail($id);
        $res = $this->workshopService->runFullWorkflow($item);

        if (request()->ajax()) {
            return response()->json($res);
        }

        if ($res['success']) {
            return redirect()->back()->with('success', 'ভিডিও প্রসেসিং সফলভাবে সম্পন্ন হয়েছে!');
        } else {
            return redirect()->back()->with('error', 'প্রসেসিং ব্যর্থ: ' . ($res['message'] ?? ''));
        }
    }

    /**
     * Post a processed video directly to Facebook Page
     */
    public function publishFacebook($id)
    {
        $item = VideoWorkshopItem::findOrFail($id);
        $res = $this->workshopService->publishToFacebook($item);

        if (request()->ajax()) {
            return response()->json($res);
        }

        if ($res['success']) {
            return redirect()->back()->with('success', 'ভিডিওটি সফলভাবে ফেসবুকে আপলোড ও পোস্ট করা হয়েছে!');
        } else {
            return redirect()->back()->with('error', 'ফেসবুক আপলোড ব্যর্থ: ' . ($res['message'] ?? ''));
        }
    }

    /**
     * Create an article on the site for this video
     */
    public function createArticle($id)
    {
        $item = VideoWorkshopItem::findOrFail($id);
        $article = $this->workshopService->createArticleFromVideo($item);

        return redirect()->back()->with('success', "ভিডিওটি সাইটের নিউজ আর্টিকেলে প্রকাশিত হয়েছে! (আইডি #{$article->id})");
    }

    /**
     * Delete video workshop task and files
     */
    public function destroy($id)
    {
        $item = VideoWorkshopItem::findOrFail($id);

        if ($item->original_video_path && Storage::disk('public')->exists($item->original_video_path)) {
            Storage::disk('public')->delete($item->original_video_path);
        }

        if ($item->processed_video_path && Storage::disk('public')->exists($item->processed_video_path)) {
            Storage::disk('public')->delete($item->processed_video_path);
        }

        $item->delete();

        return redirect()->route('admin.video-workshop.index')->with('success', 'ভিডিও টাস্ক মুছে ফেলা হয়েছে।');
    }

    /**
     * Settings Page for Video Workshop
     */
    public function settings()
    {
        $ffmpeg_path = Setting::where('key', 'video_workshop_ffmpeg_path')->value('value') ?? '';
        $ytdlp_path = Setting::where('key', 'video_workshop_ytdlp_path')->value('value') ?? '';
        $trim_start = Setting::where('key', 'video_workshop_trim_start')->value('value') ?? '5';
        $trim_end = Setting::where('key', 'video_workshop_trim_end')->value('value') ?? '10';
        $branding_type = Setting::where('key', 'video_workshop_branding_type')->value('value') ?? 'both';
        $watermark_pos = Setting::where('key', 'video_workshop_watermark_pos')->value('value') ?? 'top_right';
        $branding_text = Setting::where('key', 'video_workshop_branding_text')->value('value') ?? 'BDB NEWS • সত্যের সন্ধানে সার্বক্ষণিক';
        $auto_fb = Setting::where('key', 'video_workshop_auto_fb')->value('value') ?? '0';

        $diagnostics = $this->workshopService->diagnoseEnvironment();

        return view('admin.video-workshop.settings', compact(
            'ffmpeg_path',
            'ytdlp_path',
            'trim_start',
            'trim_end',
            'branding_type',
            'watermark_pos',
            'branding_text',
            'auto_fb',
            'diagnostics'
        ));
    }

    /**
     * Save settings for Video Workshop
     */
    public function saveSettings(Request $request)
    {
        $request->validate([
            'video_workshop_ffmpeg_path'   => 'nullable|string',
            'video_workshop_ytdlp_path'    => 'nullable|string',
            'video_workshop_trim_start'    => 'required|integer|min:0|max:300',
            'video_workshop_trim_end'      => 'required|integer|min:0|max:300',
            'video_workshop_branding_type' => 'required|in:watermark,banner,both,none',
            'video_workshop_watermark_pos' => 'required|in:top_right,top_left,bottom_right,bottom_left',
            'video_workshop_branding_text' => 'nullable|string|max:255',
            'watermark_file'               => 'nullable|file|mimes:png,webp,svg|max:5120',
        ]);

        Setting::updateOrCreate(['key' => 'video_workshop_ffmpeg_path'], ['value' => $request->video_workshop_ffmpeg_path]);
        Setting::updateOrCreate(['key' => 'video_workshop_ytdlp_path'], ['value' => $request->video_workshop_ytdlp_path]);
        Setting::updateOrCreate(['key' => 'video_workshop_trim_start'], ['value' => $request->video_workshop_trim_start]);
        Setting::updateOrCreate(['key' => 'video_workshop_trim_end'], ['value' => $request->video_workshop_trim_end]);
        Setting::updateOrCreate(['key' => 'video_workshop_branding_type'], ['value' => $request->video_workshop_branding_type]);
        Setting::updateOrCreate(['key' => 'video_workshop_watermark_pos'], ['value' => $request->video_workshop_watermark_pos]);
        Setting::updateOrCreate(['key' => 'video_workshop_branding_text'], ['value' => $request->video_workshop_branding_text]);
        Setting::updateOrCreate(['key' => 'video_workshop_auto_fb'], ['value' => $request->has('video_workshop_auto_fb') ? '1' : '0']);

        // Handle custom watermark file upload
        if ($request->hasFile('watermark_file')) {
            $file = $request->file('watermark_file');
            $filename = 'watermark_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('images', $filename, 'public');
            Setting::updateOrCreate(['key' => 'video_workshop_watermark_path'], ['value' => 'storage/' . $path]);
        }

        return redirect()->back()->with('success', 'ভিডিও ওয়ার্কশপ সেটিংস সফলভাবে সংরক্ষিত হয়েছে।');
    }

    /**
     * AJAX diagnostic status test
     */
    public function diagnostics()
    {
        $data = $this->workshopService->diagnoseEnvironment();
        return response()->json($data);
    }
}
