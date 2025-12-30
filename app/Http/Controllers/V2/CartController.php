<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Inertia\Inertia;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    // 顯示頁面 (Inertia)
    public function index()
    {
        $cartItems = $this->cartService->getCartDetails();
        $subtotal = $this->cartService->subtotal();
        $discount = $this->cartService->discountAmount();
        $total = $this->cartService->total();
        $coupon = $this->cartService->getCoupon();

        return Inertia::render('Shop/Cart', [
            'cartItems' => $cartItems,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => $total,
            'appliedCoupon' => $coupon ? $coupon->code : null,
        ]);
    }

    // API: 取得數量 (給 Navbar 紅點用)
    public function count()
    {
        return response()->json([
            'count' => $this->cartService->count()
        ]);
    }

    // API: 加入 (Return JSON)
    public function add(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $variant = ProductVariant::find($request->variant_id);

        // API 錯誤回傳 400 狀態碼
        if ($variant->stock < $request->quantity) {
            return response()->json(['success' => false, 'message' => '庫存不足'], 400);
        }

        $this->cartService->add($request->variant_id, $request->quantity);

        return response()->json([
            'success' => true,
            'message' => '已加入購物車',
            'cartCount' => $this->cartService->count(),
        ]);
    }

    // API: 更新 (Return JSON)
    public function update(Request $request)
    {
        $request->validate([
            'variant_id' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        $variant = ProductVariant::find($request->variant_id);

        if (!$variant || $variant->stock < $request->quantity) {
            return response()->json(['success' => false, 'message' => '庫存不足'], 400);
        }

        $this->cartService->update($request->variant_id, $request->quantity);

        // 回傳前端需要的最新計算數據
        $itemSubtotal = $variant->price * $request->quantity;

        return response()->json([
            'success' => true,
            'itemSubtotal' => number_format($itemSubtotal),
            'subtotal' => number_format($this->cartService->subtotal()),
            'discount' => number_format($this->cartService->discountAmount()),
            'total' => number_format($this->cartService->total()),
            'cartCount' => $this->cartService->count(),
        ]);
    }

    // API: 移除 (Return JSON)
    public function remove(Request $request)
    {
        $this->cartService->remove($request->variant_id);

        return response()->json([
            'success' => true,
            'subtotal' => number_format($this->cartService->subtotal()),
            'discount' => number_format($this->cartService->discountAmount()),
            'total' => number_format($this->cartService->total()),
            'cartCount' => $this->cartService->count(),
        ]);
    }

    // API: 優惠券
    public function applyCoupon(Request $request)
    {
        $request->validate(['code' => 'required']);

        try {
            $this->cartService->applyCoupon($request->code);

            return response()->json([
                'success' => true,
                'message' => '優惠券套用成功！',
                'subtotal' => number_format($this->cartService->subtotal()),
                'discount' => number_format($this->cartService->discountAmount()),
                'total' => number_format($this->cartService->total()),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 400);
        }
    }

    // API: 移除優惠券
    public function removeCoupon()
    {
        $this->cartService->removeCoupon();

        return response()->json([
            'success' => true,
            'subtotal' => number_format($this->cartService->subtotal()),
            'discount' => 0,
            'total' => number_format($this->cartService->total()),
        ]);
    }
}
