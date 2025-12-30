<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Pagination\Paginator;
use App\Models\ShopMenu;
use App\Models\ShopCategory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services. 全域共享資料
     */
    public function boot(): void
    {
        // 如果 .env 設定的 APP_URL 開頭是 https，就強制全站使用 HTTPS
        // 這樣在 ngrok 環境下，CSS 和 JS 就不會被瀏覽器擋掉
        if (str_contains(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        View::composer(['layouts.shop', 'shop.*'], function ($view) {
            $view->with(
                'menuItems',
                ShopMenu::orderBy('sort_order')->get()
            );
        });

        Paginator::useTailwind();
    }
}
