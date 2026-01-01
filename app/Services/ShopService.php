<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ShopCategory;
use App\Models\ProductTag;
use App\Models\Review;
use Illuminate\Http\Request;

class ShopService
{
    /**
     * 取得商店首頁/搜尋頁所需的資料
     */
    public function getIndexData(Request $request)
    {
        $query = $request->input('q');
        $tagSlug = $request->input('tag');

        // 1. 建立商品查詢
        $productsQuery = Product::where('is_active', true)
            ->with(['variants', 'category']); // 預載關聯

        // 搜尋邏輯
        if ($query) {
            $productsQuery->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                    ->orWhere('description', 'like', "%{$query}%");
            });
        }

        // 標籤篩選邏輯
        if ($tagSlug) {
            $productsQuery->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('slug', $tagSlug);
            });
        }

        // 取得分頁資料
        $products = $productsQuery->latest()->paginate(12)->withQueryString();

        // 2. 取得最上層分類 (用於首頁分類列表)
        $categories = ShopCategory::whereNull('parent_id')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        return [
            'products' => $products,
            'categories' => $categories,
            'filters' => [
                'q' => $query,
                'tag' => $tagSlug,
            ],
            'isFiltered' => ($query || $tagSlug), // 標記是否有進行篩選
        ];
    }

    /**
     * 取得單一分類頁所需的資料
     */
    public function getCategoryData($slug, Request $request)
    {
        $category = ShopCategory::where('slug', $slug)
            ->where('is_visible', true)
            ->firstOrFail();

        // 取得子分類
        $subcategories = $category->children()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        // 取得商品 (包含所有子孫分類)
        $categoryIds = $category->getAllChildrenIds();

        $query = Product::whereIn('shop_category_id', $categoryIds)
            ->where('is_active', true)
            ->with('variants');

        // 標籤篩選
        if ($request->has('tag')) {
            $tagSlug = $request->input('tag');
            $query->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('slug', $tagSlug);
            });
        }

        $products = $query->latest()->paginate(12)->withQueryString();

        // 取得該分類下相關的標籤 (用於前端篩選按鈕)
        $tags = ProductTag::whereHas('products', function ($q) use ($categoryIds) {
            $q->whereIn('shop_category_id', $categoryIds);
        })->get();

        return [
            'category' => $category,
            'subcategories' => $subcategories,
            'products' => $products,
            'tags' => $tags,
            'currentTag' => $request->input('tag'),
        ];
    }

    /**
     * 取得商品詳情頁所需的資料
     */
    public function getProductDetailData($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['variants', 'category'])
            ->with(['reviews' => function ($q) {    // 載入評價 (含使用者名稱)
                $q->with('user:id,name')->latest(); // 只抓 user 的 id 和 name，保護隱私
            }])
            ->firstOrFail();

        // 附加屬性 (平均分、總數) 讓前端可以用
        $product->append(['average_rating', 'review_count']);

        $key = 'viewed_product_' . $product->id;
        if (!session()->has($key)) {
            $product->increment('view_count');
            session()->put($key, true);
        }

        // 撈取同分類的其他商品 (關聯商品)
        $relatedProducts = Product::where('shop_category_id', $product->shop_category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->with('variants')
            ->inRandomOrder()
            ->take(4)
            ->get();

        // 檢查使用者是否已收藏此商品
        $isWishlisted = false;
        if (auth()->check()) {
            $isWishlisted = auth()->user()->wishlists()->where('product_id', $product->id)->exists();
        }

        // 評價權限嚴謹判斷
        $user = auth()->user();
        $canReview = false;
        $reviewStatus = 'guest'; // guest, bought, reviewed, no-purchase

        if ($user) {
            // 1. 檢查是否已評價
            $hasReviewed = Review::where('user_id', $user->id)
                ->where('product_id', $product->id)
                ->exists();

            if ($hasReviewed) {
                $reviewStatus = 'reviewed';
            } else {
                // 2. 檢查是否購買過且完成 (邏輯同 ReviewController)
                $hasPurchased = $user->orders()
                    ->where('status', 'completed')
                    ->whereHas('items', function ($query) use ($product) {
                        $query->whereHas('variant', function ($q) use ($product) {
                            $q->where('product_id', $product->id);
                        });
                    })
                    ->exists();

                if ($hasPurchased) {
                    $canReview = true;
                    $reviewStatus = 'bought';
                } else {
                    $reviewStatus = 'no-purchase';
                }
            }
        }

        return [
            'product' => $product,
            'relatedProducts' => $relatedProducts,
            'isWishlisted' => $isWishlisted,
            'canReview' => $canReview,
            'reviewStatus' => $reviewStatus, // 傳遞詳細狀態給前端
        ];
    }
}
