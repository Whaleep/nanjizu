<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ECPayService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CheckoutController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    // V2 結帳頁面
    public function index()
    {
        $cartItems = $this->cartService->getCartDetails();

        // 如果購物車是空的，不進行結帳，導向商店首頁
        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.index');
        }

        // 商品小計 (扣除優惠券後，尚未加運費)
        $subtotal = $this->cartService->total();

        // 取得啟用的運送方式
        $shippingMethods = ShippingMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        return Inertia::render('Shop/Checkout', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'shippingMethods' => $shippingMethods,
        ]);
    }

    // V2 下單處理 (注意：這裡的 Route Name 應該要是 checkout.store)
    public function store(Request $request, CheckoutService $checkoutService, ECPayService $ecpayService)
    {
        // 1. 驗證
        $validatedData = $request->validate([
            'customer_name' => 'required',
            'customer_phone' => 'required',
            'customer_email' => 'nullable|email',
            'customer_address' => 'required',
            'payment_method' => 'required|in:cod,bank_transfer,ecpay',
            'notes' => 'nullable',
            'shipping_method_id' => 'required|exists:shipping_methods,id',
        ]);

        try {
            // 2. 呼叫 Service 建立訂單
            $order = $checkoutService->createOrder($validatedData);

            // 3. 綠界支付：直接回傳 HTML，Inertia 前端如果是用標準 Form Submit，瀏覽器會直接渲染並跳轉
            if ($order->payment_method === 'ecpay') {
                return $ecpayService->generateAutoSubmitForm($order);
            }

            // 4. 一般付款：跳轉到 V2 成功頁
            // 使用 to_route 輔助函式，對應 routes/web.php 定義的名稱
            return to_route('checkout.success', $order->id);
        } catch (\Exception $e) {
            // Inertia 會自動處理這裡的錯誤訊息並回傳給前端 form.errors
            return back()->withErrors(['error' => '下單失敗：' . $e->getMessage()]);
        }
    }

    // V2 成功頁面
    public function success($id)
    {
        $order = Order::findOrFail($id);
        return Inertia::render('Shop/Success', [
            'order' => $order
        ]);
    }
}
