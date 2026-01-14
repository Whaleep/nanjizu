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
                                        'text' => "您的 User ID 是：\n{$userId}\n\n請將此 ID 提供給開發人員。"
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

    public function debugLine()
    {
        // 1. 讀取設定
        $token = env('LINE_CHANNEL_ACCESS_TOKEN');
        $userId = env('LINE_ADMIN_USER_ID');

        echo "<h1>LINE Bot 診斷模式</h1>";

        // 2. 檢查變數是否讀取成功
        echo "<h3>1. 環境變數檢查</h3>";
        echo "Token (前10碼): " . substr($token, 0, 10) . "...<br>";
        echo "User ID: " . $userId . "<br>";

        if (empty($token) || empty($userId)) {
            echo "<span style='color:red'>❌ 錯誤：Token 或 User ID 為空，請檢查 .env 或執行 php artisan config:clear</span>";
            return;
        } else {
            echo "<span style='color:green'>✅ 變數讀取成功</span>";
        }

        // 3. 測試發送 (純文字)
        echo "<h3>2. 發送測試 (純文字)</h3>";

        try {
            $response = Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', [
                'to' => $userId,
                'messages' => [
                    [
                        'type' => 'text',
                        'text' => '🔔 這是來自正式主機的連線測試！'
                    ]
                ]
            ]);

            echo "HTTP 狀態碼: " . $response->status() . "<br>";

            if ($response->successful()) {
                echo "<span style='color:green'>✅ 發送成功！請檢查手機。</span><br>";
            } else {
                echo "<span style='color:red'>❌ 發送失敗！LINE 回傳錯誤：</span><br>";
                echo "<pre>" . json_encode($response->json(), JSON_PRETTY_PRINT) . "</pre>";
            }
        } catch (\Exception $e) {
            echo "<span style='color:red'>❌ 連線發生例外錯誤：</span><br>";
            echo $e->getMessage();
        }

        // 4. 測試發送 (Flex Message - 模擬訂單)
        echo "<h3>3. 發送測試 (Flex Message 模擬)</h3>";
        // 這裡我們簡單模擬一個 JSON Payload，測試 Flex 格式是否被接受
        $flexPayload = [
            'to' => $userId,
            'messages' => [
                [
                    'type' => 'flex',
                    'altText' => 'Flex 測試',
                    'contents' => [
                        'type' => 'bubble',
                        'body' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'contents' => [
                                ['type' => 'text', 'text' => 'Flex 訊息測試成功', 'weight' => 'bold', 'size' => 'xl', 'color' => '#1DB446']
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $responseFlex = Http::withToken($token)->post('https://api.line.me/v2/bot/message/push', $flexPayload);

        if ($responseFlex->successful()) {
            echo "<span style='color:green'>✅ Flex Message 發送成功！</span>";
        } else {
            echo "<span style='color:red'>❌ Flex Message 失敗：</span><br>";
            echo $responseFlex->body();
        }
    }
}
