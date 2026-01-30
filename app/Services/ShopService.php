<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ShopCategory;
use App\Models\ProductTag;
use App\Models\Promotion;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ShopService
{
    protected $discountService;

    public function __construct(DiscountService $discountService)
    {
        $this->discountService = $discountService;
    }
    /**
     * 取得商店首頁/搜尋頁所需的資料
     */
    public function getIndexData(Request $request)
    {
        $query = $request->input('q');
        $tagSlug = $request->input('tag');
        $promotionId = $request->input('promotion');

        // 1. 建立商品查詢
        $productsQuery = Product::where('is_active', true);
        $this->eagerLoadProductRelations($productsQuery);

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

        // 特惠活動篩選邏輯
        $currentPromotion = null;
        if ($promotionId) {
            $currentPromotion = Promotion::with(['productTags', 'categories', 'products'])
                ->where('id', $promotionId)
                ->where('is_active', true)
                ->first();

            if ($currentPromotion) {
                // 根據活動範圍進行篩選
                if ($currentPromotion->scope === 'tag') {
                    // 找出該活動包含的所有標籤 ID
                    $tagIds = $currentPromotion->productTags->pluck('id');
                    $productsQuery->whereHas('productTags', function ($q) use ($tagIds) {
                        $q->whereIn('product_tags.id', $tagIds);
                    });
                } elseif ($currentPromotion->scope === 'category') {
                    // 找出該活動包含的所有分類 ID
                    // (如果需要包含子分類，這裡可能需要遞迴抓取 children ids，視需求而定)
                    // 這裡暫時只抓第一層選定的分類
                    $catIds = $currentPromotion->categories->pluck('id');
                    $productsQuery->whereIn('shop_category_id', $catIds);
                } elseif ($currentPromotion->scope === 'product') {
                    // 找出該活動包含的所有商品 ID
                    $prodIds = $currentPromotion->products->pluck('id');
                    $productsQuery->whereIn('id', $prodIds);
                }
                // 如果 scope 是 'all'，則不加任何條件 (全館商品)
            }
        }

        // 取得分頁資料
        $products = $productsQuery->latest()->paginate(12)->withQueryString();
        $this->fixVariantProductRelation($products->getCollection());

        // 掛載活動標籤 (讓列表頁也能顯示這商品還有什麼其他活動)
        $this->attachPromotionsToProducts($products->getCollection());

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
                'promotion' => $promotionId,
            ],
            'currentPromotion' => $currentPromotion,
            'isFiltered' => ($query || $tagSlug || $promotionId), // 標記是否有進行篩選
        ];
    }

    /**
     * 輔助方法：為商品集合附加活動標籤
     */
    protected function attachPromotionsToProducts($products)
    {
        // 取得所有活躍活動 (使用 Cache)
        $promos = Cache::tags(['promotions', 'threshold'])->remember(
            'active_threshold_promos_' . now()->format('Y-m-d-H'),
            3600,
            function () {
                return Promotion::where('is_active', true)
                    ->whereIn('type', ['threshold_cart', 'threshold_product'])
                    ->where(function ($q) {
                        $q->whereNull('start_at')->orWhere('start_at', '<=', now());
                    })
                    ->where(function ($q) {
                        $q->whereNull('end_at')->orWhere('end_at', '>=', now());
                    })
                    ->with(['categories', 'productTags', 'products'])
                    ->orderBy('priority', 'desc')
                    ->get();
            }
        );

        foreach ($products as $product) {
            $matched = [];
            foreach ($promos as $promo) {
                if ($promo->appliesTo($product) && $promo->show_badge) {
                    $matched[] = [
                        'id' => $promo->id,
                        'name' => $promo->name,
                        'action_type' => $promo->action_type,
                        'tooltip' => $promo->description ?: ($promo->action_type === 'gift' ? '滿額好禮' : '滿額折扣'),
                        'link' => "/shop?promotion=" . $promo->id
                    ];
                }
            }
            // 使用 setAttribute 掛載虛擬屬性，這樣前端 JSON 裡就會有
            $product->setAttribute('active_promotions', $matched);
        }
    }

    /**
     * 取得單一分類頁所需的資料
     */
    public function getCategoryData($slug, Request $request)
    {
        // 不過濾 is_visible 這樣即使分類被隱藏，只要有連結 (例如從商品點進來) 依然可以瀏覽
        $category = ShopCategory::where('slug', $slug)
            ->firstOrFail();

        $category->append('breadcrumb_path');

        // 取得子分類，只顯示 visible 的
        $subcategories = $category->children()
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        // 取得商品 (包含所有子孫分類)
        $categoryIds = $category->getAllChildrenIds();

        $query = Product::whereIn('shop_category_id', $categoryIds)
            ->where('is_active', true);
        $this->eagerLoadProductRelations($query);

        // 標籤篩選
        if ($request->has('tag')) {
            $tagSlug = $request->input('tag');
            $query->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('slug', $tagSlug);
            });
        }

        $products = $query->latest()->paginate(12)->withQueryString();
        $this->fixVariantProductRelation($products->getCollection());
        $this->attachPromotionsToProducts($products->getCollection());

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
        $productQuery = Product::where('slug', $slug)
            ->where('is_active', true);

        $this->eagerLoadProductRelations($productQuery);

        // 載入評價 (含使用者名稱)，只抓 user 的 id 和 name，保護隱私
        $product = $productQuery->with(['reviews' => function ($q) {
            $q->with('user:id,name')->latest();
        }])
            ->firstOrFail();

        $this->fixVariantProductRelation(collect([$product]));
        $this->attachPromotionsToProducts(collect([$product]));

        // 附加屬性 (平均分、總數) 讓前端可以用
        $product->append(['average_rating', 'review_count']);

        // 處理麵包屑 (防止無限遞迴)
        if ($product->category) {
            // 方式一：附加到 product，讓前端用 product.breadcrumb_path
            $product->setRelation('category', $product->category);
            $product->category->append('breadcrumb_path');

            // 或者：直接在 product 上附加麵包屑
            // $product->append('category_breadcrumb');
        }

        // 更新瀏覽次數（簡易防重複）
        $key = 'viewed_product_' . $product->id;
        if (!session()->has($key)) {
            $product->increment('view_count');
            session()->put($key, true);
        }

        // 撈取同分類的其他商品 (關聯商品)
        $relatedQuery = Product::where('shop_category_id', $product->shop_category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true);

        $this->eagerLoadProductRelations($relatedQuery);

        $relatedProducts = $relatedQuery->inRandomOrder()
            ->take(4)
            ->get();

        $this->fixVariantProductRelation($relatedProducts);
        $this->attachPromotionsToProducts($relatedProducts);

        // 檢查使用者是否已收藏此商品
        $isWishlisted = false;
        if (Auth::check()) {
            $isWishlisted = Auth::user()->wishlists()->where('product_id', $product->id)->exists();
        }

        // 評價權限嚴謹判斷
        $user = Auth::user();
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
            'reviewStatus' => $reviewStatus,
        ];
    }

    /**
     * 預載入商品相關的通用關聯，避免 N+1
     */
    public function eagerLoadProductRelations($query)
    {
        $query->with(['variants.media', 'category.parent', 'productTags', 'media']);

        if (Auth::check()) {
            $query->withExists(['wishlistedBy as is_wishlisted' => function ($q) {
                $q->where('user_id', Auth::id());
            }]);
        }

        return $query;
    }

    /**
     * 手動建立 Variant 到 Product 的反向關聯，避免在計算折扣時觸發反向查詢
     */
    public function fixVariantProductRelation($products)
    {
        $products->each(function ($product) {
            // 安全檢查：確保這是 Product 模型且已載入 variants 關聯
            if ($product instanceof \App\Models\Product && $product->relationLoaded('variants')) {
                $product->variants->each(function ($variant) use ($product) {
                    $variant->setRelation('product', $product);
                });
            }
        });
        return $products;
    }
}
