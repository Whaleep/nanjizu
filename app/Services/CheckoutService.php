<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class CheckoutService
{
    protected $cartService;
    protected $lineBotService;

    public function __construct(CartService $cartService, LineBotService $lineBotService)
    {
        $this->cartService = $cartService;
        $this->lineBotService = $lineBotService;
    }

    /**
     * 執行下單流程
     * @param array $data 驗證過的表單資料
     * @return Order 建立成功的訂單物件
     * @throws Exception
     */
    public function createOrder(array $data)
    {
        $cartItems = $this->cartService->getCartDetails();

        if ($cartItems->isEmpty()) {
            throw new Exception('購物車是空的');
        }

        // 取得金額與優惠資訊
        $discount = $this->cartService->discountAmount();
        $coupon = $this->cartService->getCoupon();
        $finalTotal = $this->cartService->total();

        // 開始資料庫交易
        return DB::transaction(function () use ($data, $cartItems, $discount, $coupon, $finalTotal) {

            // 1. 檢查庫存並鎖定 (Lock For Update)
            foreach ($cartItems as $item) {
                $variant = ProductVariant::lockForUpdate()->find($item->variant_id);

                if (!$variant || $variant->stock < $item->quantity) {
                    throw new Exception("商品 {$item->product_name} - {$item->variant_name} 庫存不足。");
                }
            }

            // 2. 建立訂單主檔
            $order = Order::create([
                'order_number' => 'ORD' . date('Ymd') . strtoupper(Str::random(5)),
                'user_id' => auth()->id(),
                'customer_name' => $data['customer_name'],
                'customer_phone' => $data['customer_phone'],
                'customer_email' => $data['customer_email'] ?? null,
                'customer_address' => $data['customer_address'],
                'payment_method' => $data['payment_method'],
                'notes' => $data['notes'] ?? null,
                'total_amount' => $finalTotal,
                'discount_amount' => $discount,
                'coupon_code' => $coupon ? $coupon->code : null,
                'status' => 'pending',
            ]);

            // 3. 建立明細並扣庫存
            foreach ($cartItems as $item) {
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_variant_id' => $item->variant_id,
                    'product_name' => $item->product_name,
                    'variant_name' => $item->variant_name,
                    'price' => $item->price,
                    'quantity' => $item->quantity,
                    'subtotal' => $item->subtotal,
                ]);

                // 扣庫存、同時增加商品銷售數
                $variant = ProductVariant::find($item->variant_id);
                $variant->decrement('stock', $item->quantity);
                $variant->product->increment('sold_count', $item->quantity);
            }

            // 4. 增加優惠券使用次數
            if ($coupon) {
                $coupon->increment('used_count');
            }

            // 5. 清空購物車
            $this->cartService->clear();

            // 6. 發送 Line 通知 (放在 Transaction 內或外皆可，這裡放裡面確保流程完整)
            try {
                $this->lineBotService->sendOrderNotification($order);
            } catch (Exception $e) {
                // Line 發送失敗不應影響訂單成立，紀錄 Log 即可
                \Illuminate\Support\Facades\Log::error('Line 通知失敗: ' . $e->getMessage());
            }

            return $order;
        });
    }
}
