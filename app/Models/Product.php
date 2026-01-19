<?php

namespace App\Models;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\HandlesJsonMedia;
use App\Traits\HasMediaCollections;
use Spatie\MediaLibrary\HasMedia;

class Product extends Model implements HasMedia
{
    use HasFactory, HasMediaCollections, HandlesJsonMedia;
    protected $guarded = [];

    protected $casts = [
        'description' => 'array',
        'is_active' => 'boolean',
        'content' => 'array',
        'options' => 'array',
    ];

    /**
     * 定義哪些 JSON 欄位需要自動清理圖片
     */
    public function jsonMediaAttributes(): array
    {
        return ['options', 'description', 'content'];
    }

    // 自動附加這個虛擬欄位到 JSON
    protected $appends = ['primary_image', 'images', 'is_wishlisted'];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_images')
            ->useDisk(config('media-library.disk_name'));

        // You can add more specific collections if needed
    }

    // 定義 primary_image 邏輯
    public function getPrimaryImageAttribute()
    {
        // Use Media Library to get the first image
        return $this->getFirstMediaUrl('product_images', 'thumb');
    }

    // 取得所有圖片的 URL 陣列
    public function getImagesAttribute()
    {
        return $this->getMedia('product_images')->map(function ($media) {
            return $media->getUrl();
        })->toArray();
    }

    // 判斷當前登入使用者是否收藏
    public function getIsWishlistedAttribute(): bool
    {
        if (!Auth::check()) {
            return false;
        }

        // 快取機制：避免在列表渲染時造成過多重複 SQL
        // 雖然 Laravel 本身有快取，但我們可以在載入時使用 withExists 效能更好
        // 這裡提供基礎判斷
        return $this->wishlistedBy()->where('user_id', Auth::id())->exists();
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ShopCategory::class, 'shop_category_id');
    }

    // 關鍵：一個商品有多個規格
    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    // 新增標籤關聯
    public function productTags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'product_product_tag');
    }

    // 保留這個別名以防其他地方有用到
    public function tags(): BelongsToMany
    {
        return $this->productTags();
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class)->where('is_visible', true);
    }

    // 取得平均評分 (Attribute)
    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating') ?? 0, 1); // 取一位小數
    }

    // 取得評價總數
    public function getReviewCountAttribute()
    {
        return $this->reviews()->count();
    }

    public function wishlistedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlists', 'product_id', 'user_id')->withTimestamps();
    }
}
