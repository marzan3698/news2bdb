<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\ArticleController;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/news/{slug}', [HomeController::class, 'show'])->name('news.show');
Route::get('/category/{slug}', [HomeController::class, 'category'])->name('news.category');
Route::get('/location', [HomeController::class, 'location'])->name('news.location');

// Legal Pages
Route::get('/privacy-policy', [HomeController::class, 'privacy'])->name('privacy');
Route::get('/terms-of-service', [HomeController::class, 'terms'])->name('terms');

// n8n Webhook API Route (Exempt from CSRF in bootstrap/app.php)
Route::post('/api/n8n/generate', [\App\Http\Controllers\Api\N8nController::class, 'generate']);
Route::post('/api/n8n/video-callback', [\App\Http\Controllers\Admin\VideoNewsController::class, 'callback']);

// Cron Job API Routes for Native Auto News (No n8n needed)
Route::get('/cron/snews', [\App\Http\Controllers\CronController::class, 'snews'])->name('cron.snews');
Route::get('/cron/auto-generate', [\App\Http\Controllers\CronController::class, 'autoGenerate'])->name('cron.auto-generate');

Route::get('/dashboard', function () {
    if (auth()->user()->isAdmin()) {
        return redirect()->route('admin.dashboard');
    }
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\AdminController::class, 'index'])->name('dashboard');
    Route::get('/api/latest-logs', [App\Http\Controllers\AdminController::class, 'getLatestLogs'])->name('api.latest-logs');
    
    // AI Integration Settings
    Route::get('/settings/ai', [SettingController::class, 'aiSettings'])->name('settings.ai');
    Route::post('/settings/ai', [SettingController::class, 'saveAiSettings'])->name('settings.ai.save');
    Route::post('/settings/ai/test', [SettingController::class, 'testGemini'])->name('settings.ai.test');
    
    // General Settings
    Route::get('/settings/general', [SettingController::class, 'generalSettings'])->name('settings.general');
    Route::post('/settings/general', [SettingController::class, 'saveGeneralSettings'])->name('settings.general.save');
    
    // Native Auto Post Scheduler (Replaces n8n)
    Route::get('/settings/auto-scheduler', [SettingController::class, 'autoScheduler'])->name('settings.auto-scheduler');
    Route::post('/settings/auto-scheduler', [SettingController::class, 'saveAutoScheduler'])->name('settings.auto-scheduler.save');
    Route::post('/settings/auto-scheduler/run-now', [SettingController::class, 'runSchedulerNow'])->name('settings.auto-scheduler.run-now');
    
    // Direct Facebook Auto-Post (Native, no n8n)
    Route::get('/settings/facebook', [SettingController::class, 'facebookSettings'])->name('settings.facebook');
    Route::post('/settings/facebook', [SettingController::class, 'saveFacebookSettings'])->name('settings.facebook.save');
    Route::post('/settings/facebook/test', [SettingController::class, 'testFacebookConnection'])->name('settings.facebook.test');

    // Legacy n8n routes (Redirects to new native settings)
    Route::get('/settings/n8n-setup', [SettingController::class, 'n8nSetup'])->name('settings.n8n');
    Route::get('/settings/n8n-facebook', [SettingController::class, 'n8nFacebook'])->name('settings.n8n-facebook');
    Route::post('/settings/n8n-facebook', [SettingController::class, 'saveN8nFacebook'])->name('settings.n8n-facebook.save');
    
    // AI Video Setup
    Route::get('/settings/video-setup', [SettingController::class, 'videoSetup'])->name('settings.video-setup');
    Route::post('/settings/video-setup', [SettingController::class, 'saveVideoSetup'])->name('settings.video-setup.save');
    
    // AI Sources CRUD
    Route::resource('/settings/ai-sources', App\Http\Controllers\Admin\AiSourceController::class)->names('ai-sources');
    
    // Articles Management
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/data', [ArticleController::class, 'data'])->name('articles.data');
    Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy'])->name('articles.destroy');
    Route::post('/articles/auto-generate', [ArticleController::class, 'autoGenerate'])->name('articles.autoGenerate');
    Route::get('/news-engine', [ArticleController::class, 'newsEngine'])->name('news-engine');

    // Source to News Feature
    Route::prefix('source-to-news')->name('source-to-news.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\SourceToNewsController::class, 'index'])->name('index');
        Route::post('/toggle-status', [\App\Http\Controllers\Admin\SourceToNewsController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/snews', [\App\Http\Controllers\Admin\SourceToNewsController::class, 'snews'])->name('snews');
        Route::get('/schedule', [\App\Http\Controllers\Admin\SourceToNewsController::class, 'schedule'])->name('schedule');
        Route::post('/schedule', [\App\Http\Controllers\Admin\SourceToNewsController::class, 'saveSchedule'])->name('schedule.save');
        Route::post('/clone/fetch', [\App\Http\Controllers\Admin\SourceToNewsController::class, 'fetchRssNews'])->name('clone.fetch');
        Route::post('/clone/process', [\App\Http\Controllers\Admin\SourceToNewsController::class, 'processNewsItem'])->name('clone.process');
    });

    // AI Video News
    Route::prefix('video-news')->name('video-news.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\VideoNewsController::class, 'index'])->name('index');
        Route::get('/create', [\App\Http\Controllers\Admin\VideoNewsController::class, 'create'])->name('create');
        Route::post('/trigger', [\App\Http\Controllers\Admin\VideoNewsController::class, 'trigger'])->name('trigger');
    });

    // Viral & Trending News Engine
    Route::prefix('viral-news')->name('viral-news.')->group(function () {
        Route::get('/', [\App\Http\Controllers\Admin\ViralNewsController::class, 'index'])->name('index');
        Route::get('/data', [\App\Http\Controllers\Admin\ViralNewsController::class, 'data'])->name('data');
        Route::post('/generate', [\App\Http\Controllers\Admin\ViralNewsController::class, 'generate'])->name('generate');
        Route::post('/toggle-auto', [\App\Http\Controllers\Admin\ViralNewsController::class, 'toggleAuto'])->name('toggle-auto');
    });
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
