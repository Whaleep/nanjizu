<?php

namespace App\Http\Controllers;

use App\Models\SecondHandDevice;
// use Illuminate\Http\Request;

class HomeController extends Controller
{
    //
    public function index()
    {
        // 取得最新的3篇發布文章
        $latestPosts = \App\Models\Post::where('is_published', true)
            ->latest('published_at')
            ->take(3)
            ->get();

        return view('home', compact('latestPosts'));
    }

    public function secondHand()
    {
        $devices = \App\Models\SecondHandDevice::where('is_sold', false)
            ->latest()
            ->get();

        return view('second-hand.index', compact('devices'));
    }
}
