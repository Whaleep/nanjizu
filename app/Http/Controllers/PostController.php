<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;

class PostController extends Controller
{
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
