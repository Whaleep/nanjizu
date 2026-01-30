<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Promotion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class DiscountService
{
    private static $directPromotionsCache = null;

    /**
     * Get the final price for a product after applying direct discounts.
     * This is used for product listing and detail pages.
     */
    public function getDiscountedPrice(Product $product, $basePrice = null)
    {
        $originalPrice = $basePrice ?? ($product->price ?? 0);

        if (self::$directPromotionsCache === null) {
            self::$directPromotionsCache = Promotion::where('is_active', true)
                ->where('type', 'direct')
                ->where(function ($q) {
                    $q->whereNull('start_at')->orWhere('start_at', '<=', now());
                })
                ->where(function ($q) {
                    $q->whereNull('end_at')->orWhere('end_at', '>=', now());
                })
                ->with(['products', 'categories', 'productTags'])
                ->orderBy('priority', 'desc')
                ->get();
        }

        foreach (self::$directPromotionsCache as $promotion) {
            if ($promotion->appliesTo($product)) {
                if ($promotion->action_type === 'percent') {
                    return round($originalPrice * (1 - ($promotion->action_value / 100)));
                } elseif ($promotion->action_type === 'fixed') {
                    return max(0, $originalPrice - $promotion->action_value);
                }
            }
        }

        return $originalPrice;
    }

    /**
     * Calculate promotions for the entire cart.
     * Handles threshold-based discounts and gifts.
     */
    public function getCartPromotions(Collection $cartDetails)
    {
        $appliedPromotions = collect();

        // 1. Threshold Cart Promotions (Full cart amount)
        $thresholdPromos = Cache::tags(['promotions', 'threshold'])->remember(
            'active_threshold_promos_' . now()->format('Y-m-d-H'),
            3600, // 1小時，依需求調整
            function () {
                return Promotion::where('is_active', true)
                    ->whereIn('type', ['threshold_cart', 'threshold_product'])
                    ->where(function ($q) {
                        $q->whereNull('start_at')->orWhere('start_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('end_at')->orWhere('end_at', '>=', now());
                    })
                    ->with(['gifts.product.media', 'gifts.product', 'promotionGifts', 'categories', 'productTags'])
                    ->orderBy('priority', 'desc')
                    ->get();
            }
        );

        // 2. 計算活動在當前購物車的「額度」(Allowance)
        foreach ($thresholdPromos as $promo) {
            // 1. 先算原始總數 (可能是金額，也可能是件數)
            $rawTotal = $promo->calculateRawTotal($cartDetails);

            // 如果原始總數為 0，代表購物車裡根本沒買這個特惠活動的商品，直接跳過
            if ($rawTotal <= 0) {
                continue;
            }
            // 2. 判斷是否達標
            $isQualified = $rawTotal >= $promo->min_threshold;

            // 3. 設定額度 (未達標就是 0)
            $allowance = $isQualified ? $rawTotal : 0;

            // 4. 計算折扣金額 (僅在達標時計算)
            $discountAmount = 0;
            if ($isQualified) {
                if ($promo->action_type === 'percent') {
// 如果是打折(percent)，計算基礎必須是「金額」，不能是「件數」
                    
                    $baseForDiscount = 0;

                    if ($promo->threshold_type === 'amount') {
                        // 如果門檻本來就是金額，那 allowance 就是金額，直接用
                        $baseForDiscount = $allowance;
                    } else { // threshold_type === 'quantity'
                        // ★ 如果門檻是件數，allowance 是「件數」(例如 6)，不能拿來打折
                        // 我們需要重新計算這些符合條件商品的「總金額」
                        
                        // 這裡為了效能，我們可以手動篩選加總，避免再次查詢資料庫
                        $baseForDiscount = $cartDetails->sum(function ($item) use ($promo) {
                            // 排除贈品
                            if ($item->is_gift ?? false) return 0;
                            // 檢查是否符合活動範圍
                            if ($promo->appliesTo($item)) {
                                return $item->subtotal;
                            }
                            return 0;
                        });
                    }
                    // 計算折扣：總金額 * (折扣數值 / 100)
                    $discountAmount = round($baseForDiscount * ($promo->action_value / 100));
                } elseif ($promo->action_type === 'fixed') {
                    // 滿額折抵 (滿千送百邏輯，最多送 max_gift_count 次數)
                    if ($promo->is_repeatable && $promo->min_threshold > 0) {
                        $times = floor($allowance / $promo->min_threshold);
                        $finalTimes = $promo->max_gift_count ? min($times, $promo->max_gift_count) : $times;
                        $discountAmount = $finalTimes * $promo->action_value;
                    } else {
                        $discountAmount = $promo->action_value;
                    }
                }
            }

            // 5. 準備贈品選項 (如果是贈品型活動)
            // 這裡不負責篩選「買得起哪個」，全部丟給前端判斷
            $giftOptions = [];

            if ($promo->action_type === 'gift') {
                $giftOptions = $promo->gifts->map(function ($gift) {
                    $cost = (float) ($gift->pivot->unit_cost ?? 0);

                    return [
                        'id' => $gift->id,
                        'name' => $gift->product->name . ' - ' . $gift->name,
                        'image' => $gift->smart_image,
                        'stock' => $gift->stock,
                        'cost' => $cost,
                        'slug' => $gift->product->slug,
                    ];
                })->toArray();
            }

            // 取得適用範圍 ID 列表 (Scope IDs) 讓前端可以標記「哪些商品符合這個活動」
            $scopeIds = [];
            if ($promo->scope === 'category') {
                $scopeIds = $promo->categories->pluck('id')->toArray();
            } elseif ($promo->scope === 'tag') {
                $scopeIds = $promo->productTags->pluck('id')->toArray();
            } elseif ($promo->scope === 'product') {
                $scopeIds = $promo->products->pluck('id')->toArray();
            }

            // 6. 組裝回傳資料
            $appliedPromotions->push([
                'id' => $promo->id,
                'name' => $promo->name,
                'description' => $promo->description,
                'type' => $promo->type,
                'action_type' => $promo->action_type,
                'threshold_type' => $promo->threshold_type,
                'is_qualified' => $isQualified,
                'current_total' => $rawTotal,
                'min_threshold' => $promo->min_threshold,
                'allowance' => $allowance,
                'discount_amount' => $discountAmount,
                'max_gift_count' => $promo->max_gift_count,
                'start_at' => $promo->start_at?->toIso8601String(),
                'end_at' => $promo->end_at?->toIso8601String(),
                'scope' => $promo->scope,
                'scope_ids' => $scopeIds,
                'gift_options' => $giftOptions,
            ]);
        }
        // TODO: Implement multi-buy logic (e.g., Buy 3 items from Tag X)
        // $groupedByCategory = $cartDetails->groupBy('category_id');
        // foreach ($groupedByCategory as $catId => $items) {
        //     if (count($items) >= $promo->min_quantity) { ... }
        // }

        return $appliedPromotions;
    }

    /**
     * 取得單一商品適用的「條件特惠」活動 (用於列表頁顯示標籤)
     */
    public function getProductPromotions(Product $product)
    {
        // 取得所有活躍的滿額/滿件活動 (使用與購物車相同的 Cache)
        $thresholdPromos = Cache::tags(['promotions', 'threshold'])->remember(
            'active_threshold_promos_' . now()->format('Y-m-d-H'),
            3600,
            function () {
                // ... (這裡的查詢邏輯與 getCartPromotions 裡的一模一樣，可以直接複製過來，或重構提取共用)
                return Promotion::where('is_active', true)
                    ->whereIn('type', ['threshold_cart', 'threshold_product'])
                    ->where(function ($q) {
                        $q->whereNull('start_at')->orWhere('start_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('end_at')->orWhere('end_at', '>=', now());
                    })
                    ->with(['categories', 'productTags', 'products']) // 這裡不需要載入贈品，只要範圍判斷
                    ->orderBy('priority', 'desc')
                    ->get();
            }
        );

        $applicablePromos = collect();

        foreach ($thresholdPromos as $promo) {
            if ($promo->appliesTo($product)) {
                $applicablePromos->push([
                    'id' => $promo->id,
                    'name' => $promo->name,
                    'type' => $promo->type,
                    // 可以多傳一點簡單描述給 Tooltip 用
                    'description' => $promo->action_type === 'gift' 
                        ? "滿 " . ($promo->threshold_type==='quantity' ? "{$promo->min_threshold}件" : "\${$promo->min_threshold}") . " 送贈品"
                        : "滿 " . ($promo->threshold_type==='quantity' ? "{$promo->min_threshold}件" : "\${$promo->min_threshold}") . " 享折扣",
                ]);
            }
        }

        return $applicablePromos;
    }

    /**
     * 清除促銷活動快取 (通常在後台更新活動、或訂單成立後呼叫)
     */
    public function clearCaches()
    {
        Cache::tags(['promotions'])->flush();
        self::$directPromotionsCache = null;
    }
}
