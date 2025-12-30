<?php

namespace App\Http\Controllers;
use Inertia\Inertia;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
    // V2: 最新消息列表
    public function newsV2()
    {
        $posts = \App\Models\Post::where('is_published', true)
            ->where('category', 'news')
            ->latest('published_at')
            ->paginate(9);

        return Inertia::render('Post/Index', [
            'title' => '最新消息',
            'posts' => $posts,
            'type' => 'news' // 用來區分 UI 樣式或標籤
        ]);
    }

    // V2: 維修案例列表
    public function casesV2()
    {
        $posts = \App\Models\Post::where('is_published', true)
            ->where('category', 'case')
            ->latest('published_at')
            ->paginate(9);

        return Inertia::render('Post/Index', [
            'title' => '維修案例',
            'posts' => $posts,
            'type' => 'case'
        ]);
    }

    // V2: 文章詳情
    public function showV2($slug)
    {
        $post = \App\Models\Post::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return Inertia::render('Post/Show', [
            'post' => $post
        ]);
    }

    // 顯示最新消息
    public function news()
    {
        $posts = Post::where('is_published', true)
            ->where('category', 'news')
            ->latest('published_at')
            ->paginate(9); // 每頁 9 筆

        return view('posts.index', [
            'title' => '最新消息',
            'posts' => $posts
        ]);
    }

    // 顯示維修案例
    public function cases()
    {
        $posts = Post::where('is_published', true)
            ->where('category', 'case')
            ->latest('published_at')
            ->paginate(9);

        return view('posts.index', [
            'title' => '維修案例',
            'posts' => $posts
        ]);
    }

    // 顯示單篇文章詳情
    public function show($slug)
    {
        $post = Post::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();

        return view('posts.show', compact('post'));
    }
}
?>
