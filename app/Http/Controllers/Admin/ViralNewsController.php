<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Setting;
use App\Services\ViralNewsService;
use Illuminate\Http\Request;

class ViralNewsController extends Controller
{
    protected ViralNewsService $viralService;

    public function __construct(ViralNewsService $viralService)
    {
        $this->viralService = $viralService;
    }

    /**
     * Display the Live Viral & Trending News Hub
     */
    public function index()
    {
        $categories = Category::all();
        $viralModeEnabled = Setting::where('key', 'viral_mode_enabled')->value('value') === '1';

        return view('admin.viral-news.index', compact('categories', 'viralModeEnabled'));
    }

    /**
     * Fetch real-time trending news items for the table (AJAX)
     */
    public function data(Request $request)
    {
        $forceRefresh = $request->boolean('refresh');
        $trends = $this->viralService->getLiveTrends($forceRefresh);

        $postedCount = count(array_filter($trends, fn($t) => $t['is_posted']));
        $hotCount = count(array_filter($trends, fn($t) => $t['is_hot']));
        $politicalCount = count(array_filter($trends, fn($t) => !empty($t['is_political'])));
        $ytCount = count(array_filter($trends, fn($t) => ($t['source_type'] ?? '') === 'YouTube News TV'));

        return response()->json([
            'success'         => true,
            'count'           => count($trends),
            'posted_count'    => $postedCount,
            'hot_count'       => $hotCount,
            'political_count' => $politicalCount,
            'yt_count'        => $ytCount,
            'data'            => $trends,
            'fetched_at'      => now()->format('h:i:s A'),
        ]);
    }

    /**
     * Generate an AI news article from a selected viral trend item (AJAX)
     */
    public function generate(Request $request)
    {
        $request->validate([
            'trend' => 'required|array',
            'trend.title' => 'required|string',
        ]);

        $trendData = $request->input('trend');
        $userId = auth()->id() ?? 1;

        $result = $this->viralService->generateFromTrend($trendData, $userId);

        return response()->json($result);
    }

    /**
     * Toggle whether the Auto Post Scheduler should prioritize viral trends
     */
    public function toggleAuto(Request $request)
    {
        $enabled = $request->boolean('enabled') ? '1' : '0';
        Setting::updateOrCreate(['key' => 'viral_mode_enabled'], ['value' => $enabled]);

        return response()->json([
            'success' => true,
            'enabled' => $enabled === '1',
            'message' => $enabled === '1' 
                ? 'স্বয়ংক্রিয় ভাইরাল মোড সক্রিয় করা হয়েছে (Auto Post Scheduler এখন ট্রেন্ডিং খবরকে প্রাধান্য দেবে)।' 
                : 'স্বয়ংক্রিয় ভাইরাল মোড নিষ্ক্রিয় করা হয়েছে।'
        ]);
    }
}
