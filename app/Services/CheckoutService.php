<?php

namespace App\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;
use Illuminate\Support\Facades\Mail;
use App\Mail\OrderCreated;

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
        $productsTotal = $this->cartService->total();
        $discount = $this->cartService->discountAmount();
        $coupon = $this->cartService->getCoupon();

        // 計算運費
        $shippingMethod = ShippingMethod::find($data['shipping_method_id']);
        $shippingFee = $shippingMethod->fee;

        // 檢查免運門檻
        if ($shippingMethod->free_shipping_threshold && $productsTotal >= $shippingMethod->free_shipping_threshold) {
            $shippingFee = 0;
        }

        // 計算最終總金額 (商品 + 運費)
        $finalTotal = $productsTotal + $shippingFee;

        // 準備訂單資料
        $orderAttributes = [
            'order_number' => 'ORD' . date('Ymd') . strtoupper(Str::random(5)),
            'user_id' => auth()->id(),
            'customer_name' => $data['customer_name'],
            'customer_phone' => $data['customer_phone'],
            'customer_email' => $data['customer_email'] ?? null,
            'customer_address' => $data['customer_address'],
            'notes' => $data['notes'] ?? null,
            'total_amount' => $finalTotal,
            'discount_amount' => $discount,
            'coupon_code' => $coupon ? $coupon->code : null,
            'shipping_method_name' => $shippingMethod->name,
            'shipping_fee' => $shippingFee,
            'payment_method' => $data['payment_method'],
            'status' => 'pending',
        ];

        // 開始資料庫交易
        $order = DB::transaction(function () use ($orderAttributes, $cartItems, $coupon) {

            // 檢查庫存
            foreach ($cartItems as $item) {
                $variant = ProductVariant::lockForUpdate()->find($item->variant_id);

                if (!$variant || $variant->stock < $item->quantity) {
                    throw new Exception("商品 {$item->product_name} - {$item->variant_name} 庫存不足。");
                }
            }

            // 建立訂單
            $order = Order::create($orderAttributes);

            // 建立明細並扣庫存
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

            // 增加優惠券已使用次數
            if ($coupon) {
                $coupon->increment('used_count');
            }

            // 清空購物車
            $this->cartService->clear();

            return $order;
        });

        // DB 交易成功後才會發送通知
        // 發送 Line 通知給賣家
        try {
            $this->lineBotService->sendOrderNotification($order);
        } catch (Exception $e) {
            \Illuminate\Support\Facades\Log::error('Line 通知失敗: ' . $e->getMessage());
        }

        // 發送 Email 給買家
        if ($order->customer_email) {
            try {
                Mail::to($order->customer_email)->send(new OrderCreated($order));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('郵件發送失敗: ' . $e->getMessage());
            }
        }

        return $order;
    }
}
