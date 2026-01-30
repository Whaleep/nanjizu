<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Post;
use Illuminate\Support\Facades\Storage;

class BlockController extends Controller
{
    /**
     * 取得商品區塊列表
     */
    public function products(Request $request)
    {
        $query = Product::where('is_active', true)->with('variants');

        if ($request->category_id) {
            $query->where('shop_category_id', $request->category_id);
        }

        if ($request->tag_id) {
            $query->whereHas('tags', function ($q) use ($request) {
                $q->where('id', $request->tag_id);
            });
        }

        return $query->latest()->take($request->limit ?? 8)->get();
    }

    /**
     * 取得文章區塊列表
     */
    public function posts(Request $request)
    {
        $query = Post::where('is_published', true);

        // 篩選類型
        if ($request->type && $request->type !== 'all') {
            $query->where('category', $request->type);
        }

        $limit = $request->limit ?? 3;

        $posts = $query->latest('published_at')
            ->take($limit)
            ->get()
            ->map(function ($post) {
                return [
                    'id' => $post->id,
                    'title' => $post->title,
                    'slug' => $post->slug,
                    'category' => $post->category,
                    'image_url' => $post->image ? Storage::url($post->image) : null,
                    'date' => $post->published_at ? $post->published_at->format('Y/m/d') : '',
                ];
            });

        return response()->json($posts);
    }
}
