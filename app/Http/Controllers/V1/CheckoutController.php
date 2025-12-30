<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ECPayService;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    public function index()
    {
        $cartItems = $this->cartService->getCartDetails();

        if ($cartItems->isEmpty()) {
            return redirect()->route('v1.shop.index')->with('error', '購物車是空的');
        }

        $total = $this->cartService->total();
        return view('shop.checkout', compact('cartItems', 'total'));
    }

    // 處理下單
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

            // 3. 判斷是否需要綠界支付
            if ($order->payment_method === 'ecpay') {
                return $ecpayService->generateAutoSubmitForm($order);
            }

            // 4. 一般付款，跳轉成功頁
            return redirect()->route('v1.checkout.success', $order->id);

        } catch (\Exception $e) {
            return back()->with('error', '下單失敗：' . $e->getMessage())->withInput();
        }
    }

    public function success($id)
    {
        $order = Order::findOrFail($id);
        return view('shop.success', compact('order'));
    }
}
