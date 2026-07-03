<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GenerationLog extends Model
{
    protected $fillable = [
        'source_name',
        'category_id',
        'article_id',
        'status',
        'error_message',
        'response_time_ms',
        'gemini_model',
        'used_grounding',
    ];

    protected $casts = [
        'used_grounding' => 'boolean',
    ];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function article()
    {
        return $this->belongsTo(Article::class);
    }
}
