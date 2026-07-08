<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VideoNews extends Model
{
    use HasFactory;

    protected $fillable = [
        'category',
        'concept_title',
        'n8n_execution_id',
        'facebook_video_url',
        'status',
    ];
}
