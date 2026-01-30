<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use App\Traits\HasMediaCollections;
use Spatie\MediaLibrary\HasMedia;

class ProductVariant extends Model implements HasMedia
{
    use HasFactory, HasMediaCollections;

    protected $guarded = [];

    protected $appends = ['variant_image_url', 'display_price', 'has_discount', 'smart_image'];

    /**
     * 動態取得顯示價格 (計算折扣後)
     */
    public function getDisplayPriceAttribute()
    {
        if (!$this->relationLoaded('product')) {
            return $this->price;
        }
        return app(\App\Services\DiscountService::class)->getDiscountedPrice($this->product, $this->price);
    }

    /**
     * 判斷是否有折扣
     */
    public function getHasDiscountAttribute(): bool
    {
        // 由於 display_price 已經有 defensive check，這裡直接使用即可
        return $this->display_price < $this->price;
    }

    public function getVariantImageUrlAttribute()
    {
        if ($this->relationLoaded('media')) {
            $url = $this->getFirstMediaUrl('variant_image', 'thumb');
            if ($url) return $url;
            $url = $this->getFirstMediaUrl('variant_image');
            if ($url) return $url;
        }
        return $this->image;
    }

    /**
     * 更智慧的圖片選取邏輯：
     */
    public function getSmartImageAttribute()
    {
        // 1. 規格圖
        $url = $this->variant_image_url;
        if (!empty($url)) return $url;

        // 2. 規格選項代表圖
        // 這需要產品的 options 欄位有設定
        $variantAttributes = $this->getAttribute('attributes');
        if ($this->relationLoaded('product') && !empty($this->product->options) && !empty($variantAttributes)) {
            foreach ($this->product->options as $option) {
                // 如果這個規格有此選項目
                if (isset($variantAttributes[$option['name']])) {
                    $val = $variantAttributes[$option['name']];
                    // 在選項定義中找對應的值
                    foreach ($option['values'] as $valueDef) {
                        if ($valueDef['value'] == $val && !empty($valueDef['image'])) {
                            return $valueDef['image'];
                        }
                    }
                }
            }
        }

        // 3. 回退到商品主圖
        if ($this->product) {
            return $this->product->primary_image;
        }

        return null;
    }

    protected $casts = [
        'attributes' => 'array',
        'price_points' => 'float',
        'gift_value' => 'float',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
