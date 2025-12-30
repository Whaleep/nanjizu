<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Page extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'content' => 'array', // 自動轉換 JSON 為陣列
        'is_published' => 'boolean',
    ];
}
