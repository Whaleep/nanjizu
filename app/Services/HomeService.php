<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Product; // 假設 V2 首頁要顯示商品

class HomeService
{
    /**
     * 取得最新消息
     */
    public function getLatestPosts($limit = 3)
    {
        return Post::where('is_published', true)
            ->latest('published_at')
            ->take($limit)
            ->get();
    }

    /**
     * 取得最新上架商品 (V2 首頁用)
     */
    public function getLatestProducts($limit = 4)
    {
        return Product::where('is_active', true)
            ->latest()
            ->take($limit)
            ->get();
    }

    /**
     * 取得二手機 (V1 舊版用)
     */
    public function getSecondHandDevices()
    {
        // 假設您還保留著 SecondHandDevice Model
        return \App\Models\SecondHandDevice::where('is_sold', false)
            ->latest()
            ->get();
    }
}
