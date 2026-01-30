<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use App\Models\ShopCategory;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            // 1. 應用程式資訊
            'appName' => config('app.name'),

            // 2. 登入使用者 (如果有)
            'auth' => [
                'user' => $request->user(),
            ],

            // 3. 購物車數量 (Session)
            'cartCount' => collect(session('cart', []))->sum(fn($item) => is_array($item) ? ($item['quantity'] ?? 0) : $item),

            // 4. 商店選單 (共用導航)
            'menuItems' => function () {
                return \App\Models\ShopMenu::orderBy('sort_order')
                    ->with(['category.children', 'tag', 'promotion']) 
                    ->get()
                    ->map(function ($menu) {
                        // 1. 生成連結
                        $link = '#';
                        if ($menu->type === 'category' && $menu->category) {
                            $link = route('shop.category', $menu->category->slug);
                        } elseif ($menu->type === 'tag' && $menu->tag) {
                            $link = route('shop.index', ['tag' => $menu->tag->slug]);
                        } elseif ($menu->type === 'promotion') {
                            $link = '/shop?promotion=' . $menu->target_id;
                        } elseif ($menu->type === 'link') {
                            $link = $menu->url;
                        }

                        // 2. 準備子選單 (針對分類)
                        $children = [];
                        if ($menu->type === 'category' && $menu->category) {
                            $children = $menu->category->children
                                ->where('is_visible', true)
                                ->sortBy('sort_order')
                                ->map(fn($child) => [
                                    'id' => $child->id,
                                    'name' => $child->name,
                                    'link' => route('shop.category', $child->slug),
                                ])
                                ->values(); // 重置陣列索引
                        }

                        // 3. 回傳前端友善的結構
                        return [
                            'id' => $menu->id,
                            'name' => $menu->name,
                            'type' => $menu->type,
                            'link' => $link, // 前端直接用這個 href
                            'children' => $children, // 子選單資料
                            // 額外標記：是否為活動 (可用於前端加 Badge 或變色)
                            'is_promotion' => $menu->type === 'promotion', 
                        ];
                    });
            },

            // 商店側邊欄分類樹 (V2 Shop Sidebar)
            'shopCategories' => fn() => ShopCategory::whereNull('parent_id')
                ->where('is_visible', true)
                ->with(['children' => function ($q) {
                    $q->where('is_visible', true)->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get(),

            // 5. Flash 訊息 (操作成功/失敗提示)
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],

            'csrf_token' => csrf_token(),
        ]);
    }
}
