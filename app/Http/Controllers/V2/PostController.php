<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Services\PostService;
use Inertia\Inertia;

class PostController extends Controller
{
    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    // V2: 最新消息
    public function news()
    {
        $posts = $this->postService->getListByCategory('news');

        return Inertia::render('Post/Index', [
            'title' => '最新消息',
            'posts' => $posts,
            'type' => 'news'
        ]);
    }

    // V2: 維修案例
    public function cases()
    {
        $posts = $this->postService->getListByCategory('case');

        return Inertia::render('Post/Index', [
            'title' => '維修案例',
            'posts' => $posts,
            'type' => 'case'
        ]);
    }

    // V2: 詳情
    public function show($slug)
    {
        $post = $this->postService->getBySlug($slug);

        return Inertia::render('Post/Show', [
            'post' => $post
        ]);
    }
}
