<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Post;
use App\Models\Store;
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
    
    /**
     * 取得服務據點區塊列表
     */
    public function stores(Request $request)
    {
        // 基本查詢：只撈啟用的
        $query = Store::where('is_active', true);

        // 處理數量限制 (如果有傳 limit 且大於 0)
        if ($request->filled('limit') && $request->limit > 0) {
            $query->take($request->limit);
        }

        // 排序建議 (可依據需求改為 sort_order 或 id)
        $stores = $query->orderBy('id', 'asc') // 或 orderBy('sort_order')
            ->get()
            ->map(function ($store) {
                return [
                    'id' => $store->id,
                    'name' => $store->name,
                    'address' => $store->address,
                    'phone' => $store->phone,
                    'opening_hours' => $store->opening_hours,
                    'map_url' => $store->map_url,
                ];
            });

        return response()->json($stores);
    }
}
