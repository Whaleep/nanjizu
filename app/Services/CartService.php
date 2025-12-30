<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Models\Coupon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartService
{
    // 取得目前的購物車清單 (標準化為 [variant_id => quantity])
    protected function getContent()
    {
        // 模式 A: 已登入 -> 讀 DB
        if (Auth::check()) {
            $cart = Auth::user()->cart;
            if (!$cart) return [];

            return $cart->items->pluck('quantity', 'product_variant_id')->toArray();
        }

        // 模式 B: 未登入 -> 讀 Session
        return Session::get('cart', []);
    }

    // 取得購物車內容 (結合資料庫最新資訊)
    public function getCartDetails()
    {
        $content = $this->getContent();

        if (empty($content)) {
            return collect([]);
        }

        // 從資料庫撈取規格與商品資訊
        $variants = ProductVariant::with('product')
            ->whereIn('id', array_keys($content))
            ->get();

        // 組合資料，計算小計
        return $variants->map(function ($variant) use ($content) {
            $quantity = $content[$variant->id];
            // 確保購買數量不超過庫存
            $quantity = min($quantity, $variant->stock);

            return (object) [
                'variant_id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'image' => $variant->product->image,
                'price' => $variant->price,
                'stock' => $variant->stock,
                'quantity' => $quantity,
                'subtotal' => $variant->price * $quantity,
            ];
        });
    }

    // 加入購物車
    public function add($variantId, $quantity)
    {
        if (Auth::check()) {
            // DB 模式
            $user = Auth::user();
            $cart = $user->cart ?? $user->cart()->create();

            $item = $cart->items()->where('product_variant_id', $variantId)->first();
            if ($item) {
                $item->increment('quantity', $quantity);
            } else {
                $cart->items()->create([
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity
                ]);
            }
        } else {
            // Session 模式
            $cart = Session::get('cart', []);
            if (isset($cart[$variantId])) {
                $cart[$variantId] += $quantity;
            } else {
                $cart[$variantId] = $quantity;
            }
            Session::put('cart', $cart);
        }
    }

    // 更新數量
    public function update($variantId, $quantity)
    {
        if ($quantity <= 0) {
            $this->remove($variantId);
            return;
        }

        if (Auth::check()) {
            $user = Auth::user();
            if ($user->cart) {
                $user->cart->items()
                    ->where('product_variant_id', $variantId)
                    ->update(['quantity' => $quantity]);
            }
        } else {
            $cart = Session::get('cart', []);
            $cart[$variantId] = $quantity;
            Session::put('cart', $cart);
        }
    }

    // 移除商品
    public function remove($variantId)
    {
        if (Auth::check()) {
            $user = Auth::user();
            if ($user->cart) {
                $user->cart->items()->where('product_variant_id', $variantId)->delete();
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

    // 合併購物車 (登入時呼叫)
    public function mergeSessionToDb()
    {
        $sessionCart = Session::get('cart', []);

        if (empty($sessionCart)) return;

        $user = Auth::user();
        // 確保 User 有購物車
        $dbCart = $user->cart ?? $user->cart()->create();

        foreach ($sessionCart as $variantId => $quantity) {
            $dbItem = $dbCart->items()->where('product_variant_id', $variantId)->first();

            if ($dbItem) {
                // 如果 DB 也有，數量相加
                $dbItem->increment('quantity', $quantity);
            } else {
                // 如果 DB 沒有，新增
                $dbCart->items()->create([
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity
                ]);
            }
        }

        // 合併完成，清空 Session
        Session::forget('cart');
    }

    // 取得總數量 (用於 Navbar 顯示)
    public function count()
    {
        if (Auth::check()) {
            return Auth::user()->cart?->items->sum('quantity') ?? 0;
        }
        return array_sum(Session::get('cart', []));
    }

    // 計算小計 (不含折扣)
    public function subtotal()
    {
        return (int) $this->getCartDetails()->sum('subtotal');
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

    // 計算折扣金額
    public function discountAmount()
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

    // 計算最終總金額
    public function total()
    {
        return max(0, $this->subtotal() - $this->discountAmount());
    }

    // 套用優惠券
    public function applyCoupon($code)
    {
        $coupon = Coupon::where('code', $code)->first();

        // --- Debug Start ---
        // dd([
        //    'coupon' => $coupon,
        //    'now' => now(),
        //    'subtotal' => $this->subtotal(),
        //    'isValid' => $coupon ? $coupon->isValid($this->subtotal()) : 'no coupon'
        // ]);
        // --- Debug End ---

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
}
