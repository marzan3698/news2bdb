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

    /**
     * Web Cron endpoint for Native Auto News Generation (No n8n needed).
     * Hit via cPanel Cron (curl/wget) or external uptime ping.
     */
    public function autoGenerate(Request $request)
    {
        $key = $request->input('key');
        $cronSecret = Setting::where('key', 'cron_secret')->value('value');

        if (empty($cronSecret)) {
            $cronSecret = Setting::where('key', 'snews_cron_secret')->value('value');
        }

        if (empty($cronSecret) || $key !== $cronSecret) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized cron execution. Invalid secret key.'
            ], 401);
        }

        $schedulerEnabled = Setting::where('key', 'scheduler_enabled')->value('value') ?? '0';
        if ($schedulerEnabled !== '1') {
            return response()->json([
                'success' => false,
                'message' => 'Auto news posting is currently disabled in admin settings.'
            ]);
        }

        $count = min(max((int)($request->input('count', 1)), 1), 5);
        $service = new \App\Services\NewsGeneratorService();
        $results = [];

        for ($i = 0; $i < $count; $i++) {
            $res = $service->generate([], 1);
            $results[] = [
                'success' => $res['success'],
                'message' => $res['message'],
                'title'   => $res['article']->title ?? null,
                'category'=> $res['article']->category->name ?? null,
            ];
            if ($i < $count - 1) {
                sleep(2);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Auto news generation completed. Processed: {$count}",
            'results' => $results,
        ]);
    }
}

