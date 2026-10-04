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
                // 1. Direct Facebook Graph API posting (Native, no n8n required)
                try {
                    $fbService = new \App\Services\FacebookPublisherService();
                    if ($fbService->isConfigured()) {
                        $fbService->publish($article);
                        return;
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::error('Direct Facebook auto-post failed: ' . $e->getMessage());
                }

                // 2. Legacy fallback to n8n webhook if still configured
                $webhookUrl = Setting::where('key', 'n8n_facebook_webhook_url')->value('value');
                if ($webhookUrl) {
                    $tagsString = '';
                    if (is_array($article->tags) && count($article->tags) > 0) {
                        $tagsString = implode(' ', array_map(function($t) { return '#' . str_replace(' ', '', $t); }, $article->tags));
                    }

                    $imageUrl = $article->image_url;
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
                    } catch (\Throwable $e) {
                        \Illuminate\Support\Facades\Log::error('Failed to send legacy n8n Facebook webhook: ' . $e->getMessage());
                    }
                }
            }
        });
    }

    /**
     * Extract YouTube video ID if this article has an associated video.
     */
    public function getVideoIdAttribute(): ?string
    {
        // 1. Try to extract from content iframe src
        if (!empty($this->content)) {
            if (preg_match('/youtube\.com\/embed\/([a-zA-Z0-9_-]{11})/i', $this->content, $m)) {
                return $m[1];
            }
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i', $this->content, $m)) {
                return $m[1];
            }
        }

        // 2. Try to extract from source_url
        if (!empty($this->source_url)) {
            if (preg_match('/(?:youtube\.com\/(?:watch\?v=|embed\/|shorts\/)|youtu\.be\/)([a-zA-Z0-9_-]{11})/i', $this->source_url, $m)) {
                return $m[1];
            }
        }

        // 3. Try to extract from image_url (e.g., maxresdefault or hqdefault)
        if (!empty($this->image_url)) {
            if (preg_match('/i(?:[0-9])?\.ytimg\.com\/vi(?:_webp)?\/([a-zA-Z0-9_-]{11})/i', $this->image_url, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    /**
     * Check if article is a video news.
     */
    public function getIsVideoAttribute(): bool
    {
        return !empty($this->video_id) || str_contains($this->content ?? '', 'youtube.com/embed');
    }

    /**
     * Get YouTube embed URL.
     */
    public function getVideoEmbedUrlAttribute(): ?string
    {
        $id = $this->video_id;
        return $id ? "https://www.youtube.com/embed/{$id}" : null;
    }

    /**
     * Get a guaranteed valid image URL that works on both local server and production.
     */
    public function getSafeImageUrlAttribute(): string
    {
        $url = $this->image_url;
        if (!empty($url)) {
            if (preg_match('/(?:localhost(?:\/bdbnews\/public)?|127\.0\.0\.1:8000)\/(storage\/[^\s"\']+)/', $url, $m)) {
                return asset($m[1]);
            }
            if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
                return $url;
            }
            return asset(ltrim($url, '/'));
        }

        if ($this->video_id) {
            return "https://i.ytimg.com/vi/{$this->video_id}/maxresdefault.jpg";
        }

        return asset('images/lead_national.png');
    }
}
