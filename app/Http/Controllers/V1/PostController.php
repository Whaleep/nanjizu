<?php

namespace App\Http\Controllers\V1;

use App\Http\Controllers\Controller;
use App\Services\PostService;

class PostController extends Controller
{
    protected $postService;

    public function __construct(PostService $postService)
    {
        $this->postService = $postService;
    }

    // 最新消息
    public function news()
    {
        $posts = $this->postService->getListByCategory('news');

        return view('posts.index', [
            'title' => '最新消息',
            'posts' => $posts,
            'type' => 'news'
        ]);
    }

    // 維修案例
    public function cases()
    {
        $posts = $this->postService->getListByCategory('case');

        return view('posts.index', [
            'title' => '維修案例',
            'posts' => $posts,
            'type' => 'case'
        ]);
    }

    // 詳情
    public function show($slug)
    {
        $post = $this->postService->getBySlug($slug);
        return view('posts.show', compact('post'));
    }
}

