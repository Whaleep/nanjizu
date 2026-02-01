<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\Promotion;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Exception;

class CartService
{
    protected $discountService;
    private $cachedVariants;
    private $cachedContent;
    private $memoizedCartDetails = null;    // 暫存「計算完成」的詳細購物車資料
    private $memoizedPromotions = null;     // 暫存「計算完成」的優惠活動

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }
    // 取得目前的購物車清單 (標準化為 [variant_id => quantity])
    protected function getContent()
    {
        if (Auth::check()) {
            $cart = Auth::user()->cart;
            if (!$cart) return collect([]);

            return $cart->items->map(function ($item) {
                return (object)[
                    'id' => $item->id,
                    'variant_id' => $item->product_variant_id,
                    'quantity' => $item->quantity,
                    'is_gift' => $item->is_gift,
                    'promotion_id' => $item->promotion_id
                ];
            });
        }

        $sessionCart = Session::get('cart', []);
        $content = collect($sessionCart)->map(function ($data, $key) {
            // Handle old format [id => qty] and new format [id => ['qty' => X, 'is_gift' => Y]]
            if (is_array($data)) {
                $actualVariantId = (str_contains($key, 'gift_')) ? explode('_', $key)[1] : $key;
                return (object) array_merge(['id' => $key, 'variant_id' => $actualVariantId], $data);
            }
            return (object) [
                'variant_id' => $key,
                'quantity' => $data,
                'is_gift' => false,
                'promotion_id' => null
            ];
        });
        $this->cachedContent = $content;
        return $content;
    }

    // 取得購物車內容 (結合資料庫最新資訊)
    public function getCartDetails()
    {
        // 如果已經算過了，直接回傳，阻斷後續 90% 的重複查詢
        if ($this->memoizedCartDetails !== null) {
            return $this->memoizedCartDetails;
        }

        // 先清理贈品 (僅一次)
        $this->cleanupGifts();
        $content = $this->getContent();
        if ($content->isEmpty()) {
            $this->memoizedPromotions = collect([]);     // 空車時活動也為空
            return $this->memoizedCartDetails = collect([]);
        }

        // 從資料庫撈取規格與商品資訊
        $query = ProductVariant::with([
            'media',
            'product.media',
            'product.productTags', // 這裡原本有，很好
            'product.category',    // ★ 建議補上：避免 appliesTo 檢查分類時觸發查詢
        ])
        ->whereIn('id', $content->pluck('variant_id'));

        // ★ 處理 is_wishlisted 的 N+1
        // 如果 Product Model 有 append 'is_wishlisted'，這裡必須預載，不然每一行都會查一次 DB
        if (Auth::check()) {
            $query->with(['product' => function ($q) {
                // 這裡利用巢狀預載入，同時載入 product 的關聯與 wishlistedBy
                $q->with(['media', 'productTags', 'category'])
                  ->withExists(['wishlistedBy as is_wishlisted' => function ($subQ) {
                      $subQ->where('user_id', Auth::id());
                  }]);
            }]);
        }

        $this->cachedVariants = $query->get()->keyBy('id');

        $standardItems = $content->map(function ($item) {
            $variant = $this->cachedVariants->get($item->variant_id);
            if (!$variant) return null;

            $quantity = min($item->quantity, $variant->stock);
            $displayImage = $variant->smart_image;
            $unitPrice = $item->is_gift ? 0 : $this->discountService->getDiscountedPrice($variant->product, $variant->price);

            return (object) [
                'cart_item_key' => $item->id,
                'variant_id' => $variant->id,
                'product_id' => $variant->product->id,
                'category_id' => $variant->product->shop_category_id,
                'tag_ids' => $variant->product->productTags->pluck('id')->toArray(),
                'product_name' => $variant->product->name . ($item->is_gift ? ' (贈品)' : ''),
                'product_slug' => $variant->product->slug,
                'variant_name' => $variant->name,
                'image' => $displayImage,
                'is_gift' => $item->is_gift,
                'promotion_id' => $item->promotion_id,
                'price' => $unitPrice,
                'original_price' => $variant->price,
                'has_discount' => !$item->is_gift && ($unitPrice < $variant->price),
                'stock' => $variant->stock,
                'quantity' => $quantity,
                'subtotal' => $unitPrice * $quantity,
            ];
        })->filter();

        // 取得適用活動
        $this->memoizedPromotions = $this->discountService->getCartPromotions($standardItems);

        // 為每個一般商品添加 applicable_promotions 欄位
        $finalItems = $standardItems->map(function ($item) {
            $activePromos = $this->memoizedPromotions;

            if (!$item->is_gift) {
                // 找出適用於此商品的促銷活動
                $applicablePromotionIds = $activePromos->filter(function ($promo) use ($item) {
                    // 檢查此促銷是否適用於此商品
                    if ($promo['scope'] === 'all') return true;

                    if ($promo['scope'] === 'category') {
                        return in_array($item->category_id, $promo['scope_ids']);
                    }

                    if ($promo['scope'] === 'tag') {
                        return !empty(array_intersect($item->tag_ids, $promo['scope_ids']));
                    }

                    if ($promo['scope'] === 'product') {
                        return in_array($item->product_id, $promo['scope_ids']);
                    }

                    return false;
                })->pluck('id')->toArray();

                $item->applicable_promotions = $applicablePromotionIds;
            } else {
                // 為已在購物車的贈品附加額度資訊
                $promo = $activePromos->firstWhere('id', $item->promotion_id);
                if ($promo) {
                    $item->promotion_name = $promo['name'];
                    $item->gift_quota_info = [
                        'total' => $promo['gift_quota'],
                        'used' => $promo['used_quota'],
                        'unit' => $promo['quota_unit']
                    ];
                }
            }

            return $item;
        });

        // 將結果存入暫存變數，再回傳
        return $this->memoizedCartDetails = $finalItems->values();
    }

    /**
     * 取得目前套用的全館/滿額優惠
     */
    public function getAppliedPromotions()
    {
        // 如果還沒計算過詳細內容，先跑一次 getCartDetails
        // getCartDetails 執行後會自動填入 $this->memoizedPromotions
        if ($this->memoizedPromotions === null) {
            $this->getCartDetails();
        }
        
        return $this->memoizedPromotions;
    }

    /**
     * 取得結帳用的購物車內容 (包含驗證後的贈品)
     * @param array $selectedGifts 來自 Session 的贈品選擇 ['promo_variant' => qty]
     */
    public function getCheckoutCartDetails($selectedGifts = [])
    {
        // 1. 取得一般商品 (已經包含 applicable_promotions 等標記)
        $cartItems = $this->getCartDetails();

        // 2. 取得目前的促銷活動狀態 (這時候 activePromos 裡的 allowance 是根據 cartItems 算好的)
        $activePromos = $this->getAppliedPromotions();

        // 3. 如果沒有選贈品，直接回傳
        if (empty($selectedGifts)) {
            return $cartItems;
        }

        $giftItems = collect([]);

        // 將選取的贈品按活動分組: [promoId => [[variantId => qty], ...]]
        $giftsByPromo = [];
        foreach ($selectedGifts as $key => $qty) {
            if ($qty <= 0) continue;

            // key 格式: promoId_variantId
            $parts = explode('_', $key);
            if (count($parts) !== 2) continue;

            $promoId = (int)$parts[0];
            $variantId = (int)$parts[1];

            $giftsByPromo[$promoId][] = [
                'variant_id' => $variantId,
                'quantity' => $qty
            ];
        }

        // 4. 逐一驗證每個活動的贈品是否合法
        foreach ($giftsByPromo as $promoId => $gifts) {
            // 找對應的促銷活動
            $promoData = $activePromos->firstWhere('id', $promoId);

            // 如果活動已過期、未達門檻(allowance=0)或不存在，直接跳過
            if (!$promoData || ($promoData['allowance'] ?? 0) <= 0) {
                continue; 
            }

            $allowance = (float) $promoData['allowance'];
            $maxGiftCount = $promoData['max_gift_count']; // 可能為 null

            $currentPromoTotalCost = 0;
            $currentPromoTotalQty = 0;
            $validatedGifts = [];

            // // 檢查總額度 (gift_quota 即為符合條件的消費金額)
            // $totalQuota = $promo['gift_quota'];
            // $usedWeight = 0;
            // $currentPromoGifts = [];

            // 取得該活動所有可選贈品資訊 (含 cost)
            $availableGiftOptions = collect($promoData['gift_options']);

            foreach ($gifts as $giftData) {
                $variantId = $giftData['variant_id'];
                $qty = $giftData['quantity'];

                $giftOption = $availableGiftOptions->firstWhere('id', $variantId);

                // 驗證 A: 贈品是否存在於該活動中
                if (!$giftOption) continue;
                
                // 驗證 B: 檢查庫存 (若請求數量 > 庫存，則下修為庫存量)
                if ($giftOption['stock'] < $qty) {
                    $qty = $giftOption['stock'];
                }
                if ($qty <= 0) continue;

                // 累計成本與數量
                // 注意：這裡使用新欄位 cost
                $itemTotalCost = $giftOption['cost'] * $qty;
                $currentPromoTotalCost += $itemTotalCost;
                $currentPromoTotalQty += $qty;

                // 暫存有效的贈品
                $validatedGifts[] = [
                    'option' => $giftOption,
                    'quantity' => $qty,
                    'cost_subtotal' => $itemTotalCost
                ];
            }

            // 驗證 C: 總成本是否超過額度 (Allowance)
            if ($currentPromoTotalCost > $allowance) {
                // 安全機制：若總成本超過額度，視為不合法請求，忽略此活動所有贈品
                // (也可以選擇拋出異常，但忽略通常使用者體驗較好，頂多是沒贈品)
                continue;
            }

            // 驗證 D: 是否超過總數量上限
            if (!is_null($maxGiftCount) && $currentPromoTotalQty > $maxGiftCount) {
                continue;
            }

            // 全部驗證通過，轉為 Cart Item 格式
            foreach ($validatedGifts as $item) {
                $giftOption = $item['option'];
                $qty = $item['quantity'];

                $giftItems->push((object)[
                    'cart_item_key' => "gift_{$promoId}_{$giftOption['id']}",
                    'variant_id' => $giftOption['id'],
                    'product_id' => 0,  // 贈品可能沒有獨立 product_id 邏輯，或從 option 抓
                    'product_name' => $giftOption['name'], // 這裡已經是 "產品名 - 規格名"
                    'variant_name' => '贈品',
                    'product_slug' => '', // 贈品通常不點擊跳轉
                    'image' => $giftOption['image'],
                    'is_gift' => true,
                    'promotion_id' => $promoId,
                    'promotion_name' => $promoData['name'],
                    'price' => 0,
                    'original_price' => $giftOption['cost'],
                    'has_discount' => false,
                    'stock' => $giftOption['stock'],
                    'quantity' => $qty,
                    'subtotal' => 0,
                    'gift_quota_info' => [
                        'total' => $allowance,
                        'used' => $currentPromoTotalCost,
                        'unit' => $promoData['threshold_type'] === 'quantity' ? '件' : 'currency'
                    ]
                ]);
            }
        }

        return $cartItems->merge($giftItems);
    }

    // 清除所有暫存
    private function resetMemoization()
    {
        $this->memoizedCartDetails = null;
        $this->memoizedPromotions = null;
    }

    // 加入購物車 (只處理一般商品，贈品由前端管理)
    public function add($variantId, $quantity, $isGift = false, $promotionId = null)
    {
        $this->resetMemoization();
        // 贈品不存入購物車，直接返回
        if ($isGift) {
            return;
        }

        // 1. 先抓出規格與庫存
        $variant = ProductVariant::with('product')->find($variantId);
        if (!$variant) {
            throw new Exception('商品規格不存在');
        }

        if (!$variant->product->is_sellable) {
            throw new Exception('贈品會在符合條件時自動加入購物車');
        }

        // 2. 檢查總量是否超過庫存
        $currentQtyInCart = $this->quantityOf($variantId, false);
        if (($currentQtyInCart + $quantity) > $variant->stock) {
            $remaining = max(0, $variant->stock - $currentQtyInCart);
            throw new Exception("庫存不足！目前購物車已有 {$currentQtyInCart} 件，您最多只能再加 {$remaining} 件。");
        }

        // 3. 執行寫入
        if (Auth::check()) {
            /** @var \App\Models\User $user */
            $user = Auth::user();
            $cart = $user->cart ?? $user->cart()->create();

            $item = $cart->items()
                ->where('product_variant_id', $variantId)
                ->where('is_gift', false)
                ->first();

            if ($item) {
                $item->increment('quantity', $quantity);
            } else {
                $cart->items()->create([
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'is_gift' => false,
                    'promotion_id' => null
                ]);
            }
        } else {
            $cart = Session::get('cart', []);

            if (isset($cart[$variantId])) {
                $cart[$variantId]['quantity'] += $quantity;
            } else {
                $cart[$variantId] = [
                    'quantity' => $quantity,
                    'is_gift' => false,
                    'promotion_id' => null
                ];
            }
            Session::put('cart', $cart);
        }
    }

    // 更新數量 (只處理一般商品)
    public function update($variantId, $quantity, $isGift = false, $promotionId = null)
    {
        $this->resetMemoization();
        // 贈品不處理，直接返回
        if ($isGift) {
            return;
        }

        if ($quantity <= 0) {
            $this->remove($variantId, false, null);
            return;
        }

        // 1. 檢查庫存
        $variant = ProductVariant::find($variantId);
        if (!$variant) throw new Exception('商品不存在');

        if ($quantity > $variant->stock) {
            throw new Exception("庫存不足！該商品目前僅剩 {$variant->stock} 件。");
        }

        // 2. 執行更新
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->cart) {
                $user->cart->items()
                    ->where('product_variant_id', $variantId)
                    ->where('is_gift', false)
                    ->update(['quantity' => $quantity]);
            }
        } else {
            $cart = Session::get('cart', []);
            if (isset($cart[$variantId])) {
                $cart[$variantId]['quantity'] = $quantity;
            }
            Session::put('cart', $cart);
        }
    }

    // 移除商品 (只處理一般商品)
    public function remove($variantId, $isGift = false, $promotionId = null)
    {
        $this->resetMemoization();
        // 贈品不處理，直接返回
        if ($isGift) {
            return;
        }

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->cart) {
                $user->cart->items()
                    ->where('product_variant_id', $variantId)
                    ->where('is_gift', false)
                    ->delete();
            }
        } else {
            $cart = Session::get('cart', []);
            unset($cart[$variantId]);
            Session::put('cart', $cart);
        }
    }

    // 清空購物車
    public function clear()
    {
        if (Auth::check()) {
            Auth::user()->cart?->delete(); // 直接刪除整台車，或者 delete items
        } else {
            Session::forget('cart');
        }
    }

    // 取得特定規格在購物車目前的數量
    public function quantityOf($variantId, $isGift = false, $promotionId = null)
    {
        $content = $this->getContent();
        $target = $content->first(function ($item) use ($variantId, $isGift, $promotionId) {
            return $item->variant_id == $variantId &&
                $item->is_gift == $isGift &&
                $item->promotion_id == $promotionId;
        });
        return $target ? $target->quantity : 0;
    }

    // 合併購物車 (登入時呼叫)
    public function mergeSessionToDb()
    {
        $this->resetMemoization();
        // 這裡不能用 $this->getContent()，因為登入後它會抓到 DB 的資料
        // 我們必須強制抓取 Session 中的原始資料
        $sessionCart = Session::get('cart', []);

        // 轉為 Collection 以便操作 (配合原本的邏輯結構)
        $sessionContent = collect($sessionCart)->map(function ($data, $key) {
            // 相容舊格式與新格式
            if (is_array($data)) {
                $actualVariantId = (str_contains($key, 'gift_')) ? explode('_', $key)[1] : $key;
                return (object) array_merge(['id' => $key, 'variant_id' => $actualVariantId], $data);
            }
            return (object) [
                'variant_id' => $key,
                'quantity' => $data,
                'is_gift' => false,
                'promotion_id' => null
            ];
        });
        // 這裡如果是未登入，會回傳從 Session 轉出的 Collection
        if ($sessionContent->isEmpty()) return;

        // 合併購物車
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $dbCart = $user->cart ?? $user->cart()->create();

        foreach ($sessionContent as $item) {
            if ($item->is_gift ?? false) continue;

            $dbItem = $dbCart->items()
                ->where('product_variant_id', $item->variant_id)
                ->where('is_gift', false)
                ->first();

            if ($dbItem) {
                $dbItem->increment('quantity', $item->quantity);
            } else {
                $dbCart->items()->create([
                    'product_variant_id' => $item->variant_id,
                    'quantity' => $item->quantity,
                    'is_gift' => false,
                    'promotion_id' => null
                ]);
            }
        }
        // 清空 Session
        Session::forget('cart');

        // 清除快取，確保後續讀取 getContent 時是拿到合併後的最新資料
        $this->cachedContent = null;
        $this->cachedVariants = null;
    }

    // 取得總數量 (用於 Navbar 顯示)
    public function count()
    {
        $content = $this->getContent();
        return $content->sum('quantity');
    }

    // 計算小計 (已包含商品層級的直接折扣，但不包含全館滿額折扣與優惠券)
    public function subtotal()
    {
        return (int) $this->getCartDetails()->sum('subtotal');
    }

    // 計算全館/滿額折扣金額
    public function promoDiscount()
    {
        return $this->getAppliedPromotions()->sum('discount_amount');
    }

    // 計算全館/滿額折扣金額 (Old name for compatibility)
    public function promotionDiscountAmount()
    {
        return $this->promoDiscount();
    }


    // 取得目前套用的優惠券
    public function getCoupon()
    {
        $code = Session::get('coupon_code');
        if (!$code) return null;

        $coupon = Coupon::where('code', $code)->first();

        // 再次驗證有效性 (防止過期後還被使用)
        if (!$coupon || !$coupon->isValid($this->subtotal())) {
            Session::forget('coupon_code');
            return null;
        }

        return $coupon;
    }

    // 計算優惠券折扣金額
    public function couponDiscount()
    {
        $coupon = $this->getCoupon();
        if (!$coupon) return 0;

        $subtotal = $this->subtotal();

        if ($coupon->type === 'fixed') {
            return min($coupon->value, $subtotal); // 折扣不能超過總額
        } elseif ($coupon->type === 'percent') {
            return round($subtotal * ($coupon->value / 100));
        }

        return 0;
    }

    // 計算總折扣金額 (優惠券 + 活動)
    public function discountAmount()
    {
        return $this->couponDiscount() + $this->promoDiscount();
    }

    // 計算最終總金額
    public function total()
    {
        $subtotal = $this->subtotal();
        $totalDiscount = $this->discountAmount();

        return max(0, $subtotal - $totalDiscount);
    }

    // 套用優惠券
    public function applyCoupon($code)
    {
        $this->resetMemoization();
        $coupon = Coupon::where('code', $code)->first();

        if (!$coupon) {
            throw new \Exception('優惠碼不存在');
        }

        if (!$coupon->isValid($this->subtotal())) {
            throw new \Exception('優惠碼無效或未達低消');
        }

        Session::put('coupon_code', $code);
    }

    // 移除優惠券
    public function removeCoupon()
    {
        Session::forget('coupon_code');
    }

    /**
     * 檢查特定活動的贈品額度是否超標 (動態計算)
     */
    protected function checkQuotaBeforeAdd($promotionId, $variantId, $quantityToAdd)
    {
        $promo = Promotion::with('gifts')->find($promotionId);
        if (!$promo) {
            throw new Exception("活動不存在");
        }

        $gift = $promo->gifts->firstWhere('id', $variantId);
        if (!$gift) {
            throw new Exception("贈品未加入此活動");
        }

        $weight = (float) $gift->pivot->gift_weight; // e.g. 1500元/份
        $additionalUsed = $weight * $quantityToAdd;  // 新增消耗

        // 1. 計算目前已用quota (購物車內此promo的所有贈品)
        $usedQuota = 0;
        $content = $this->getContent();
        foreach ($content->where('promotion_id', $promotionId)->where('is_gift', true) as $item) {
            $usedGift = $promo->gifts->firstWhere('id', $item->variant_id);
            $usedWeight = $usedGift ? (float) $usedGift->pivot->gift_weight : 1.0;
            $usedQuota += $usedWeight * $item->quantity;
        }

        // 2. 總quota限制
        $totalQuota = $promo->gift_quota > 0 ? (float) $promo->gift_quota : PHP_FLOAT_MAX;

        // 3. 檢查超標
        $newTotal = $usedQuota + $additionalUsed;
        if ($newTotal > $totalQuota) {
            $over = $newTotal - $totalQuota;
            $unit = $promo->quota_unit === 'currency' ? '元' : '份';
            throw new Exception("贈品總額超出限制！超額 {$over} {$unit}");
        }
    }

    /**
     * 清理無效的贈品 (門檻未達、過期、超額)
     */
    public function cleanupGifts()
    {
        $content = $this->getContent();
        $gifts = $content->where('is_gift', true);
        if ($gifts->isEmpty()) return;

        // 獨立計算raw subtotal
        $rawSubtotal = $this->getRawSubtotal();

        // 2. 僅對每個gift檢查其promo資格 (不全掃)
        foreach ($gifts as $gift) {
            $promo = Promotion::find($gift->promotion_id);
            if (!$promo || !$promo->isValid()) {
                $this->remove($gift->variant_id, true, $gift->promotion_id);
                continue;
            }

            // 3. 檢查門檻 (模擬單promo qualifying subtotal)
            $mockDetails = collect([$gift]); // 最小mock
            $qualifyingSubtotal = $promo->getQualifyingSubtotal($content->reject(fn($i) => $i->is_gift)); // 只正常商品

            if ($qualifyingSubtotal < $promo->min_threshold) {
                $this->remove($gift->variant_id, true, $gift->promotion_id);
                continue;
            }

            // 4. 檢查quota超標 (用getCartPromotions quota邏輯)
            if ($promo->gift_quota > 0) { // gift_quota=0視為無限/禁用
                $applied = $this->discountService->getCartPromotions($content); // 安全：僅quota用，不嵌套cleanup
                $promoData = $applied->firstWhere('id', $promo->id);
                if ($promoData && $promoData['used_quota'] > $promoData['gift_quota']) {
                    $this->remove($gift->variant_id, true, $gift->promotion_id);
                }
            }
        }
    }

    /**
     * 計算純購買商品的小計 (不含贈品)
     */
    public function getRawSubtotal()
    {
        $content = $this->cachedContent ?? $this->getContent();
        if ($content->isEmpty()) return 0;

        $variants = $this->cachedVariants ?? ProductVariant::whereIn('id', $content->pluck('variant_id'))
            ->with(['product'])
            ->get()
            ->keyBy('id');

        return $content->sum(function ($item) use ($variants) {
            if ($item->is_gift) return 0;
            $v = $variants->get($item->variant_id);
            if (!$v) return 0;
            return $this->discountService->getDiscountedPrice($v->product, $v->price) * $item->quantity;
        });
    }

    /**
     * 新增：檢查單promo贈品資格 (獨立，避免循環)
     */
    protected function stillEligibleForGift(Promotion $promo, float $subtotal, ?ProductVariant $variant = null)
    {
        if ($promo->type !== 'threshold_gift') return false;
        if ($subtotal < $promo->min_threshold) return false;

        $applied = $this->getAppliedPromotions();
        $promoData = $applied->firstWhere('id', $promo->id);
        if (!$promoData || $promoData['gift_quota'] <= 0) {
            return false;
        }
        // quota檢查 (簡化)
        return $promoData['used_quota'] <= $promoData['gift_quota'];
    }


    /**
     * 清空購物車中所有的贈品
     */
    public function clearAllGifts()
    {
        $this->resetMemoization();

        if (Auth::check()) {
            $cart = Auth::user()->cart;
            if ($cart) {
                $cart->items()->where('is_gift', true)->delete();
            }
        } else {
            $cart = Session::get('cart', []);
            $newCart = [];
            foreach ($cart as $key => $item) {
                if (!isset($item['is_gift']) || !$item['is_gift']) {
                    $newCart[$key] = $item;
                }
            }
            Session::put('cart', $newCart);
        }
        $this->cachedContent = null;
        $this->cachedVariants = null;
    }
}
