<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Setting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FacebookPublisherService
{
    protected string $pageAccessToken;
    protected string $pageId;
    protected bool $enabled;

    public function __construct()
    {
        $this->pageAccessToken = Setting::where('key', 'facebook_page_access_token')->value('value') ?? '';
        $this->pageId = Setting::where('key', 'facebook_page_id')->value('value') ?? 'me';
        if (empty($this->pageId)) {
            $this->pageId = 'me';
        }
        $this->enabled = (Setting::where('key', 'facebook_enabled')->value('value') ?? '0') === '1';
    }

    /**
     * Check if direct Facebook publishing is enabled and configured.
     */
    public function isConfigured(): bool
    {
        return $this->enabled && !empty($this->pageAccessToken);
    }

    /**
     * Publish an article directly to Facebook Page.
     *
     * @param Article $article
     * @return array ['success' => bool, 'message' => string, 'post_id' => ?string]
     */
    public function publish(Article $article): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Facebook publishing is disabled or page access token is missing.'
            ];
        }

        try {
            $message = $this->buildCaption($article);
            $imagePath = $this->resolveLocalImagePath($article->image_url);
            $targetEndpoint = "https://graph.facebook.com/v19.0/{$this->pageId}/photos";

            // If we have a local physical image file, upload it directly via multipart
            if ($imagePath && file_exists($imagePath)) {
                $response = Http::timeout(30)
                    ->asMultipart()
                    ->post($targetEndpoint, [
                        [
                            'name'     => 'source',
                            'contents' => fopen($imagePath, 'r'),
                            'filename' => basename($imagePath),
                        ],
                        [
                            'name'     => 'caption',
                            'contents' => $message,
                        ],
                        [
                            'name'     => 'access_token',
                            'contents' => $this->pageAccessToken,
                        ],
                    ]);
            } elseif (!empty($article->image_url) && filter_var($article->image_url, FILTER_VALIDATE_URL)) {
                // If image is a remote URL
                $response = Http::timeout(30)
                    ->post($targetEndpoint, [
                        'url'          => $article->image_url,
                        'caption'      => $message,
                        'access_token' => $this->pageAccessToken,
                    ]);
            } else {
                // No image found: post as standard text/link feed
                $feedEndpoint = "https://graph.facebook.com/v19.0/{$this->pageId}/feed";
                $response = Http::timeout(30)
                    ->post($feedEndpoint, [
                        'message'      => $message,
                        'link'         => route('news.show', $article->slug),
                        'access_token' => $this->pageAccessToken,
                    ]);
            }

            $data = $response->json();

            if ($response->successful()) {
                $postId = $data['id'] ?? $data['post_id'] ?? null;
                Log::info("Facebook Direct Post successful for Article #{$article->id}. Post ID: {$postId}");
                return [
                    'success' => true,
                    'message' => 'Successfully published to Facebook Page.',
                    'post_id' => $postId,
                ];
            } else {
                $errorMsg = $data['error']['message'] ?? $response->body();
                Log::error("Facebook Direct Post failed for Article #{$article->id}: {$errorMsg}");
                return [
                    'success' => false,
                    'message' => "Facebook API error: {$errorMsg}",
                    'post_id' => null,
                ];
            }
        } catch (\Throwable $e) {
            Log::error("Facebook Direct Post exception for Article #{$article->id}: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage(),
                'post_id' => null,
            ];
        }
    }

    /**
     * Test token and connection with Facebook Graph API.
     */
    public function testConnection(): array
    {
        if (empty($this->pageAccessToken)) {
            return [
                'success' => false,
                'message' => 'Page Access Token is empty. Please enter your Page Access Token first.'
            ];
        }

        try {
            $response = Http::timeout(15)
                ->get("https://graph.facebook.com/v19.0/me", [
                    'fields'       => 'id,name,category',
                    'access_token' => $this->pageAccessToken,
                ]);

            $data = $response->json();

            if ($response->successful() && isset($data['id'])) {
                return [
                    'success'   => true,
                    'page_id'   => $data['id'],
                    'page_name' => $data['name'] ?? 'Facebook Page',
                    'message'   => "Connected successfully to Page: {$data['name']} (ID: {$data['id']})",
                ];
            }

            return [
                'success' => false,
                'message' => $data['error']['message'] ?? 'Failed to verify token with Facebook.',
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Build standard post caption with Title, Summary, Link, and Tags.
     */
    protected function buildCaption(Article $article): string
    {
        $tagsString = '';
        if (is_array($article->tags) && count($article->tags) > 0) {
            $formattedTags = array_map(function ($t) {
                $clean = preg_replace('/[^\p{L}\p{N}_]+/u', '', $t);
                return !empty($clean) ? '#' . $clean : '';
            }, $article->tags);
            $tagsString = implode(' ', array_filter($formattedTags));
        }

        $captionParts = [];
        $captionParts[] = $article->title;

        if (!empty($article->summary)) {
            $captionParts[] = "\n" . $article->summary;
        }

        $articleUrl = route('news.show', $article->slug);
        $captionParts[] = "\nবিস্তারিত পড়ুন: " . $articleUrl;

        if (!empty($tagsString)) {
            $captionParts[] = "\n" . $tagsString;
        }

        return implode("\n", $captionParts);
    }

    /**
     * Resolve the physical path for a stored image.
     */
    protected function resolveLocalImagePath(?string $imageUrl): ?string
    {
        if (empty($imageUrl)) {
            return null;
        }

        // Check if starts with /storage/
        if (str_starts_with($imageUrl, '/storage/')) {
            $rel = str_replace('/storage/', '', $imageUrl);
            $fullPath = storage_path('app/public/' . $rel);
            if (file_exists($fullPath)) {
                return $fullPath;
            }
        }

        // Check in public folder directly
        $cleaned = ltrim($imageUrl, '/');
        $pubPath = public_path($cleaned);
        if (file_exists($pubPath)) {
            return $pubPath;
        }

        return null;
    }
}
