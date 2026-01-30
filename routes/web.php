<?php

use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Models\Post;
use App\Http\Controllers\V2;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\OrderTrackingController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Api\BlockController;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Inertia\Inertia;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/
/*
|--------------------------------------------------------------------------
| V2 Routes (Inertia.js / Vue) - 新版前台
|--------------------------------------------------------------------------
*/

Route::get('/', [V2\HomeController::class, 'index'])->name('home');

// 服務據點 (需要撈資料)
Route::get('/stores', function () {
    $stores = \App\Models\Store::where('is_active', true)->get();
    return Inertia::render('Pages/Stores', ['stores' => $stores]);
})->name('stores.index');

// 送修流程 (靜態)
Route::get('/process', function () {
    return Inertia::render('Pages/Process');
})->name('process');

// 關於我們 (靜態)
Route::get('/about', function () {
    return Inertia::render('Pages/About');
})->name('about');

Route::get('/news', [V2\PostController::class, 'news'])->name('news.index');
Route::get('/cases', [V2\PostController::class, 'cases'])->name('cases.index');
Route::get('/posts/{slug}', [V2\PostController::class, 'show'])->name('posts.show');

Route::get('/repair', [V2\RepairController::class, 'index'])->name('repair.index');
Route::get('/repair/{id}', [V2\RepairController::class, 'show'])->name('repair.show');
// 注意：前端 Vue 的 Form 如果 action 改打這裡，要記得改路由名稱或路徑
Route::post('/repair/inquiry', [V2\RepairController::class, 'store'])->name('repair.inquiry');

// 商店首頁
Route::get('/shop', [V2\ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/category/{slug}', [V2\ShopController::class, 'category'])->name('shop.category');
Route::get('/shop/product/{slug}', [V2\ShopController::class, 'product'])->name('shop.product');

// 購物車頁面
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [V2\CartController::class, 'index'])->name('index');
    // APIs (Vue Axios 呼叫這些)
    Route::get('/count', [V2\CartController::class, 'count'])->name('count');
    Route::post('/add', [V2\CartController::class, 'add'])->name('add');
    Route::post('/update', [V2\CartController::class, 'update'])->name('update');
    Route::post('/remove', [V2\CartController::class, 'remove'])->name('remove');
    Route::post('/coupon', [V2\CartController::class, 'applyCoupon'])->name('coupon.apply');
    Route::delete('coupon', [V2\CartController::class, 'removeCoupon'])->name('coupon.remove');
    // 新增：提交贈品並前往結帳
    Route::post('/checkout', [V2\CartController::class, 'prepareCheckout'])->name('prepare-checkout');
});

Route::get('/checkout', [V2\CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [V2\CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{id}', [V2\CheckoutController::class, 'success'])->name('checkout.success');

// 訪客訂單查詢
Route::get('/tracking', [OrderTrackingController::class, 'index'])->name('tracking.index');
Route::post('/tracking', [OrderTrackingController::class, 'search'])->name('tracking.search');

// Auth Routes (只給未登入者)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'create']);
});

// 會員專區 (需登入)
Route::middleware(['auth'])->group(function () {
    // 買家評論、 切換收藏 (API)、登出
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
    Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::prefix('dashboard')->name('dashboard.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('index');
        Route::get('/orders', [DashboardController::class, 'orders'])->name('orders');
        Route::get('/orders/{orderNumber}', [DashboardController::class, 'orderDetail'])->name('orders.show');
        Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
        Route::post('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');
        Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
    });
});

// 後台管理路由群組 (後台專屬非 Filament 功能)
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/orders/{order}/print', [AdminOrderController::class, 'print'])->name('orders.print');

    // 用 webhook 取得 line User ID
    Route::post('/line/webhook', [\App\Http\Controllers\WebhookController::class, 'handleLineCallback'])->name('webhook.line');
    Route::get('/debug/line', [\App\Http\Controllers\WebhookController::class, 'debugLine'])->name('debug.line');
});

/*
|--------------------------------------------------------------------------
| Shared APIs (共用 API)
|--------------------------------------------------------------------------
*/
// 1. Webhooks / Callbacks (通常不需要 CSRF 保護，需檢查 VerifyCsrfToken middleware)
Route::post('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');

// 2. Frontend Data Blocks (前端區塊資料 API)
// 商品、文章區塊列表
Route::prefix('api')->name('api.')->group(function () {
    Route::controller(BlockController::class)->group(function () {
        Route::get('/products/block', 'products')->name('products.block');
        Route::get('/posts/block', 'posts')->name('posts.block');
    });
});

/*
|--------------------------------------------------------------------------
| V1 Routes Loading blades
|--------------------------------------------------------------------------
*/
Route::prefix('v1')
    ->name('v1.')
    ->group(base_path('routes/blades.php'));

/*
|--------------------------------------------------------------------------
| Static Pages (放在最後以免攔截)
|--------------------------------------------------------------------------
*/
Route::get('/{slug}', [V2\PageController::class, 'show'])->name('page.show');
