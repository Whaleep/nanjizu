<?php

namespace App\Services;

use App\Models\Post;

class PostService
{
    /**
     * 取得特定分類的文章列表 (分頁)
     */
    public function getListByCategory(string $category, int $perPage = 9)
    {
        return Post::where('is_published', true)
            ->where('category', $category)
            ->latest('published_at')
            ->paginate($perPage);
    }

    /**
     * 取得單篇文章
     */
    public function getBySlug(string $slug)
    {
        return Post::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
    }
}

