<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\HomeService;

class HomeController extends Controller
{
    protected $homeService;

    public function __construct(HomeService $homeService)
    {
        $this->homeService = $homeService;
    }

    public function index()
    {
        $latestPosts = $this->homeService->getLatestPosts(3);
        return view('home', compact('latestPosts'));
    }

    // 舊版的二手機頁面保留在 V1
    public function secondHand()
    {
        $devices = $this->homeService->getSecondHandDevices();
        return view('second-hand.index', compact('devices'));
    }
}
