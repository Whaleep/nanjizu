<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Order;
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

        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.index');
        }

        $total = $this->cartService->total();

        return Inertia::render('Shop/Checkout', [
            'cartItems' => $cartItems,
            'total' => $total,
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
