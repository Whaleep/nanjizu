<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use App\Services\LineBotService;
use App\Services\ECPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    // V2: 結帳頁面
    public function indexV2()
    {
        $cartItems = $this->cartService->getCartDetails();

        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.index'); // 導回商店
        }

        $total = $this->cartService->total();

        return Inertia::render('Shop/Checkout', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    // V2: 成功頁面
    public function successV2($id)
    {
        $order = Order::findOrFail($id);
        return Inertia::render('Shop/Success', [
            'order' => $order
        ]);
    }

    // 顯示結帳頁面
    public function index()
    {
        $cartItems = $this->cartService->getCartDetails();

        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.index')->with('error', '購物車是空的');
        }

        $total = $this->cartService->total();
        return view('shop.checkout', compact('cartItems', 'total'));
    }

    // 處理下單
    public function store(Request $request, ECPayService $ecpayService, LineBotService $lineBot)
    {
        // 1. 驗證資料
        $request->validate([
            'customer_name' => 'required',
            'customer_phone' => 'required',
            'customer_address' => 'required', // 即使是面交也可填寫 "店面自取"
            'payment_method' => 'required|in:cod,bank_transfer,ecpay',
        ]);

        $cartItems = $this->cartService->getCartDetails();

        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.index');
        }

        // 2. 開始資料庫交易 (Transaction)
        // 確保 "建立訂單" 和 "扣庫存" 同時成功，否則全部回滾
        try {
            DB::beginTransaction();

            // 再次檢查庫存
            foreach ($cartItems as $item) {
                $variant = ProductVariant::lockForUpdate()->find($item->variant_id); // 鎖定該行資料，防止併發問題
                if (!$variant || $variant->stock < $item->quantity) {
                    throw new \Exception("商品 {$item->product_name} - {$item->variant_name} 庫存不足，請返回購物車修改。");
                }
            }

            // A. 建立訂單主檔
            $order = Order::create([
                'order_number' => 'ORD' . date('Ymd') . strtoupper(Str::random(5)),
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'customer_address' => $request->customer_address,
                'total_amount' => $this->cartService->total(),
                'payment_method' => $request->payment_method,
                'notes' => $request->notes,
                'status' => 'pending',
            ]);

            // B. 建立訂單明細 並 扣庫存
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

                // 扣庫存
                $variant = ProductVariant::find($item->variant_id);
                $variant->decrement('stock', $item->quantity);
            }

            // 提交交易
            DB::commit();

            // 3. 清空購物車
            $this->cartService->clear();

            // 發送 Line 通知 ===
            try {
                $lineBot->sendOrderNotification($order);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Line通知失敗');
            }

            // 如果是綠界付款，產生表單並回傳
            if ($request->payment_method === 'ecpay') {
                return $ecpayService->generateAutoSubmitForm($order);
            }

            // 4. 跳轉到感謝頁面
            return redirect()->route('checkout.success', $order->id);
        } catch (\Exception $e) {
            DB::rollBack(); // 發生錯誤，復原所有動作
            return back()->with('error', '下單失敗：' . $e->getMessage())->withInput();
        }
    }

    public function success($id)
    {
        $order = Order::findOrFail($id);
        return view('shop.success', compact('order'));
    }
}
