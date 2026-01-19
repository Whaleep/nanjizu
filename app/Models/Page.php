<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Traits\HandlesJsonMedia;

class Page extends Model
{
    use HasFactory, HandlesJsonMedia;

    protected $guarded = [];

    protected $casts = [
        'content' => 'array', // 自動轉換 JSON 為陣列
        'is_published' => 'boolean',
    ];

    /**
     * 定義哪些欄位需要自動清理圖片
     */
    public function jsonMediaAttributes(): array
    {
        return ['content'];
    }
}
