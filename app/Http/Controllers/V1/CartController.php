<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\CartService;
use App\Models\ProductVariant;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    // 顯示頁面 (Blade)

    public function index()
    {
        $cartItems = $this->cartService->getCartDetails();
        $total = $this->cartService->total();
        return view('shop.cart', compact('cartItems', 'total'));
    }

    // 加入 (Form Post -> Redirect)
    public function add(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            // 呼叫 Service，如果有問題會 throw Exception
            $this->cartService->add($request->variant_id, $request->quantity);
            // 成功回傳
            return back()->with('success', '已加入購物車！');
        } catch (\Exception $e) {
            // 失敗捕捉
            return back()->with('error', $e->getMessage());
        }

        // 檢查庫存 (簡單檢查)
        // $variant = ProductVariant::find($request->variant_id);
        // if ($variant->stock < $request->quantity) {
        //     return back()->with('error', '庫存不足！');
        // }

        // $this->cartService->add($request->variant_id, $request->quantity);

        // return back()->with('success', '已加入購物車！');
    }

    // 更新 (Form Post -> Redirect)
    public function update(Request $request)
    {
        $request->validate([
            'variant_id' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        try {
            // 回傳成功資料
            $this->cartService->update($request->variant_id, $request->quantity);
            return back()->with('success', '購物車已更新');
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    // 移除 (Form Post -> Redirect)
    public function remove(Request $request)
    {
        $this->cartService->remove($request->variant_id);
        return back()->with('success', '商品已移除');
    }
}
