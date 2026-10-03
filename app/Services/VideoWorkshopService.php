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
        $escapedYtdlp = $ytdlp;
        $escapedUrl = escapeshellarg($item->source_url);
        $escapedOutput = escapeshellarg($outputPath);

        $ffmpegPath = $this->getFfmpegBinary();
        $ffmpegDir = ($ffmpegPath && $this->isBinaryExecutable($ffmpegPath)) 
            ? (is_dir($ffmpegPath) ? $ffmpegPath : dirname($ffmpegPath))
            : null;
        $ffmpegFlag = $ffmpegDir ? "--ffmpeg-location " . escapeshellarg($ffmpegDir) . " " : "";

        // Standard merge to MP4 (optimized up to 720p for fast download)
        $cmd = "{$escapedYtdlp} {$ffmpegFlag}-f \"best[height<=720]/bestvideo[height<=720]+bestaudio/best\" --merge-output-format mp4 --no-playlist -o {$escapedOutput} {$escapedUrl} 2>&1";
        
        Log::info("Executing yt-dlp: " . $cmd);
        $output = shell_exec($cmd);

        if (!file_exists($outputPath) || filesize($outputPath) < 1000) {
            // Fallback download attempt with simpler format selector
            $cmdFallback = "{$escapedYtdlp} {$ffmpegFlag}-f \"best\" --no-playlist -o {$escapedOutput} {$escapedUrl} 2>&1";
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
     * Run full workflow: Download + Process (Direct or AI Reels) + (Auto Facebook Post).
     */
    public function runFullWorkflow(VideoWorkshopItem $item): array
    {
        if ($item->ai_mode === 'reels') {
            return $this->compileAiVerticalReels($item);
        }

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
     * Generate an AI voiceover news script using Gemini API (with smart Bengali template fallback).
     */
    public function generateAiScript(string $title, ?string $context = null): array
    {
        $apiKey = Setting::where('key', 'gemini_api_key')->value('value')
            ?: Setting::where('key', 'ai_gemini_api_key')->value('value')
            ?: config('services.gemini.key');

        if (!empty($apiKey)) {
            $prompt = "You are a professional Bengali TV & Social Media News Anchor for 'BDB News' (বিডিবি নিউজ). Write an engaging, crisp, objective Bengali voiceover news report script for a vertical 25-35 second Reel/Short about the following news topic:
Topic: {$title}
" . ($context ? "Context details: {$context}\n" : "") . "
Rules:
- 4 to 5 short, impactful sentences in standard modern Bengali (প্রমিত চলিত বাংলা).
- Total duration should be around 20-30 seconds spoken (around 50-70 words).
- End with: 'বিস্তারিত জানতে বিডিবি নিউজের সাথেই থাকুন।'
- Return pure plain text only (do NOT include markdown, asterisks, bullet points, english text, or notes). Just the exact spoken words.";

            try {
                $endpoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=" . $apiKey;
                $res = Http::timeout(20)->post($endpoint, [
                    'contents' => [
                        ['parts' => [['text' => $prompt]]]
                    ]
                ]);

                if ($res->successful()) {
                    $json = $res->json();
                    $text = $json['candidates'][0]['content']['parts'][0]['text'] ?? '';
                    $cleanScript = trim(preg_replace('/[*#_`\[\]]/u', '', $text));
                    if (!empty($cleanScript)) {
                        return [
                            'success'           => true,
                            'script'            => $cleanScript,
                            'source'            => 'gemini',
                            'estimated_seconds' => max(15, (int)round(mb_strlen($cleanScript, 'UTF-8') / 15)),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("Gemini script generation failed: " . $e->getMessage());
            }
        }

        // Smart Bengali news anchor template fallback
        $cleanTitle = trim(preg_replace('/[*#_`]/u', '', $title));
        $fallback = "{$cleanTitle} শীর্ষক ঘটনাটি নিয়ে ব্যাপক আলোচনা শুরু হয়েছে। সাম্প্রতিক তথ্যানুযায়ী, এই বিষয়ে সংশ্লিষ্ট মহলে গভীর পর্যবেক্ষণ চলছে। ঘটনাটির প্রভাব সাধারণ মানুষ ও আন্তর্জাতিক পরিমণ্ডলে গুরুত্ব পাচ্ছে। বিস্তারিত ও সর্বশেষ তথ্য জানতে বিডিবি নিউজের সাথেই থাকুন।";

        return [
            'success'           => true,
            'script'            => $fallback,
            'source'            => 'template',
            'estimated_seconds' => 20,
        ];
    }

    /**
     * Synthesize Bengali speech from text using Google TTS (sentence-chunked).
     */
    public function generateVoiceoverAudio(string $bengaliText, string $prefix = 'vo'): array
    {
        $voDir = storage_path('app/public/videos/voiceover');
        if (!file_exists($voDir)) {
            mkdir($voDir, 0755, true);
        }

        $filename = 'vo_' . preg_replace('/[^a-zA-Z0-9_-]/', '', $prefix) . '_' . time() . '.mp3';
        $fullPath = $voDir . DIRECTORY_SEPARATOR . $filename;
        $relPath = 'videos/voiceover/' . $filename;

        // Split text into punctuation-based sentences
        $clean = trim(preg_replace('/[\r\n\t]+/', ' ', $bengaliText));
        $chunks = preg_split('/([।?!]+)/u', $clean, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
        $mergedChunks = [];
        $temp = '';
        foreach ($chunks as $chunk) {
            if (in_array(trim($chunk), ['।', '?', '!'])) {
                $temp .= $chunk;
                $mergedChunks[] = trim($temp);
                $temp = '';
            } else {
                $temp .= ' ' . trim($chunk);
            }
        }
        if (!empty(trim($temp))) {
            $mergedChunks[] = trim($temp);
        }

        if (empty($mergedChunks)) {
            $mergedChunks = [$clean];
        }

        $fp = fopen($fullPath, 'wb');
        if (!$fp) {
            return ['success' => false, 'message' => 'ভয়েসওভার ফাইল তৈরি করা যায়নি।'];
        }

        foreach ($mergedChunks as $chunk) {
            $chunk = trim($chunk);
            if (empty($chunk)) continue;

            $url = "https://translate.google.com/translate_tts?ie=UTF-8&q=" . urlencode($chunk) . "&tl=bn&client=tw-ob";
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36');
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            $data = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($code === 200 && !empty($data)) {
                fwrite($fp, $data);
            }
        }
        fclose($fp);

        if (!file_exists($fullPath) || filesize($fullPath) < 1000) {
            return ['success' => false, 'message' => 'ভয়েসওভার অডিও তৈরি ব্যর্থ হয়েছে।'];
        }

        // Measure duration
        $duration = 20.0;
        $ffmpeg = $this->getFfmpegBinary();
        if ($ffmpeg) {
            $cmd = "{$ffmpeg} -i " . escapeshellarg($fullPath) . " 2>&1";
            $output = shell_exec($cmd);
            if ($output && preg_match('/Duration:\s*(\d+):(\d+):(\d+\.?\d*)/', $output, $m)) {
                $duration = (int)$m[1] * 3600 + (int)$m[2] * 60 + (float)$m[3];
            }
        }

        return [
            'success'   => true,
            'path'      => $relPath,
            'full_path' => $fullPath,
            'duration'  => round($duration, 2),
        ];
    }

    /**
     * Extract strategic keyframes from video for Ken Burns animation.
     */
    public function extractVideoKeyframes(string $videoPath, int $count = 5): array
    {
        $ffmpeg = $this->getFfmpegBinary();
        if (!$ffmpeg) return [];

        $totalDur = $this->probeVideoDuration($videoPath) ?: 60;
        $framesDir = storage_path('app/public/videos/frames');
        if (!file_exists($framesDir)) {
            mkdir($framesDir, 0755, true);
        }

        // Space samples between 12% and 85% of duration to skip TV intro/outro bumpers
        $startPct = 0.12;
        $endPct = 0.85;
        $step = ($endPct - $startPct) / max(1, ($count - 1));

        $frames = [];
        $unique = time() . '_' . rand(100, 999);

        for ($i = 0; $i < $count; $i++) {
            $pct = $startPct + ($i * $step);
            $ts = max(1, (int)round($totalDur * $pct));
            
            $frameFilename = "frame_{$unique}_{$i}.jpg";
            $frameFullPath = $framesDir . DIRECTORY_SEPARATOR . $frameFilename;
            $frameRelPath = "videos/frames/{$frameFilename}";

            $cmd = "{$ffmpeg} -y -ss {$ts} -i " . escapeshellarg($videoPath) . " -frames:v 1 -q:v 2 -update 1 " . escapeshellarg($frameFullPath) . " 2>&1";
            shell_exec($cmd);

            if (file_exists($frameFullPath) && filesize($frameFullPath) > 1000) {
                $frames[] = [
                    'rel_path'  => $frameRelPath,
                    'full_path' => $frameFullPath,
                    'timestamp' => $ts,
                ];
            }
        }

        return $frames;
    }

    /**
     * Compile AI Vertical Reels (9:16) with Ken Burns motion, Google TTS Bengali voiceover & BDB News branding.
     */
    public function compileAiVerticalReels(VideoWorkshopItem $item): array
    {
        $item->update([
            'status'         => 'processing',
            'status_message' => 'কপিরাইট-মুক্ত এআই রিলস প্রস্তুত হচ্ছে (ডাউনলোড, ফ্রেম এনালাইসিস ও ভয়েসওভার)...',
            'error_message'  => null,
        ]);

        $ffmpeg = $this->getFfmpegBinary();
        if (!$ffmpeg || !$this->isBinaryExecutable($ffmpeg)) {
            $msg = 'FFmpeg পাওয়া যায়নি। অনুগ্রহ করে Settings থেকে কনফিগার করুন।';
            $item->update(['status' => 'failed', 'status_message' => 'প্রসেসিং ব্যর্থ', 'error_message' => $msg]);
            return ['success' => false, 'message' => $msg];
        }

        // 1. Ensure original video is downloaded
        if (empty($item->original_video_path) || !file_exists(storage_path('app/public/' . $item->original_video_path))) {
            $dlRes = $this->downloadVideo($item);
            if (!$dlRes['success']) {
                return $dlRes;
            }
            $item->refresh();
        }

        $sourceVideo = storage_path('app/public/' . $item->original_video_path);

        // 2. Ensure narration script
        $script = trim($item->narration_script ?? '');
        if (empty($script)) {
            $scriptRes = $this->generateAiScript($item->title);
            $script = $scriptRes['script'] ?? $item->title;
            $item->update(['narration_script' => $script]);
        }

        // 3. Generate voiceover audio
        $item->update(['status_message' => 'বাংলা ভয়েসওভার অডিও তৈরি হচ্ছে...']);
        $voRes = $this->generateVoiceoverAudio($script, 'item_' . $item->id);
        if (!$voRes['success']) {
            $item->update(['status' => 'failed', 'status_message' => 'ভয়েসওভার ব্যর্থ', 'error_message' => $voRes['message']]);
            return $voRes;
        }

        $voFullPath = $voRes['full_path'];
        $voDur = $voRes['duration'] ?: 20.0;
        $item->update([
            'voiceover_path'   => $voRes['path'],
            'duration_seconds' => (int)ceil($voDur),
        ]);

        // 4. Extract strategic keyframes
        $item->update(['status_message' => 'ভিডিও থেকে গুরুত্বপূর্ণ ৫টি এইচডি দৃশ্য সংগ্রহ করা হচ্ছে...']);
        $frames = $this->extractVideoKeyframes($sourceVideo, 5);
        if (empty($frames)) {
            $msg = 'ভিডিও থেকে ফ্রেম সংগ্রহ করা যায়নি।';
            $item->update(['status' => 'failed', 'status_message' => 'ফ্রেম সংগ্রহ ব্যর্থ', 'error_message' => $msg]);
            return ['success' => false, 'message' => $msg];
        }

        $item->update(['extracted_frames' => array_column($frames, 'rel_path')]);

        // 5. Render Ken Burns vertical zoompan segments
        $item->update(['status_message' => '৯:১৬ ভার্টিক্যাল জুম-ইন মোশন রেন্ডার হচ্ছে...']);
        $frameCount = count($frames);
        $segDur = round($voDur / $frameCount, 2);
        $fps = 25;
        $segFrames = (int)ceil($segDur * $fps);

        $processedDir = storage_path('app/public/videos/processed');
        if (!file_exists($processedDir)) {
            mkdir($processedDir, 0755, true);
        }

        $segFiles = [];
        $uniqueId = $item->id . '_' . time();

        foreach ($frames as $idx => $frameData) {
            $segFile = $processedDir . DIRECTORY_SEPARATOR . "seg_{$uniqueId}_{$idx}.mp4";
            
            // Alternating zoom-in and zoom-out
            if ($idx % 2 === 0) {
                $vf = "scale=-1:1280,zoompan=z='min(zoom+0.0012,1.20)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d={$segFrames}:s=720x1280:fps={$fps}";
            } else {
                $vf = "scale=-1:1280,zoompan=z='max(1.20-0.0012*on,1.0)':x='iw/2-(iw/zoom/2)':y='ih/2-(ih/zoom/2)':d={$segFrames}:s=720x1280:fps={$fps}";
            }

            $cmd = "{$ffmpeg} -y -loop 1 -i " . escapeshellarg($frameData['full_path']) . " -vf \"{$vf}\" -t {$segDur} -c:v libx264 -pix_fmt yuv420p -preset ultrafast " . escapeshellarg($segFile) . " 2>&1";
            shell_exec($cmd);

            if (file_exists($segFile) && filesize($segFile) > 1000) {
                $segFiles[] = $segFile;
            }
        }

        if (empty($segFiles)) {
            $msg = 'ভার্টিক্যাল সেগমেন্ট রেন্ডার ব্যর্থ হয়েছে।';
            $item->update(['status' => 'failed', 'status_message' => 'রেন্ডার ব্যর্থ', 'error_message' => $msg]);
            return ['success' => false, 'message' => $msg];
        }

        // 6. Create Concat Demuxer List
        $listFile = $processedDir . DIRECTORY_SEPARATOR . "concat_{$uniqueId}.txt";
        $listContent = "";
        foreach ($segFiles as $sf) {
            $listContent .= "file '" . str_replace('\\', '/', $sf) . "'\n";
        }
        file_put_contents($listFile, $listContent);

        // 7. Create ASS Subtitle Overlay
        $assFile = $processedDir . DIRECTORY_SEPARATOR . "sub_{$uniqueId}.ass";
        $assDurStr = sprintf('0:%02d:%02d.00', floor($voDur / 60), floor($voDur % 60));
        $cleanTitle = Str::limit(trim(preg_replace('/[\r\n\t]+/', ' ', $item->title)), 70);

        $assContent = "[Script Info]
Title: BDB News Vertical Reels
ScriptType: v4.00+
WrapStyle: 2
ScaledBorderAndShadow: yes
YCbCr Matrix: None
PlayResX: 720
PlayResY: 1280

[V4+ Styles]
Format: Name, Fontname, Fontsize, PrimaryColour, SecondaryColour, OutlineColour, BackColour, Bold, Italic, Underline, StrikeOut, ScaleX, ScaleY, Spacing, Angle, BorderStyle, Outline, Shadow, Alignment, MarginL, MarginR, MarginV, Encoding
Style: TopHeader,Hind Siliguri,32,&H00FFFFFF,&H000000FF,&H001010E0,&HE01010E0,1,0,0,0,100,100,0,0,3,10,0,8,30,30,50,1
Style: BottomHeadline,Hind Siliguri,30,&H00FFFFFF,&H000000FF,&H00000000,&HD0000000,1,0,0,0,100,100,0,0,3,12,0,2,30,30,70,1

[Events]
Format: Layer, Start, End, Style, Name, MarginL, MarginR, MarginV, Effect, Text
Dialogue: 0,0:00:00.00,{$assDurStr},TopHeader,,0,0,0,,{\\b1}  ● BDB NEWS | ব্রেকিং নিউজ  
Dialogue: 0,0:00:00.00,{$assDurStr},BottomHeadline,,0,0,0,,{\\b1\\c&H00FFFF&}শিরোনাম:{\\c&HFFFFFF&} " . addslashes($cleanTitle) . "
";
        file_put_contents($assFile, $assContent);

        // 8. Final Concat + Audio Mux + Subtitles Overlay
        $item->update(['status_message' => 'ফাইনাল এআই রিলস ভিডিও এনকোডিং চলছে...']);
        $outFilename = "reels_{$uniqueId}.mp4";
        $finalOutputPath = $processedDir . DIRECTORY_SEPARATOR . $outFilename;
        $relProcessed = "videos/processed/{$outFilename}";

        // Use relative path for ASS & concat to prevent Windows colon issues
        $relAss = "storage/app/public/videos/processed/sub_{$uniqueId}.ass";
        $relList = "storage/app/public/videos/processed/concat_{$uniqueId}.txt";

        $finalCmd = "{$ffmpeg} -y -f concat -safe 0 -i " . escapeshellarg($relList) . " -i " . escapeshellarg($voFullPath) . " -vf \"drawbox=y=ih-140:color=black@0.92:width=iw:height=140:t=fill,drawbox=y=ih-144:color=red:width=iw:height=4:t=fill,ass={$relAss}:fontsdir='public/fonts'\" -c:v libx264 -preset fast -crf 22 -c:a aac -b:a 128k -shortest " . escapeshellarg($finalOutputPath) . " 2>&1";

        Log::info("Executing Final AI Reels Render: " . $finalCmd);
        $finalOutput = shell_exec($finalCmd);

        // Cleanup temporary files
        @unlink($listFile);
        @unlink($assFile);
        foreach ($segFiles as $sf) {
            @unlink($sf);
        }

        if (file_exists($finalOutputPath) && filesize($finalOutputPath) > 10000) {
            $item->update([
                'processed_video_path' => $relProcessed,
                'status'               => 'completed',
                'status_message'       => 'কপিরাইট-মুক্ত এআই রিলস সফলভাবে তৈরি হয়েছে!',
                'error_message'        => null,
            ]);

            // Auto-post to Facebook if requested
            if ($item->auto_post_facebook) {
                $this->publishToFacebook($item);
            }

            return [
                'success'   => true,
                'path'      => $relProcessed,
                'full_path' => $finalOutputPath,
                'message'   => 'এআই রিলস ভিডিও সফলভাবে প্রস্তুত হয়েছে!',
            ];
        }

        $errMsg = 'ফাইনাল রিলস ভিডিও তৈরি ব্যর্থ: ' . Str::limit(trim($finalOutput ?? 'FFmpeg ত্রুটি'), 400);
        $item->update([
            'status'         => 'failed',
            'status_message' => 'এনকোডিং ব্যর্থ',
            'error_message'  => $errMsg,
        ]);

        return ['success' => false, 'message' => $errMsg];
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
        
        // Build responsive video player HTML based on format
        if ($item->ai_mode === 'reels') {
            $videoPlayerHtml = '
            <div class="video-workshop-reels-player my-4 text-center">
                <div style="max-width: 440px; margin: 0 auto; background: #000; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 40px rgba(0,0,0,0.3); border: 2px solid #e2e8f0;">
                    <div style="background: linear-gradient(135deg, #8A2387, #E94057, #F27121); color: #fff; padding: 10px 14px; font-weight: bold; font-size: 14px; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="mdi mdi-play-circle mr-1"></i> বিডিবি নিউজ এক্সক্লুসিভ রিলস</span>
                        <span style="background: rgba(255,255,255,0.25); padding: 2px 8px; border-radius: 4px; font-size: 11px;">৯:১৬ HD</span>
                    </div>
                    <video controls autoplay muted playsinline loop style="width: 100%; display: block; aspect-ratio: 9/16; object-fit: cover; background: #000;" poster="' . ($item->thumbnail_url ?: '') . '">
                        <source src="' . $videoUrl . '" type="video/mp4">
                        আপনার ব্রাউজারটি ভিডিও প্লেয়ার সমর্থন করে না।
                    </video>
                </div>
            </div>';
        } else {
            $videoPlayerHtml = '
            <div class="video-workshop-player my-4" style="background:#000; border-radius:12px; overflow:hidden; box-shadow:0 10px 25px rgba(0,0,0,0.2);">
                <video controls autoplay muted playsinline style="width:100%; max-height:560px; display:block;" poster="' . ($item->thumbnail_url ?: '') . '">
                    <source src="' . $videoUrl . '" type="video/mp4">
                    আপনার ব্রাউজারটি ভিডিও প্লেয়ার সমর্থন করে না।
                </video>
            </div>';
        }

        // Determine category_id
        $category = Category::where('name', $item->category)->first() 
            ?: Category::where('slug', 'video')->first()
            ?: Category::first();

        $primaryImage = $item->thumbnail_url;
        if (!empty($item->extracted_frames) && is_array($item->extracted_frames)) {
            $primaryImage = asset('storage/' . $item->extracted_frames[0]);
        }

        $scriptText = $item->narration_script ?: $item->title;
        $summary = Str::limit(strip_tags($scriptText), 180);

        $article = new Article();
        $article->title = $item->title;
        $article->slug = Str::slug($item->title) . '-' . time();
        $article->category_id = $category ? $category->id : 1;
        $article->summary = $summary;
        $article->content = "<p class='lead'><strong>বিডিবি নিউজ ডেস্ক:</strong> " . e($scriptText) . "</p>" . $videoPlayerHtml . "<p>সর্বশেষ ব্রেকিং নিউজ এবং ভিডিও প্রতিবেদন দেখতে চোখ রাখুন বিডিবি নিউজের সাথেই। সত্যের সন্ধানে সার্বক্ষণিক।</p>";
        $article->image_url = $primaryImage ?: '/admin-assets/images/logo-sm.png';
        $article->source_name = 'BDB Video Workshop';
        $article->user_id = $item->created_by ?: (auth()->id() ?? 1);
        $article->is_featured = true;
        $article->status = 'published';
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
            storage_path('app/bin/ffmpeg.exe'),
            storage_path('app/bin/ffmpeg'),
            'ffmpeg',
            '/usr/bin/ffmpeg',
            '/usr/local/bin/ffmpeg',
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
            $testCmd = $cmd . ' -version 2>&1';
            $output = @shell_exec($testCmd);
            if (empty($output)) {
                return false;
            }
            $lower = strtolower($output);
            if (str_contains($lower, 'not recognized') || 
                str_contains($lower, 'cannot find') || 
                str_contains($lower, 'no such file') ||
                str_contains($lower, 'command not found') ||
                str_contains($lower, 'syntax error')) {
                return false;
            }
            return str_contains($lower, 'version') || str_contains($lower, 'copyright');
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
