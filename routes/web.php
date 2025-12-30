<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\V1;
use App\Http\Controllers\V2;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WishlistController;
use App\Http\Controllers\ReviewController;
use App\Models\Post;
use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
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
Route::get('/cart', [V2\CartController::class, 'index'])->name('cart.index');
// 購物車 APIs (Vue Axios 呼叫這些)
Route::get('/cart/count', [V2\CartController::class, 'count'])->name('cart.count');
Route::post('/cart/add', [V2\CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [V2\CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [V2\CartController::class, 'remove'])->name('cart.remove');
Route::post('/cart/coupon', [V2\CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [V2\CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

// Route::get('/checkout', [CheckoutController::class, 'indexV2'])->name('checkout.index');
// Route::get('/checkout/success/{id}', [CheckoutController::class, 'successV2'])->name('checkout.success');
Route::get('/checkout', [V2\CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [V2\CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{id}', [V2\CheckoutController::class, 'success'])->name('checkout.success');

// Auth Routes (只給未登入者)
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::post('/login', [AuthController::class, 'store']);
    Route::get('/register', [AuthController::class, 'register'])->name('register');
    Route::post('/register', [AuthController::class, 'create']);
});

// 會員專區 (需登入)
Route::middleware(['auth'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('index');
    Route::get('/orders', [DashboardController::class, 'orders'])->name('orders');
    Route::get('/orders/{orderNumber}', [DashboardController::class, 'orderDetail'])->name('orders.show');
    Route::get('/profile', [DashboardController::class, 'profile'])->name('profile');
    Route::post('/profile', [DashboardController::class, 'updateProfile'])->name('profile.update');
    Route::get('/wishlist', [WishlistController::class, 'index'])->name('wishlist');
});

Route::middleware(['auth'])->group(function () {
    Route::post('/reviews', [ReviewController::class, 'store'])->name('reviews.store');
});


// 切換收藏 (API)
Route::post('/wishlist/toggle', [WishlistController::class, 'toggle'])->name('wishlist.toggle')->middleware('auth');

// Logout (需登入)
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');


/*
|--------------------------------------------------------------------------
| V1 Routes (Blade) - 舊版備份
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->name('v1.')->group(function () {

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
});

// 共用 API 不分版本
// 綠界金流回調路由
Route::post('/payment/callback', [PaymentController::class, 'callback'])->name('payment.callback');

// 用 webhook 取得 line User ID
// Route::post('/line/webhook', function (\Illuminate\Http\Request $request) {
//     // 1. 驗證請求 (簡單略過簽章驗證，直接取資料)
//     $events = $request->input('events', []);

//     foreach ($events as $event) {
//         // 當有人傳訊息進來
//         if ($event['type'] === 'message' && $event['message']['type'] === 'text') {

//             $userId = $event['source']['userId']; // 抓到 User ID 了！
//             $replyToken = $event['replyToken'];

//             // 2. 回覆 User ID 給使用者
//             Http::withToken(env('LINE_CHANNEL_ACCESS_TOKEN'))
//                 ->post('https://api.line.me/v2/bot/message/reply', [
//                     'replyToken' => $replyToken,
//                     'messages' => [
//                         [
//                             'type' => 'text',
//                             'text' => "您的 User ID 是：\n" . $userId . \\ "\n\n請將此 ID 複製到 .env 的 LINE_ADMIN_USER_ID 欄位中。"
//                         ]
//                     ]
//                 ]);
//         }
//     }

//     return response('OK', 200);
// });

Route::get('/{slug}', [PageController::class, 'showV2'])->name('page.show');
