<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1;
use App\Models\Store;

/*
|--------------------------------------------------------------------------
| V1 Routes (Blade) - 舊版備份
|--------------------------------------------------------------------------
*/

// 首頁
Route::get('/', [V1\HomeController::class, 'index'])->name('home');
Route::get('/second-hand', [V1\HomeController::class, 'secondHand'])->name('second-hand.index');
Route::get('/about', function () {
    return view('pages.about');
})->name('about');

// 簡單展示分店列表

// 簡單展示文章
Route::get('/news', [V1\PostController::class, 'news'])->name('news.index');
Route::get('/cases', [V1\PostController::class, 'cases'])->name('cases.index');
Route::get('/posts/{slug}', [V1\PostController::class, 'show'])->name('posts.show');

// 報價與預約路由
Route::get('/repair', [V1\RepairController::class, 'index'])->name('repair.index');
Route::get('/repair/{id}', [V1\RepairController::class, 'show'])->name('repair.show');
Route::post('/inquiry', [V1\RepairController::class, 'storeInquiry'])->name('inquiry.store');

// 靜態與分店列表
Route::get('/second-hand', [V1\HomeController::class, 'secondHand'])->name('second-hand.index');
Route::get('/process', function () {
    return view('pages.process');
})->name('process');
Route::get('/stores', function () {
    $stores = Store::where('is_active', true)->get();
    return view('stores.index', compact('stores'));
})->name('stores.index');

// 商店路由
Route::get('/shop', [V1\ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/category/{slug}', [V1\ShopController::class, 'category'])->name('shop.category');
Route::get('/shop/product/{slug}', [V1\ShopController::class, 'product'])->name('shop.product');

// 購物車路由
Route::get('/cart', [V1\CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [V1\CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [V1\CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [V1\CartController::class, 'remove'])->name('cart.remove');

// 結帳路由
Route::get('/checkout', [V1\CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [V1\CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{id}', [V1\CheckoutController::class, 'success'])->name('checkout.success');
