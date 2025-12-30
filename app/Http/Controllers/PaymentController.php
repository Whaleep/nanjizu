<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\ECPayService;
use App\Services\LineBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    public function callback(Request $request, ECPayService $ecpayService, LineBotService $lineBot)
    {
        // 1. 接收綠界回傳的所有資料
        $data = $request->all();

        // 建議：將回傳資料記錄到 Log，方便除錯
        Log::info('ECPay Callback:', $data);

        // 2. 驗證檢查碼 (防止偽造請求)
        if (!$ecpayService->verifyCheckMacValue($data)) {
            Log::error('ECPay Signature Mismatch');
            return '0|Error'; // 驗證失敗回傳錯誤給綠界
        }

        // 3. 檢查交易狀態 (RtnCode == 1 代表成功)
        if ($data['RtnCode'] == '1') {

            // 找出對應的訂單
            $orderNumber = $data['MerchantTradeNo'];
            $order = Order::where('order_number', $orderNumber)->first();

            if ($order) {
                // 更新訂單狀態為「處理中」或「已付款」
                // 注意：避免重複更新 (如果已經是 completed 就別動了)
                if ($order->status == 'pending') {
                    $order->update([
                        'status' => 'processing', // 或 processing
                        'payment_method' => 'ecpay_paid', // 標記已付款
                        'notes' => $order->notes . "\n[綠界付款成功] 交易單號: " . $data['TradeNo'],
                    ]);
                    $lineBot->sendText("💰 訂單 {$order->order_number} 已完成綠界付款！");
                }
            }
        }

        // 4. 回傳 '1|OK' 給綠界 (這是綠界規定的回應格式)
        return '1|OK';
    }
}

?>
