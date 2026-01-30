<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Services\CartService;
use App\Services\CheckoutService;
use App\Services\ECPayService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        // 1. 從 Session 取得選擇的贈品
        $selectedGifts = session('checkout_selected_gifts', []);

        // 2. 取得合併贈品後的購物車內容
        $cartItems = $this->cartService->getCheckoutCartDetails($selectedGifts);

        // 如果購物車是空的，不進行結帳，導向商店首頁
        if ($cartItems->isEmpty()) {
            return redirect()->route('shop.index');
        }

        // 3. 取得金額資訊
        // 注意：這裡要小心，CartService 的 subtotal/total 方法通常是讀取資料庫的 cart
        // 但因為贈品是 $0，所以金額計算不會因為多了贈品而改變，直接呼叫 Service 方法是安全的
        $subtotal = $this->cartService->subtotal();
        $promoDiscount = $this->cartService->promoDiscount(); // 滿額/全館折扣金額
        $couponDiscount = $this->cartService->couponDiscount(); // 優惠券折扣
        $total = $this->cartService->total(); // 最終金額

        // 4. 取得啟用的運送方式
        $shippingMethods = ShippingMethod::where('is_active', true)
            ->orderBy('sort_order')
            ->get();

        // 5. 取得使用者預設資料 (若有登入)
        $user = Auth::user();
        $savedAddress = $user ? [
            'name' => $user->name,
            'phone' => $user->phone, // 假設有這個欄位
            'address' => $user->address, // 假設有這個欄位
            'email' => $user->email,
        ] : null;

        return Inertia::render('Shop/Checkout', [
            'cartItems' => $cartItems, // 這裡包含了通過驗證的贈品
            'summary' => [
                'subtotal' => $subtotal,
                'promo_discount' => $promoDiscount,
                'coupon_discount' => $couponDiscount,
                'total' => $total,
            ],
            'shippingMethods' => $shippingMethods,
            'savedAddress' => $savedAddress,
            'appliedCoupon' => session('coupon_code'), // 讓前端知道有用優惠券
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

        // 加入贈品資訊到資料中
        $validatedData['selected_gifts'] = session('checkout_selected_gifts', []);

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
