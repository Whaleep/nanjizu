<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrderTrackingController extends Controller
{
// 顯示查詢表單
    public function index(Request $request)
    {
        // 允許透過網址帶入訂單編號 (例如從 Email 點進來)
        return Inertia::render('Tracking/Index', [
            'prefilledOrderNumber' => $request->query('order_number')
        ]);
    }

    // 執行查詢
    public function search(Request $request)
    {
        $request->validate([
            'order_number' => 'required|string',
            'customer_phone' => 'required|string',
        ]);

        // 雙重驗證：單號 + 電話 必須同時吻合
        $order = Order::where('order_number', $request->order_number)
            ->where('customer_phone', $request->customer_phone)
            ->with('items') // 預載明細
            ->first();

        if (!$order) {
            return back()->withErrors([
                'order_number' => '找不到訂單，請檢查「訂單編號」與「電話」是否輸入正確。'
            ]);
        }

        // 查詢成功，顯示結果頁
        return Inertia::render('Tracking/Show', [
            'order' => $order
        ]);
    }
}
