<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use App\Services\CartService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class MergeCartOnLogin
{
    /**
     * Create the event listener.
     */
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event): void
    {
        // 當使用者登入時，執行合併
        $this->cartService->mergeSessionToDb();
    }
}
