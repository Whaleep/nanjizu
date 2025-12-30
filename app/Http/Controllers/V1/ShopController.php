<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\ShopService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    protected $shopService;

    public function __construct(ShopService $shopService)
    {
        $this->shopService = $shopService;
    }

    // 商店首頁 & 搜尋
    public function index(Request $request)
    {
        $data = $this->shopService->getIndexData($request);

        // V1 的邏輯：如果有篩選條件，顯示搜尋頁；否則顯示首頁
        if ($data['isFiltered']) {
            $title = $data['filters']['tag']
                ? '標籤：' . $data['filters']['tag']
                : '搜尋：' . $data['filters']['q'];

            return view('shop.search', [
                'products' => $data['products'],
                'query' => $title
            ]);
        }

        return view('shop.index', [
            'categories' => $data['categories'],
            'products' => $data['products']
        ]);
    }

    // 分類頁
    public function category(Request $request, $slug)
    {
        $data = $this->shopService->getCategoryData($slug, $request);
        return view('shop.category', $data);
    }

    // 商品詳情
    public function product($slug)
    {
        $data = $this->shopService->getProductDetailData($slug);

        // V1 的 view 只接收 product，如果要加關聯商品需修改 view
        return view('shop.product', [
            'product' => $data['product']
        ]);
    }
}
