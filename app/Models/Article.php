<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = [
        'title',
        'slug',
        'summary',
        'content',
        'image_url',
        'category_id',
        'user_id',
        'views',
        'is_featured',
        'status',
        'division',
        'district',
        'source_name',
        'content_hash',
        'source_url',
        'meta_description',
        'tags',
    ];

    protected $casts = [
        'tags' => 'array',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::created(function ($article) {
            if ($article->status === 'published') {
                $webhookUrl = Setting::where('key', 'n8n_facebook_webhook_url')->value('value');
                if ($webhookUrl) {
                    $tagsString = '';
                    if (is_array($article->tags) && count($article->tags) > 0) {
                        $tagsString = implode(' ', array_map(function($t) { return '#' . str_replace(' ', '', $t); }, $article->tags));
                    }

                    $imageUrl = $article->image_url;
                    // If image is relative, make it absolute
                    if ($imageUrl && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $imageUrl = url($imageUrl);
                    }

                    try {
                        \Illuminate\Support\Facades\Http::post($webhookUrl, [
                            'title' => $article->title,
                            'subtitle' => $article->summary,
                            'url' => route('news.show', $article->slug),
                            'image' => $imageUrl,
                            'tags' => $tagsString,
                        ]);
                    } catch (\Exception $e) {
                        // Silently fail or log it. We don't want to break the article creation if webhook fails.
                        \Illuminate\Support\Facades\Log::error('Failed to send n8n Facebook webhook: ' . $e->getMessage());
                    }
                }
            }
        });
    }
}
