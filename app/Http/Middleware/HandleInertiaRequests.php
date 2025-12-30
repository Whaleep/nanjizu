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
            'cartCount' => array_sum(session('cart', [])),

            // 4. 商店選單 (共用導航)
            'menuItems' => \App\Models\ShopMenu::orderBy('sort_order')->get(),

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
