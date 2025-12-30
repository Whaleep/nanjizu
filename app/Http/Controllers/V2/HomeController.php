<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\HomeService;
use Inertia\Inertia;

class HomeController extends Controller
{
    protected $homeService;

    public function __construct(HomeService $homeService)
    {
        $this->homeService = $homeService;
    }

    public function index()
    {
        return Inertia::render('Home', [
            'latestPosts' => $this->homeService->getLatestPosts(3),
            // 如果 V2 首頁想顯示商品，可以順便傳過去：
            // 'latestProducts' => $this->homeService->getLatestProducts(4),
        ]);
    }
}
