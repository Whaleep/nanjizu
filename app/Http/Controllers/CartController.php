<?php

namespace App\Http\Controllers;

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

    // V2: 購物車頁面
    public function indexV2()
    {
        $cartItems = $this->cartService->getCartDetails();
        $total = $this->cartService->total();

        return Inertia::render('Shop/Cart', [
            'cartItems' => $cartItems,
            'total' => $total,
        ]);
    }

    // 顯示購物車頁面
    public function index()
    {
        $cartItems = $this->cartService->getCartDetails();
        $total = $this->cartService->total();

        return view('shop.cart', compact('cartItems', 'total'));
    }

    // 加入購物車 (POST)
    public function add(Request $request)
    {
        $request->validate([
            'variant_id' => 'required|exists:product_variants,id',
            'quantity' => 'required|integer|min:1',
        ]);

        // 檢查庫存 (簡單檢查)
        $variant = ProductVariant::find($request->variant_id);
        if ($variant->stock < $request->quantity) {
            return back()->with('error', '庫存不足！');
        }

        $this->cartService->add($request->variant_id, $request->quantity);

        // 新增：如果是 AJAX 請求 (wantsJson)，回傳 JSON
        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => '已加入購物車',
                'cartCount' => $this->cartService->count(), // 回傳最新數量
            ]);
        }

        return back()->with('success', '已加入購物車！');
    }

    // 更新數量 (AJAX 支援)
    public function update(Request $request)
    {
        $request->validate([
            'variant_id' => 'required',
            'quantity' => 'required|integer|min:1',
        ]);

        $variant = ProductVariant::find($request->variant_id);
        if (!$variant || $variant->stock < $request->quantity) {
            if ($request->wantsJson()) {
                return response()->json(['success' => false, 'message' => '庫存不足'], 400);
            }
            return back()->with('error', '庫存不足');
        }

        $this->cartService->update($request->variant_id, $request->quantity);

        // 如果是 AJAX，回傳最新數據以便前端更新 DOM
        if ($request->wantsJson()) {
            // 重新計算這一個項目的小計
            $itemSubtotal = $variant->price * $request->quantity;

            return response()->json([
                'success' => true,
                'itemSubtotal' => number_format($itemSubtotal), // 單項小計
                'total' => number_format($this->cartService->total()), // 總金額
                'cartCount' => $this->cartService->count(), // 購物車總數
            ]);
        }

        return back()->with('success', '購物車已更新');
    }

    // 移除商品 (AJAX 支援)
    public function remove(Request $request)
    {
        $this->cartService->remove($request->variant_id);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'total' => number_format($this->cartService->total()),
                'cartCount' => $this->cartService->count(),
            ]);
        }

        return back()->with('success', '商品已移除');
    }
}
