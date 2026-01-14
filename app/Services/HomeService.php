<?php

namespace App\Services;

use App\Models\Post;
use App\Models\Page;
class HomeService
{
    // 取得最新消息 (V1 使用，V2 動態載入)
    public function getLatestPosts($limit = 3)
    {
        return Post::where('is_published', true)
            ->latest('published_at')
            ->take($limit)
            ->get();
    }

    // 取得 V2 首頁設定 (Page Builder 資料)
    public function getHomePageData()
    {
        // 嘗試抓取 slug 為 'home' 且已發布的頁面
        return Page::where('slug', 'home')
            ->where('is_published', true)
            ->first();
    }

    // 取得二手機 (V1 舊版用)
    public function getSecondHandDevices()
    {
        // 假設您還保留著 SecondHandDevice Model
        return \App\Models\SecondHandDevice::where('is_sold', false)
            ->latest()
            ->get();
    }
}
