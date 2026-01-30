<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Services\ShopService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class WishlistController extends Controller
{
    // 顯示我的收藏頁面
    public function index()
    {
        $query = Auth::user()->wishlists();

        // 使用 ShopService 的優化邏輯
        $shopService = app(ShopService::class);
        $shopService->eagerLoadProductRelations($query);

        $products = $query->latest('wishlists.created_at')
            ->paginate(12);

        $shopService->fixVariantProductRelation($products->getCollection());

        return Inertia::render('Dashboard/Wishlist', [
            'products' => $products
        ]);
    }

    // 切換收藏 (AJAX)
    public function toggle(Request $request)
    {
        $request->validate(['product_id' => 'required|exists:products,id']);

        $user = Auth::user();
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
