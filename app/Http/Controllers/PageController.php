<?php

namespace App\Http\Controllers;

use App\Models\Page;
use Inertia\Inertia;

class PageController extends Controller
{
    // V2: 顯示自訂頁面
    public function showV2($slug)
    {
        $page = Page::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return Inertia::render('Page/Show', [
            'page' => $page,
        ]);
    }
}
