<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;
    protected $guarded = [];

    protected $casts = [
        'images' => 'array',
        'description' => 'array', // 自動轉為陣列
        'is_active' => 'boolean',
        'content' => 'array',
    ];

    // 自動附加這個虛擬欄位到 JSON
    protected $appends = ['primary_image', 'is_wishlisted'];

    // 定義 primary_image 邏輯
    public function getPrimaryImageAttribute()
    {
        // 1. 優先讀取新的多圖欄位 (取第一張)
        if (!empty($this->images) && is_array($this->images) && count($this->images) > 0) {
            return $this->images[0];
        }

        // 2. 如果新欄位沒資料，回退讀取舊欄位 (相容舊資料)
        if (!empty($this->image)) {
            return $this->image;
        }

        // 3. 都沒有就回傳 null
        return null;
    }

    // 判斷當前登入使用者是否收藏
    public function getIsWishlistedAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
        }

        // 快取機制：避免在列表渲染時造成過多重複 SQL
        // 雖然 Laravel 本身有快取，但我們可以在載入時使用 withExists 效能更好
        // 這裡提供基礎判斷
        return $this->wishlistedBy()->where('user_id', auth()->id())->exists();
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
