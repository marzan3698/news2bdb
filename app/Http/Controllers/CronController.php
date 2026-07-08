<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Setting;
use App\Models\Article;
use App\Http\Controllers\Admin\SourceToNewsController;
use Illuminate\Support\Facades\Log;

class CronController extends Controller
{
    /**
     * Run the scheduled Snews auto cloning.
     * This endpoint is meant to be hit by a server cron job.
     */
    public function snews(Request $request)
    {
        $key = $request->input('key');
        $validKey = Setting::where('key', 'snews_cron_secret')->value('value');

        if (empty($validKey) || $key !== $validKey) {
            return response()->json(['success' => false, 'message' => 'Unauthorized cron execution. Invalid key.'], 401);
        }

        $scheduleStatus = Setting::where('key', 'snews_schedule_status')->value('value');
        if ($scheduleStatus !== '1') {
            return response()->json(['success' => false, 'message' => 'Scheduled Snews is currently disabled.']);
        }

        $numToClone = (int) (Setting::where('key', 'snews_schedule_count')->value('value') ?? 4);

        $response = SourceToNewsController::getNewsItemsToClone($numToClone);

        if (!$response['success']) {
            return response()->json($response);
        }

        $items = $response['items'];
        $processed = 0;
        $failed = 0;

        foreach ($items as $item) {
            if (empty($item) || empty($item['url'])) {
                continue;
            }

            // Prevent concurrent duplicates
            if (Article::where('source_url', $item['url'])->exists()) {
                continue;
            }

            try {
                $service = new \App\Services\NewsGeneratorService();
                // User ID 1 for admin cron tasks, assuming admin is ID 1
                $adminUserId = 1; 
                $result = $service->generate([], $adminUserId, $item);

                if (isset($result['success']) && $result['success']) {
                    $processed++;
                } else {
                    $failed++;
                    Log::error("Cron snews failed for url {$item['url']}: " . ($result['message'] ?? 'Unknown error'));
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error("Cron snews exception for url {$item['url']}: " . $e->getMessage());
            }
        }

        return response()->json([
            'success' => true, 
            'message' => "Cron execution completed. Processed: $processed, Failed: $failed"
        ]);
    }
}
