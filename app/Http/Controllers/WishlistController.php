<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Inertia\Inertia;

class WishlistController extends Controller
{
    // 顯示我的收藏頁面
    public function index()
    {
        $products = auth()->user()->wishlists()
            ->with('variants') // 為了顯示價格
            ->latest('wishlists.created_at')
            ->paginate(12);

        return Inertia::render('Dashboard/Wishlist', [
            'products' => $products
        ]);
    }

    // 切換收藏 (AJAX)
    public function toggle(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $user = auth()->user();
        $productId = $request->product_id;

        // toggle() 是 Laravel 內建的多對多切換方法
        // 如果有就刪除，如果沒有就新增
        $result = $user->wishlists()->toggle($productId);

        $isWishlisted = count($result['attached']) > 0;

        return response()->json([
            'success' => true,
            'is_wishlisted' => $isWishlisted,
            'message' => $isWishlisted ? '已加入收藏' : '已取消收藏'
        ]);
    }
}
