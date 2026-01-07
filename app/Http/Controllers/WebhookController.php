<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle LINE Messaging API Webhooks.
     * Used to capture User ID or handle auto-replies.
     */
    public function handleLineCallback(Request $request)
    {
        // 1. 驗證請求 (簡單略過簽章驗證，直接取資料)
        // 實務上建議在此處加入 LINE 的 Signature 驗證
        $events = $request->input('events', []);

        foreach ($events as $event) {
            // 當有人傳訊息進來
            if ($event['type'] === 'message' && $event['message']['type'] === 'text') {

                $userId = $event['source']['userId'] ?? null;
                $replyToken = $event['replyToken'] ?? null;

                if ($userId && $replyToken) {
                    // Log User ID 方便除錯
                    Log::info("Captured LINE User ID: {$userId}");

                    // 2. 回覆 User ID 給使用者
                    // 注意：需確保 .env 中有設定 LINE_CHANNEL_ACCESS_TOKEN
                    try {
                        Http::withToken(config('services.line.channel_token', env('LINE_CHANNEL_ACCESS_TOKEN')))
                            ->post('https://api.line.me/v2/bot/message/reply', [
                                'replyToken' => $replyToken,
                                'messages' => [
                                    [
                                        'type' => 'text',
                                        'text' => "您的 User ID 是：\n{$userId}\n\n請將此 ID 提供給管理員或設定至系統中。"
                                    ]
                                ]
                            ]);
                    } catch (\Exception $e) {
                        Log::error('LINE Webhook Reply Error: ' . $e->getMessage());
                    }
                }
            }
        }

        return response('OK', 200);
    }
}
