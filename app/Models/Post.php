<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Post extends Model
{
    protected $guarded = [];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
    ];

    // 當模型啟動時執行
    protected static function booted(): void
    {
        // 監聽刪除事件 (deleting)
        static::deleting(function (Post $post) {
            // 如果這篇文章有圖片
            if ($post->image) {
                // 刪除 storage/app/public/posts/xxx.jpg
                Storage::disk('public')->delete($post->image);
            }
        });

        // 如果您希望在「更新圖片」時，自動刪除舊圖片，Filament 其實已經幫忙處理了大部分
        // 但如果您想確保萬無一失，也可以監聽 updating 事件，比較複雜，通常刪除事件就夠了
    }
}
