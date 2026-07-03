<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSource extends Model
{
    protected $fillable = ['name', 'url', 'type', 'status', 'category_id'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}
