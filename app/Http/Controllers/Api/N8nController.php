<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Setting;
use App\Services\NewsGeneratorService;
use App\Models\Category;

class N8nController extends Controller
{
    public function generate(Request $request)
    {
        // Check API Key
        $n8nApiKey = Setting::where('key', 'n8n_api_key')->value('value');
        $providedKey = $request->header('X-API-KEY') ?? $request->input('api_key');

        if (empty($n8nApiKey)) {
             return response()->json(['success' => false, 'message' => 'n8n API key is not configured in settings.'], 403);
        }

        if ($providedKey !== $n8nApiKey) {
            return response()->json(['success' => false, 'message' => 'Unauthorized. Invalid API Key.'], 401);
        }

        // Initialize News Generator
        $service = new NewsGeneratorService();
        
        // Optional: n8n can pass a specific category slug or ID
        $categoryIds = [];
        $catInput = $request->input('category');
        if ($catInput) {
            if (is_numeric($catInput)) {
                $categoryIds = [$catInput];
            } else {
                $cat = Category::where('slug', $catInput)->first();
                if ($cat) {
                    $categoryIds = [$cat->id];
                }
            }
        }

        // Generate Article (user_id = 1 for admin)
        $result = $service->generate($categoryIds, 1);

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
