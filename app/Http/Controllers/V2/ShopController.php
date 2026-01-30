<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\DiscountService;
use App\Services\ShopService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ShopController extends Controller
{
    protected $shopService;
    protected $discountService;

    public function __construct(ShopService $shopService, DiscountService $discountService)
    {
        $this->shopService = $shopService;
        $this->discountService = $discountService;
    }

    // V2 商店首頁
    public function index(Request $request)
    {
        $data = $this->shopService->getIndexData($request);

        return Inertia::render('Shop/Index', [
            'products' => $data['products'],
            'categories' => $data['categories'],
            'filters' => $data['filters'],
            'currentPromotion' => $data['currentPromotion'] ?? null,
        ]);
    }

    // V2 分類頁
    public function category(Request $request, $slug)
    {
        $data = $this->shopService->getCategoryData($slug, $request);
        return Inertia::render('Shop/Category', $data);
    }

    // V2 商品詳情
    public function product($slug)
    {
        $data = $this->shopService->getProductDetailData($slug);
        return Inertia::render('Shop/Product', $data);
    }
}
