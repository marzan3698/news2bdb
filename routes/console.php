<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\Setting;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// AI News Auto-Generation Scheduler
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
// To activate, add this to your server's crontab:
//   * * * * * cd /path/to/bdbnews && php artisan schedule:run >> /dev/null 2>&1
// ━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

Schedule::call(function () {
    $schedulerEnabled = Setting::where('key', 'scheduler_enabled')->value('value') ?? '0';

    if ($schedulerEnabled !== '1') {
        return; // Scheduler is disabled from admin panel
    }

    $service = new \App\Services\NewsGeneratorService();
    $service->generate([], 1); // Generate 1 article, all categories rotating, user_id=1
})->cron(function () {
    // Read interval from DB settings (default: every 30 minutes)
    $interval = Setting::where('key', 'scheduler_interval')->value('value') ?? '30';

    return match ($interval) {
        '5'   => '*/5 * * * *',
        '10'  => '*/10 * * * *',
        '15'  => '*/15 * * * *',
        '30'  => '*/30 * * * *',
        '60'  => '0 * * * *',
        '120' => '0 */2 * * *',
        default => '*/30 * * * *',
    };
})->name('ai-news-generator')->withoutOverlapping(5);
