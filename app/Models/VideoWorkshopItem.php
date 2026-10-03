<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class VideoWorkshopItem extends Model
{
    use HasFactory;

    protected $table = 'video_workshop_items';

    protected $fillable = [
        'title',
        'category',
        'source_type',
        'source_url',
        'youtube_video_id',
        'channel_name',
        'thumbnail_url',
        'duration_seconds',
        'trim_start',
        'trim_end',
        'branding_type',
        'watermark_position',
        'branding_text',
        'original_video_path',
        'processed_video_path',
        'status',
        'status_message',
        'error_message',
        'auto_post_facebook',
        'facebook_status',
        'facebook_post_id',
        'facebook_video_url',
        'facebook_posted_at',
        'facebook_error',
        'article_id',
        'created_by',
    ];

    protected $casts = [
        'duration_seconds' => 'integer',
        'trim_start' => 'integer',
        'trim_end' => 'integer',
        'auto_post_facebook' => 'boolean',
        'facebook_posted_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function article()
    {
        return $this->belongsTo(Article::class, 'article_id');
    }

    /**
     * Get URL for the original video
     */
    public function getOriginalVideoUrlAttribute(): ?string
    {
        if (empty($this->original_video_path)) {
            return null;
        }
        return Storage::disk('public')->url($this->original_video_path);
    }

    /**
     * Get URL for the processed/branded video
     */
    public function getProcessedVideoUrlAttribute(): ?string
    {
        if (empty($this->processed_video_path)) {
            return null;
        }
        return Storage::disk('public')->url($this->processed_video_path);
    }

    /**
     * Format duration into mm:ss
     */
    public function getFormattedDurationAttribute(): string
    {
        if (!$this->duration_seconds) {
            return '--:--';
        }
        $m = floor($this->duration_seconds / 60);
        $s = $this->duration_seconds % 60;
        return sprintf('%02d:%02d', $m, $s);
    }

    /**
     * Estimated duration after trimming
     */
    public function getNetDurationAttribute(): ?int
    {
        if (!$this->duration_seconds) {
            return null;
        }
        $net = $this->duration_seconds - ($this->trim_start ?? 0) - ($this->trim_end ?? 0);
        return max(1, $net);
    }

    /**
     * Formatted net duration after trimming
     */
    public function getFormattedNetDurationAttribute(): string
    {
        $net = $this->net_duration;
        if (!$net) {
            return '--:--';
        }
        $m = floor($net / 60);
        $s = $net % 60;
        return sprintf('%02d:%02d', $m, $s);
    }
}
