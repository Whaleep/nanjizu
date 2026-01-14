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
        $page = $this->homeService->getHomePageData();

        return Inertia::render('Home', [
            'page' => $page,
        ]);
    }
}
