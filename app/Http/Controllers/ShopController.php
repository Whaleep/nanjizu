<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ShopCategory;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    // 商店首頁：顯示最上層分類 (如 Apple, Samsung, 配件)
    public function index(Request $request)
    {
        $query = $request->input('q');
        $tagSlug = $request->input('tag');

        // 如果有搜尋 OR 有標籤，都進入「商品列表模式」
        if ($query || $tagSlug) {
            $productsQuery = Product::where('is_active', true)->with('variants');

            if ($query) {
                $productsQuery->where('name', 'like', "%{$query}%");
            }

            if ($tagSlug) {
                $productsQuery->whereHas('tags', function ($q) use ($tagSlug) {
                    $q->where('slug', $tagSlug);
                });
            }

            $products = $productsQuery->paginate(12);

            // 顯示搜尋標題
            $title = $tagSlug ? '標籤：' . $tagSlug : '搜尋：' . $query;

            // 重用 search view
            return view('shop.search', ['products' => $products, 'query' => $title]);
        }

        // 1. 抓取最上層分類
        $categories = ShopCategory::whereNull('parent_id')
            ->where('is_visible', true)
            ->orderBy('sort_order')
            ->get();

        // 2. 抓取所有上架商品 (分頁)
        $products = Product::where('is_active', true)
            ->with('variants') // 預載規格以顯示價格
            ->latest() // 依照最新上架排序
            ->paginate(12); // 每頁 12 筆

        return view('shop.index', compact('categories', 'products'));
    }

    // 分類頁：顯示該分類下的「子分類」或是「商品」
    public function category(Request $request, $slug) // 接收 Request
    {
        $category = ShopCategory::where('slug', $slug)->firstOrFail();
        $subcategories = $category->children()->where('is_visible', true)->get();

        // 取得所有子孫分類的 ID (包含自己)
        $categoryIds = $category->getAllChildrenIds();

        // 基礎查詢
        $query = Product::whereIn('shop_category_id', $categoryIds)
            ->where('is_active', true);

        // 標籤篩選 (如果有 ?tag=電池)
        if ($request->has('tag')) {
            $tagSlug = $request->input('tag');
            $query->whereHas('tags', function ($q) use ($tagSlug) {
                $q->where('slug', $tagSlug);
            });
        }

        $products = $query->with('variants')->latest()->paginate(12);

        // 取得該分類下所有商品有用到的標籤 (用於顯示篩選按鈕)
        // 這裡用一個比較進階的查詢，或是簡單地列出所有標籤
        $tags = \App\Models\ProductTag::whereHas('products', function ($q) use ($category) {
            $q->where('shop_category_id', $category->id);
        })->get();

        return view('shop.category', compact('category', 'subcategories', 'products', 'tags'));
    }

    // 顯示商品詳情
    public function product($slug)
    {
        $product = Product::where('slug', $slug)
            ->where('is_active', true)
            ->with(['variants', 'category']) // 預先載入規格和分類
            ->firstOrFail();

        return view('shop.product', compact('product'));
    }
}
