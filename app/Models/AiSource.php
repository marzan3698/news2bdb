<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiSource extends Model
{
    protected $fillable = ['name', 'url', 'type', 'status'];
}
