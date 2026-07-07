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

// n8n Webhook API Route (Exempt from CSRF in bootstrap/app.php)
Route::post('/api/n8n/generate', [\App\Http\Controllers\Api\N8nController::class, 'generate']);

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
    
    // n8n Setup
    Route::get('/settings/n8n-setup', [SettingController::class, 'n8nSetup'])->name('settings.n8n');
    
    // n8n +Facebook Setup
    Route::get('/settings/n8n-facebook', [SettingController::class, 'n8nFacebook'])->name('settings.n8n-facebook');
    Route::post('/settings/n8n-facebook', [SettingController::class, 'saveN8nFacebook'])->name('settings.n8n-facebook.save');
    
    // AI Sources CRUD
    Route::resource('/settings/ai-sources', App\Http\Controllers\Admin\AiSourceController::class)->names('ai-sources');
    
    // Articles Management
    Route::get('/articles', [ArticleController::class, 'index'])->name('articles.index');
    Route::get('/articles/data', [ArticleController::class, 'data'])->name('articles.data');
    Route::get('/articles/create', [ArticleController::class, 'create'])->name('articles.create');
    Route::post('/articles', [ArticleController::class, 'store'])->name('articles.store');
    Route::delete('/articles/{id}', [ArticleController::class, 'destroy'])->name('articles.destroy');
    Route::post('/articles/auto-generate', [ArticleController::class, 'autoGenerate'])->name('articles.autoGenerate');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
