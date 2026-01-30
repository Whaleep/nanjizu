<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Promotion extends Model
{
    use HasFactory;

    protected $guarded = [];

    private static $productTagsCache = [];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'is_active' => 'boolean',
        'show_badge' => 'boolean',
        'action_value' => 'float',
        'min_threshold' => 'float',
        'max_gift_count' => 'integer',
    ];

    // Relationships
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'promotion_product');
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ShopCategory::class, 'promotion_shop_category');
    }

    public function productTags(): BelongsToMany
    {
        return $this->belongsToMany(ProductTag::class, 'promotion_product_tag');
    }

    public function gifts(): BelongsToMany
    {
        return $this->belongsToMany(ProductVariant::class, 'promotion_gifts', 'promotion_id', 'product_variant_id')
                    ->withPivot('quantity', 'unit_cost') 
                    ->withTimestamps();
    }

    public function promotionGifts(): HasMany
    {
        return $this->hasMany(PromotionGift::class);
    }

    // Logic
    public function isValid()
    {
        if (!$this->is_active) return false;
        if ($this->start_at && now()->lt($this->start_at)) return false;
        if ($this->end_at && now()->gt($this->end_at)) return false;
        return true;
    }

    /**
     * 計算購物車內符合此活動範圍的「原始總數值」 (不檢查門檻)
     */
    public function calculateRawTotal($cartDetails): float
    {
        return $cartDetails->sum(function ($item) {
            // 排除贈品
            if ($item->is_gift ?? false) return 0;

            // 檢查此商品是否在活動範圍內
            if ($this->appliesTo($item)) {
                return $this->threshold_type === 'quantity' 
                    ? $item->quantity 
                    : $item->subtotal;
            }
            return 0;
        });
    }

    /**
     * 計算此活動在當前購物車中提供的「可折抵額度」
     * 
      * @param \Illuminate\Support\Collection $cartDetails
      * @return float
     */
    public function computeAllowance($cartDetails): float
    {
        // 1. 計算符合條件的總量 (總金額 或 總件數)
        $rawTotal = $this->calculateRawTotal($cartDetails);

        // 2. 檢查是否達到最低門檻 (若未達標，額度為 0)
        if ($rawTotal < $this->min_threshold) {
            return 0;
        }

        // 3. 回傳額度
        // 如果是「滿額贈」，額度就是消費金額 (買5000，額度5000)
        // 如果是「滿件贈」，額度就是件數 (買10件，額度10)
        return $rawTotal;
    }

    /**
     * 計算購物車內符合此活動範圍的「累積數值」
     * @param Collection $cartDetails 購物車明細集合
     * @return float 累積金額或累積數量
     */
    public function calculateQualifyingTotal($cartDetails): float
    {
        return $cartDetails->sum(function ($item) {
            // 排除贈品本身
            if ($item->is_gift ?? false) return 0;

            // 檢查此商品是否在活動範圍內 (Tag/Category/Product)
            if ($this->appliesTo($item)) {
                // 根據 threshold_type 決定累加「金額」還是「數量」
                return $this->threshold_type === 'quantity' 
                    ? $item->quantity 
                    : $item->subtotal; // 假設 item->subtotal 是數量*單價
            }
            return 0;
        });
    }
    
    /**
     * 檢查是否符合活動條件
     * @param float $qualifyingTotal calculateQualifyingTotal 算出來的值
     */
    public function checkThreshold(float $qualifyingTotal): bool
    {
        return $qualifyingTotal >= $this->min_threshold;
    }

    /**
     * Check if this promotion applies to a specific product or cart item
     */
    public function appliesTo($data): bool
    {
        if (!$this->isValid()) return false;

        // Extract ID and attributes from various possible data structures
        $productId = null;
        $categoryId = null;
        $productModel = null;

        if ($data instanceof Product) {
            $productId = $data->id;
            $categoryId = $data->shop_category_id;
            $productModel = $data;
        } elseif (is_object($data)) {
            $productId = $data->product_id ?? null;
            $categoryId = $data->category_id ?? null;
            // If it's a detail object, it might not have the full model
        }

        if (!$productId) return false;

        if ($this->scope === 'all') return true;

        if ($this->scope === 'product') {
            return $this->products->contains('id', $productId);
        }

        if ($this->scope === 'category') {
            return $this->categories->contains('id', $categoryId);
        }

        if ($this->scope === 'tag') {
            // Tag check requires the product model or pre-calculated tag IDs
            $tagIds = [];
            if ($productModel) {
                if ($productModel->relationLoaded('productTags')) {
                    $tagIds = $productModel->productTags->pluck('id')->toArray();
                } else {
                    $tagIds = $productModel->productTags()->pluck('product_tags.id')->toArray();
                }
            } elseif (isset($data->tag_ids)) {
                $tagIds = $data->tag_ids;
            }

            if (empty($tagIds)) return false;

            $promoTagIds = $this->productTags->pluck('id')->toArray();
            return !empty(array_intersect($tagIds, $promoTagIds));
        }

        return false;
    }

    /**
     * Calculate the subtotal of items in the cart that qualify for this promotion.
     */
    public function getQualifyingSubtotal($cartDetails): float
    {
        return $cartDetails->sum(function ($item) {
            // Only regular items count towards the threshold, not gifts
            if ($item->is_gift) return 0;

            if ($this->appliesTo($item)) {
                return $item->subtotal;
            }
            return 0;
        });
    }
}
