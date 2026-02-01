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
        'is_sellable' => 'boolean',
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
    protected $appends = ['primary_image', 'images', 'price', 'display_price', 'has_discount'];

    /**
     * 動態取得原始價格 (多規格時回傳最小值)
     */
    public function getPriceAttribute()
    {
        // 如果沒有預載 variants，直接回傳 0 避免 N+1 (在後台列表時)
        if (!$this->relationLoaded('variants')) {
            return 0;
        }
        return $this->variants->min('price') ?? 0;
    }

    /**
     * 動態取得顯示價格 (計算折扣後)
     */
    public function getDisplayPriceAttribute()
    {
        if (!$this->relationLoaded('variants')) {
            return 0;
        }
        return app(\App\Services\DiscountService::class)->getDiscountedPrice($this, $this->price);
    }

    /**
     * 判斷是否有折扣
     */
    public function getHasDiscountAttribute(): bool
    {
        if (!$this->relationLoaded('variants')) {
            return false;
        }
        return $this->display_price < $this->price;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('product_images')
            ->useDisk(config('media-library.disk_name'));

        // You can add more specific collections if needed
    }

    // 定義 primary_image 邏輯
    public function getPrimaryImageAttribute()
    {
        // 嘗試取得 'thumb' 縮圖，如果縮圖不存在 (例如 GIF/SVG 或生成失敗)，回退取用「原圖」
        if ($this->relationLoaded('media')) {
            $url = $this->getFirstMediaUrl('product_images', 'thumb');
            if ($url) return $url;

            $url = $this->getFirstMediaUrl('product_images');
            if ($url) return $url;
        }
        return $this->image; // 回退到欄位值
    }

    public function getImagesAttribute()
    {
        if (!$this->relationLoaded('media')) {
            return [];
        }
        return $this->getMedia('product_images')->map(function ($media) {
            return $media->getUrl();
        })->toArray();
    }

    public function getIsWishlistedAttribute(): bool
    {
        if (array_key_exists('is_wishlisted', $this->attributes)) {
            return (bool) $this->attributes['is_wishlisted'];
        }

        // 後台請求或是未登入，不進行額外查詢
        if (request()->is('admin*') || request()->is('filament*') || !Auth::check()) {
            return false;
        }

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
        if (!$this->relationLoaded('reviews')) {
            return 0;
        }
        return round($this->reviews()->avg('rating') ?? 0, 1); // 取一位小數
    }

    // 取得評價總數
    public function getReviewCountAttribute()
    {
        if (!$this->relationLoaded('reviews')) {
            return 0;
        }
        return $this->reviews()->count();
    }

    public function wishlistedBy(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'wishlists', 'product_id', 'user_id')->withTimestamps();
    }

    public function promotions(): BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'promotion_product');
    }
}
