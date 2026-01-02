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
}
