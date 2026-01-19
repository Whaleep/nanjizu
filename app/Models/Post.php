<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

use App\Traits\HasMediaCollections;
use Spatie\MediaLibrary\HasMedia;

use App\Traits\HandlesJsonMedia;

class Post extends Model implements HasMedia
{
    use HasMediaCollections, HandlesJsonMedia;
    protected $guarded = [];

    protected $appends = ['featured_image_url'];

    protected $casts = [
        'published_at' => 'datetime',
        'is_published' => 'boolean',
        'content' => 'array',
    ];

    /**
     * 定義哪些欄位需要自動清理圖片
     * 即使 content 不是 JSON (是 HTML 字串)，現在也能處理了
     */
    public function jsonMediaAttributes(): array
    {
        return ['content'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')
            ->singleFile()
            ->useDisk(config('media-library.disk_name'));
    }

    public function getFeaturedImageUrlAttribute()
    {
        return $this->getFirstMediaUrl('featured_image');
    }
}
