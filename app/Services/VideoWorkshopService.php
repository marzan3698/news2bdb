<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Category;
use App\Models\Setting;
use App\Models\VideoWorkshopItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoWorkshopService
{
    protected FacebookPublisherService $facebookService;

    public function __construct(FacebookPublisherService $facebookService)
    {
        $this->facebookService = $facebookService;
    }

    /**
     * Parse YouTube video ID from various URL formats.
     */
    public function extractYouTubeId(string $url): ?string
    {
        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/ ]{11})/i';
        if (preg_match($pattern, trim($url), $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Fetch video metadata from YouTube URL (Title, Thumbnail, Channel Name, Duration).
     */
    public function fetchYouTubeMetadata(string $url): array
    {
        $videoId = $this->extractYouTubeId($url);
        
        $metadata = [
            'video_id'     => $videoId,
            'title'        => 'ইউটিউব ভিডিও',
            'channel_name' => 'YouTube',
            'thumbnail_url'=> $videoId ? "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg" : null,
            'duration'     => 120, // default fallback 2 minutes
            'url'          => $url,
        ];

        // 1. Try YouTube oEmbed for accurate title and author
        try {
            $oembedUrl = "https://www.youtube.com/oembed?url=" . urlencode($url) . "&format=json";
            $res = Http::timeout(8)->get($oembedUrl);
            if ($res->successful()) {
                $data = $res->json();
                if (!empty($data['title'])) {
                    $metadata['title'] = $data['title'];
                }
                if (!empty($data['author_name'])) {
                    $metadata['channel_name'] = $data['author_name'];
                }
                if (!empty($data['thumbnail_url'])) {
                    $metadata['thumbnail_url'] = $data['thumbnail_url'];
                }
            }
        } catch (\Throwable $e) {
            Log::warning("YouTube oEmbed fetch failed: " . $e->getMessage());
        }

        // 2. Try yt-dlp dump-json if available for exact duration
        $ytdlp = $this->getYtDlpBinary();
        if ($ytdlp && $this->isBinaryExecutable($ytdlp)) {
            try {
                $cmd = escapeshellcmd($ytdlp) . " --dump-json --no-playlist " . escapeshellarg($url);
                $output = shell_exec($cmd);
                if ($output) {
                    $json = json_decode($output, true);
                    if (is_array($json)) {
                        if (!empty($json['title'])) {
                            $metadata['title'] = $json['title'];
                        }
                        if (!empty($json['uploader'])) {
                            $metadata['channel_name'] = $json['uploader'];
                        }
                        if (!empty($json['duration'])) {
                            $metadata['duration'] = (int)$json['duration'];
                        }
                        if (!empty($json['thumbnail'])) {
                            $metadata['thumbnail_url'] = $json['thumbnail'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("yt-dlp dump-json failed: " . $e->getMessage());
            }
        }

        return $metadata;
    }

    /**
     * Download YouTube video to storage.
     */
    public function downloadVideo(VideoWorkshopItem $item): array
    {
        $item->update([
            'status' => 'downloading',
            'status_message' => 'ইউটিউব থেকে ভিডিও ডাউনলোড হচ্ছে...',
            'error_message' => null,
        ]);

        $originalsDir = storage_path('app/public/videos/originals');
        if (!file_exists($originalsDir)) {
            mkdir($originalsDir, 0755, true);
        }

        $ytdlp = $this->getYtDlpBinary();
        if (!$ytdlp || !$this->isBinaryExecutable($ytdlp)) {
            $msg = 'yt-dlp পাওয়া যায়নি। অনুগ্রহ করে Settings থেকে yt-dlp বা Python পাথ সেট করুন।';
            $item->update([
                'status' => 'failed',
                'status_message' => 'ডাউনলোড ব্যর্থ',
                'error_message' => $msg,
            ]);
            return ['success' => false, 'message' => $msg];
        }

        $filename = 'yt_' . ($item->youtube_video_id ?: $item->id) . '_' . time() . '.mp4';
        $outputPath = $originalsDir . DIRECTORY_SEPARATOR . $filename;

        // Command to download video with best compatibility (MP4 format with AAC audio)
        // Format selector: best video (up to 1080p) + best audio merged into mp4
        $escapedYtdlp = $ytdlp;
        $escapedUrl = escapeshellarg($item->source_url);
        $escapedOutput = escapeshellarg($outputPath);

        $cmd = "{$escapedYtdlp} -f \"bestvideo[ext=mp4][height<=1080]+bestaudio[ext=m4a]/best[ext=mp4]/best\" --no-playlist --merge-output-format mp4 -o {$escapedOutput} {$escapedUrl} 2>&1";
        
        Log::info("Executing yt-dlp: " . $cmd);
        $output = shell_exec($cmd);

        if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
            // Fallback download attempt with simpler format selector
            $cmdFallback = "{$escapedYtdlp} -f \"best\" --no-playlist -o {$escapedOutput} {$escapedUrl} 2>&1";
            $output = shell_exec($cmdFallback);
        }

        if (file_exists($outputPath) && filesize($outputPath) > 10000) {
            $relPath = 'videos/originals/' . $filename;
            
            // Detect exact duration if not yet set
            $duration = $this->probeVideoDuration($outputPath) ?: $item->duration_seconds;

            $item->update([
                'original_video_path' => $relPath,
                'duration_seconds'    => $duration,
                'status'              => 'processing',
                'status_message'      => 'ভিডিও ডাউনলোড সম্পন্ন! এখন ব্র্যান্ডিং ও ট্রিম প্রস্তুত হচ্ছে...',
            ]);

            return ['success' => true, 'path' => $relPath, 'full_path' => $outputPath];
        }

        $errMsg = 'ভিডিও ডাউনলোড করা যায়নি: ' . Str::limit(trim($output ?? 'অজানা ত্রুটি'), 400);
        $item->update([
            'status'         => 'failed',
            'status_message' => 'ডাউনলোড ব্যর্থ',
            'error_message'  => $errMsg,
        ]);

        return ['success' => false, 'message' => $errMsg];
    }

    /**
     * Process video: Trim beginning & end, overlay BDB NEWS branding / watermark.
     */
    public function processVideo(VideoWorkshopItem $item): array
    {
        $item->update([
            'status' => 'processing',
            'status_message' => 'ভিডিও ট্রিম ও বিডিবি নিউজ ব্র্যান্ডিং যুক্ত করা হচ্ছে...',
            'error_message' => null,
        ]);

        $ffmpeg = $this->getFfmpegBinary();
        if (!$ffmpeg || !$this->isBinaryExecutable($ffmpeg)) {
            $msg = 'FFmpeg পাওয়া যায়নি। অনুগ্রহ করে Settings থেকে FFmpeg বাইনারি পাথ কনফিগার করুন।';
            $item->update([
                'status' => 'failed',
                'status_message' => 'প্রসেসিং ব্যর্থ',
                'error_message' => $msg,
            ]);
            return ['success' => false, 'message' => $msg];
        }

        if (empty($item->original_video_path)) {
            return ['success' => false, 'message' => 'মূল ভিডিও ফাইল পাওয়া যায়নি।'];
        }

        $inputPath = storage_path('app/public/' . $item->original_video_path);
        if (!file_exists($inputPath)) {
            $msg = "মূল ভিডিও ফাইল লোকেশনে বিদ্যমান নেই: {$inputPath}";
            $item->update([
                'status' => 'failed',
                'status_message' => 'ফাইল অনুপস্থিত',
                'error_message' => $msg,
            ]);
            return ['success' => false, 'message' => $msg];
        }

        $processedDir = storage_path('app/public/videos/processed');
        if (!file_exists($processedDir)) {
            mkdir($processedDir, 0755, true);
        }

        $outFilename = 'bdbnews_v_' . $item->id . '_' . time() . '.mp4';
        $outputPath = $processedDir . DIRECTORY_SEPARATOR . $outFilename;

        // Calculate trim timings
        $trimStart = max(0, (int)$item->trim_start);
        $trimEnd = max(0, (int)$item->trim_end);
        
        $totalDuration = $item->duration_seconds ?: $this->probeVideoDuration($inputPath);
        $durationArgs = "";
        
        if ($totalDuration && $totalDuration > ($trimStart + $trimEnd)) {
            $targetDuration = $totalDuration - $trimStart - $trimEnd;
            $durationArgs = "-t {$targetDuration}";
        }

        // Branding configuration
        $watermarkPath = $this->getWatermarkLogoPath();
        $hasWatermark = file_exists($watermarkPath);
        $brandingType = $item->branding_type ?: 'both'; // watermark, banner, both, none

        // Build FFmpeg complex filter
        $filterParts = [];
        $inputArgs = "-ss {$trimStart} -i " . escapeshellarg($inputPath);
        
        if ($hasWatermark && in_array($brandingType, ['watermark', 'both'])) {
            $inputArgs .= " -i " . escapeshellarg($watermarkPath);
            
            // Position coordinate
            $pos = $item->watermark_position ?: 'top_right';
            $overlayPos = match($pos) {
                'top_left'     => '25:25',
                'bottom_right' => 'W-w-25:H-h-25',
                'bottom_left'  => '25:H-h-25',
                default        => 'W-w-25:25', // top_right
            };

            if ($brandingType === 'both') {
                // Lower-third news strap + watermark
                $filterParts[] = "[0:v]drawbox=y=ih-55:color=black@0.65:width=iw:height=55:t=fill,drawbox=y=ih-58:color=red:width=iw:height=3:t=fill,drawtext=text='BDB NEWS • সত্যের সন্ধানে সার্বক্ষণিক':fontcolor=white:fontsize=22:x=25:y=h-38[v1];[v1][1:v]overlay={$overlayPos}[outv]";
            } else {
                // Watermark only
                $filterParts[] = "[0:v][1:v]overlay={$overlayPos}[outv]";
            }
        } elseif ($brandingType === 'banner' || $brandingType === 'both') {
            // Lower-third news banner only
            $filterParts[] = "[0:v]drawbox=y=ih-55:color=black@0.65:width=iw:height=55:t=fill,drawbox=y=ih-58:color=red:width=iw:height=3:t=fill,drawtext=text='BDB NEWS • সত্যের সন্ধানে সার্বক্ষণিক':fontcolor=white:fontsize=22:x=25:y=h-38[outv]";
        }

        $filterClause = "";
        $mapClause = "";
        if (!empty($filterParts)) {
            $filterClause = "-filter_complex \"" . implode(';', $filterParts) . "\"";
            $mapClause = "-map \"[outv]\" -map 0:a?";
        }

        // Fast video encoding parameters for web and Facebook compatibility
        $cmd = "{$ffmpeg} -y {$inputArgs} {$durationArgs} {$filterClause} {$mapClause} -c:v libx264 -preset fast -crf 23 -c:a aac -b:a 128k -movflags +faststart " . escapeshellarg($outputPath) . " 2>&1";

        Log::info("Executing FFmpeg Video Processing: " . $cmd);
        $output = shell_exec($cmd);

        if (file_exists($outputPath) && filesize($outputPath) > 10000) {
            $relProcessed = 'videos/processed/' . $outFilename;
            
            $item->update([
                'processed_video_path' => $relProcessed,
                'status'               => 'completed',
                'status_message'       => 'ভিডিও এডিটিং ও ব্র্যান্ডিং সফলভাবে সম্পন্ন হয়েছে!',
                'error_message'        => null,
            ]);

            // Auto-post to Facebook if requested
            if ($item->auto_post_facebook) {
                $this->publishToFacebook($item);
            }

            return [
                'success'   => true,
                'path'      => $relProcessed,
                'full_path' => $outputPath,
                'message'   => 'ভিডিও প্রসেসিং সফল হয়েছে!',
            ];
        }

        // If complex filter failed (e.g. missing font or overlay error), fallback to direct trim without filter
        Log::warning("FFmpeg filtered render failed, trying basic trim fallback: " . $output);
        $cmdSimple = "{$ffmpeg} -y -ss {$trimStart} -i " . escapeshellarg($inputPath) . " {$durationArgs} -c:v libx264 -preset fast -crf 23 -c:a aac -movflags +faststart " . escapeshellarg($outputPath) . " 2>&1";
        shell_exec($cmdSimple);

        if (file_exists($outputPath) && filesize($outputPath) > 10000) {
            $relProcessed = 'videos/processed/' . $outFilename;
            $item->update([
                'processed_video_path' => $relProcessed,
                'status'               => 'completed',
                'status_message'       => 'ভিডিও ট্রিম সম্পন্ন হয়েছে (ব্র্যান্ডিং ফিল্টার ব্যতিরেকে)।',
            ]);

            if ($item->auto_post_facebook) {
                $this->publishToFacebook($item);
            }

            return ['success' => true, 'path' => $relProcessed];
        }

        $errMsg = 'ভিডিও প্রসেসিং ব্যর্থ হয়েছে: ' . Str::limit(trim($output ?? 'FFmpeg ত্রুটি'), 400);
        $item->update([
            'status'         => 'failed',
            'status_message' => 'প্রসেসিং ব্যর্থ',
            'error_message'  => $errMsg,
        ]);

        return ['success' => false, 'message' => $errMsg];
    }

    /**
     * Run full workflow: Download + Process + (Auto Facebook Post).
     */
    public function runFullWorkflow(VideoWorkshopItem $item): array
    {
        // 1. Download
        $downloadRes = $this->downloadVideo($item);
        if (!$downloadRes['success']) {
            return $downloadRes;
        }

        // 2. Process
        $processRes = $this->processVideo($item);
        return $processRes;
    }

    /**
     * Publish processed video to Facebook Page.
     */
    public function publishToFacebook(VideoWorkshopItem $item): array
    {
        if (empty($item->processed_video_path)) {
            return [
                'success' => false,
                'message' => 'প্রসেস করা ভিডিও ফাইল এখনও প্রস্তুত নয়।'
            ];
        }

        $fullPath = storage_path('app/public/' . $item->processed_video_path);
        if (!file_exists($fullPath)) {
            return [
                'success' => false,
                'message' => 'ভিডিও ফাইলটি খুঁজে পাওয়া যায়নি: ' . $fullPath
            ];
        }

        $item->update([
            'facebook_status' => 'posting',
            'facebook_error'  => null,
        ]);

        // Build engaging Facebook post caption
        $siteUrl = url('/');
        $captionLines = [
            $item->title,
            "",
            "📌 বিডিবি নিউজ (BDB News) - সত্যের সন্ধানে সার্বক্ষণিক",
            "সর্বশেষ ব্রেকিং নিউজ এবং ভিডিও দেখতে ভিজিট করুন: " . $siteUrl,
            "",
            "#BDBNews #BangladeshNews #BreakingNews #VideoNews #{$item->category}"
        ];
        $description = implode("\n", $captionLines);

        $res = $this->facebookService->publishVideo($fullPath, $item->title, $description);

        if ($res['success']) {
            $item->update([
                'facebook_status'    => 'posted',
                'facebook_post_id'   => $res['video_id'],
                'facebook_video_url' => $res['video_url'],
                'facebook_posted_at' => now(),
                'facebook_error'     => null,
            ]);
            return $res;
        } else {
            $item->update([
                'facebook_status' => 'failed',
                'facebook_error'  => $res['message'],
            ]);
            return $res;
        }
    }

    /**
     * Create an article on the website for this video.
     */
    public function createArticleFromVideo(VideoWorkshopItem $item): Article
    {
        if ($item->article_id && $item->article) {
            return $item->article;
        }

        $videoUrl = $item->processed_video_url ?: $item->original_video_url;
        
        // Build video player HTML
        $videoPlayerHtml = '
        <div class="video-workshop-player mb-4" style="background:#000; border-radius:12px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
            <video controls autoplay muted playsinline style="width:100%; max-height:560px; display:block;" poster="' . ($item->thumbnail_url ?: '') . '">
                <source src="' . $videoUrl . '" type="video/mp4">
                আপনার ব্রাউজারটি ভিডিও প্লেয়ার সমর্থন করে না।
            </video>
        </div>';

        $article = new Article();
        $article->title = $item->title;
        $article->slug = Str::slug($item->title) . '-' . time();
        $article->category = $item->category ?: 'ভিডিও';
        $article->content = "<p><strong>বিডিবি নিউজ ভিডিও ডেস্ক:</strong> " . e($item->title) . "</p>" . $videoPlayerHtml . "<p>সত্যের সন্ধানে সার্বক্ষণিক বিডিবি নিউজের সাথে থাকুন।</p>";
        $article->image_url = $item->thumbnail_url ?: '/admin-assets/images/logo-sm.png';
        $article->source_name = 'BDB Video Workshop';
        $article->user_id = $item->created_by ?: (auth()->id() ?? 1);
        $article->save();

        $item->update(['article_id' => $article->id]);

        return $article;
    }

    /**
     * Probe video duration using ffprobe or ffmpeg.
     */
    public function probeVideoDuration(string $videoPath): ?int
    {
        $ffmpeg = $this->getFfmpegBinary();
        if (!$ffmpeg) return null;

        // Try using ffmpeg -i to parse Duration string
        $cmd = "{$ffmpeg} -i " . escapeshellarg($videoPath) . " 2>&1";
        $output = shell_exec($cmd);

        if ($output && preg_match('/Duration:\s*(\d+):(\d+):(\d+\.?\d*)/', $output, $m)) {
            $hours = (int)$m[1];
            $minutes = (int)$m[2];
            $seconds = (float)$m[3];
            return (int)round(($hours * 3600) + ($minutes * 60) + $seconds);
        }

        return null;
    }

    /**
     * Get path to the watermark logo image.
     */
    public function getWatermarkLogoPath(): string
    {
        $custom = Setting::where('key', 'video_workshop_watermark_path')->value('value');
        if ($custom && file_exists(public_path($custom))) {
            return public_path($custom);
        }

        $siteLogo = Setting::where('key', 'site_logo')->value('value');
        if ($siteLogo && file_exists(public_path($siteLogo))) {
            return public_path($siteLogo);
        }

        // Built-in crisp admin logo
        $defaultLogo = public_path('admin-assets/images/logo-sm.png');
        if (file_exists($defaultLogo)) {
            return $defaultLogo;
        }

        return public_path('favicon.ico');
    }

    /**
     * Locate FFmpeg binary (Configured in settings or auto-detected).
     */
    public function getFfmpegBinary(): ?string
    {
        $configured = Setting::where('key', 'video_workshop_ffmpeg_path')->value('value');
        if (!empty($configured) && $this->isBinaryExecutable($configured)) {
            return $configured;
        }

        // Potential paths
        $candidates = [
            'ffmpeg',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
            storage_path('app/bin/ffmpeg'),
            'C:\\ffmpeg\\bin\\ffmpeg.exe',
            'ffmpeg.exe',
        ];

        foreach ($candidates as $cand) {
            if ($this->isBinaryExecutable($cand)) {
                return $cand;
            }
        }

        return 'ffmpeg'; // Default fallback
    }

    /**
     * Locate yt-dlp binary (Configured in settings or auto-detected).
     */
    public function getYtDlpBinary(): ?string
    {
        $configured = Setting::where('key', 'video_workshop_ytdlp_path')->value('value');
        if (!empty($configured) && $this->isBinaryExecutable($configured)) {
            return $configured;
        }

        $candidates = [
            'yt-dlp',
            '/usr/bin/yt-dlp',
            '/usr/local/bin/yt-dlp',
            'python3 -m yt_dlp',
            'python -m yt_dlp',
            'C:\\Python314\\python.exe -m yt_dlp',
            storage_path('app/bin/yt-dlp'),
            'yt-dlp.exe',
        ];

        foreach ($candidates as $cand) {
            if ($this->isBinaryExecutable($cand)) {
                return $cand;
            }
        }

        return 'yt-dlp'; // Default fallback
    }

    /**
     * Check if a command or binary is executable.
     */
    public function isBinaryExecutable(string $cmd): bool
    {
        try {
            $testCmd = $cmd . ' --version 2>&1';
            $output = @shell_exec($testCmd);
            return !empty($output) && !str_contains(strtolower($output), 'not recognized') && !str_contains(strtolower($output), 'no such file');
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Diagnose environment for Video Workshop (FFmpeg, yt-dlp, Facebook, Permissions).
     */
    public function diagnoseEnvironment(): array
    {
        $ffmpegPath = $this->getFfmpegBinary();
        $ffmpegOk = $this->isBinaryExecutable($ffmpegPath);
        $ffmpegVersion = $ffmpegOk ? trim(explode("\n", shell_exec($ffmpegPath . ' -version 2>&1') ?? '')[0] ?? '') : 'Not Found';

        $ytdlpPath = $this->getYtDlpBinary();
        $ytdlpOk = $this->isBinaryExecutable($ytdlpPath);
        $ytdlpVersion = $ytdlpOk ? trim(shell_exec($ytdlpPath . ' --version 2>&1') ?? '') : 'Not Found';

        $fbStatus = $this->facebookService->testConnection();

        $storageWritable = is_writable(storage_path('app/public'));

        return [
            'ffmpeg' => [
                'ok'      => $ffmpegOk,
                'path'    => $ffmpegPath,
                'version' => $ffmpegVersion,
            ],
            'ytdlp' => [
                'ok'      => $ytdlpOk,
                'path'    => $ytdlpPath,
                'version' => $ytdlpVersion,
            ],
            'facebook' => [
                'ok'        => $fbStatus['success'] ?? false,
                'message'   => $fbStatus['message'] ?? 'Not Configured',
                'page_name' => $fbStatus['page_name'] ?? null,
                'page_id'   => $fbStatus['page_id'] ?? null,
            ],
            'storage' => [
                'writable' => $storageWritable,
                'path'     => storage_path('app/public/videos'),
            ],
            'ready' => ($ffmpegOk && $ytdlpOk),
        ];
    }
}
