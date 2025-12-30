<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Support\Facades\Session;

class CartService
{
    // 取得購物車內容 (結合資料庫最新資訊)
    public function getCartDetails()
    {
        $cart = Session::get('cart', []);

        if (empty($cart)) {
            return collect([]);
        }

        // 從資料庫撈取規格與商品資訊
        $variants = ProductVariant::with('product')
            ->whereIn('id', array_keys($cart))
            ->get();

        // 組合資料，計算小計
        return $variants->map(function ($variant) use ($cart) {
            $quantity = $cart[$variant->id];
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
        $cart = Session::get('cart', []);

        // 如果已在車內，則累加
        if (isset($cart[$variantId])) {
            $cart[$variantId] += $quantity;
        } else {
            $cart[$variantId] = $quantity;
        }

        Session::put('cart', $cart);
    }

    // 更新數量
    public function update($variantId, $quantity)
    {
        $cart = Session::get('cart', []);

        if ($quantity > 0) {
            $cart[$variantId] = $quantity;
            Session::put('cart', $cart);
        } else {
            $this->remove($variantId);
        }
    }

    // 移除商品
    public function remove($variantId)
    {
        $cart = Session::get('cart', []);
        unset($cart[$variantId]);
        Session::put('cart', $cart);
    }

    // 清空購物車
    public function clear()
    {
        Session::forget('cart');
    }

    // 取得總金額
    public function total()
    {
        return $this->getCartDetails()->sum('subtotal');
    }

    // 取得總數量 (用於 Navbar 顯示)
    public function count()
    {
        return array_sum(Session::get('cart', []));
    }
}

?>
