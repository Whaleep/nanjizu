<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\PostController;
use App\Http\Controllers\RepairController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\PaymentController;
use App\Models\Post;
use App\Models\Store;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

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

Route::get('/', [HomeController::class, 'index'])->name('home');

// 關於我們頁面
Route::get('/about', function () {
    return view('pages.about');
})->name('about');

// 簡單展示分店列表
Route::get('/stores', function () {
    $stores = Store::where('is_active', true)->get();
    return view('stores.index', compact('stores'));
})->name('stores.index');

// 簡單展示文章
Route::get('/news/{slug}', function ($slug) {
    $post = Post::where('slug', $slug)->where('is_published', true)->firstOrFail();
    return view('posts.show', compact('post'));
})->name('posts.show');

// 文章相關路由
Route::get('/news', [PostController::class, 'news'])->name('news.index');
Route::get('/cases', [PostController::class, 'cases'])->name('cases.index');
Route::get('/posts/{slug}', [PostController::class, 'show'])->name('posts.show');

// 報價與預約路由
Route::get('/repair', [RepairController::class, 'index'])->name('repair.index');
Route::get('/repair/{id}', [RepairController::class, 'show'])->name('repair.show');
Route::post('/inquiry', [RepairController::class, 'storeInquiry'])->name('inquiry.store');
Route::get('/second-hand', [App\Http\Controllers\HomeController::class, 'secondHand'])->name('second-hand.index');
Route::get('/process', function () {
    return view('pages.process');
})->name('process');

// 商店路由
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/category/{slug}', [ShopController::class, 'category'])->name('shop.category');
Route::get('/shop/product/{slug}', [ShopController::class, 'product'])->name('shop.product');

// 購物車路由
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
Route::post('/cart/update', [CartController::class, 'update'])->name('cart.update');
Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');

// 結帳路由
Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');
Route::get('/checkout/success/{id}', [CheckoutController::class, 'success'])->name('checkout.success');

// 綠界回調路由 (必須是 POST)
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
//                             'text' => "您的 User ID 是：\n" . $userId . "\n\n請將此 ID 複製到 .env 的 LINE_ADMIN_USER_ID 欄位中。"
//                         ]
//                     ]
//                 ]);
//         }
//     }

//     return response('OK', 200);
// });


?>
