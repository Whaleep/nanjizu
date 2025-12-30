<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LineBotService
{
    protected $token;
    protected $targetUserId;
    protected $apiUrl = 'https://api.line.me/v2/bot/message/push';

    public function __construct()
    {
        $this->token = env('LINE_CHANNEL_ACCESS_TOKEN');
        $this->targetUserId = env('LINE_ADMIN_USER_ID');
    }

    /**
     * 發送純文字訊息
     */
    public function sendText($text)
    {
        if (!$this->token || !$this->targetUserId) {
            Log::warning('Line Bot 設定未完成，無法發送通知');
            return;
        }

        try {
            $response = Http::withToken($this->token)
                ->post($this->apiUrl, [
                    'to' => $this->targetUserId,
                    'messages' => [
                        [
                            'type' => 'text',
                            'text' => $text
                        ]
                    ]
                ]);

            if (!$response->successful()) {
                Log::error('Line Bot 發送失敗: ' . $response->body());
            }
        } catch (\Exception $e) {
            Log::error('Line Bot 連線錯誤: ' . $e->getMessage());
        }
    }

    /**
     * 發送訂單通知 (使用 Flex Message 美化版)
     */
    public function sendOrderNotification($order)
    {
        if (!$this->token || !$this->targetUserId) return;

        // 判斷付款狀態文字顏色
        $statusColor = ($order->payment_method == 'ecpay' || $order->payment_method == 'ecpay_paid') ? '#1DB446' : '#ff9800';
        $paymentText = match($order->payment_method) {
            'cod' => '貨到付款',
            'bank_transfer' => '銀行匯款',
            'ecpay', 'ecpay_paid' => '綠界刷卡',
            default => $order->payment_method,
        };

        // 建構 Flex Message (漂亮的卡片樣式)
        $messagePayload = [
            'to' => $this->targetUserId,
            'messages' => [
                [
                    'type' => 'flex',
                    'altText' => '收到新訂單！',
                    'contents' => [
                        'type' => 'bubble',
                        'header' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'contents' => [
                                ['type' => 'text', 'text' => '新訂單通知', 'weight' => 'bold', 'color' => '#1DB446', 'size' => 'sm'],
                                ['type' => 'text', 'text' => '$' . number_format($order->total_amount), 'weight' => 'bold', 'size' => 'xxl', 'margin' => 'md'],
                                ['type' => 'text', 'text' => $order->order_number, 'size' => 'xs', 'color' => '#aaaaaa', 'wrap' => true]
                            ]
                        ],
                        'body' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'contents' => [
                                [
                                    'type' => 'box',
                                    'layout' => 'baseline',
                                    'spacing' => 'sm',
                                    'contents' => [
                                        ['type' => 'text', 'text' => '客戶', 'color' => '#aaaaaa', 'size' => 'sm', 'flex' => 1],
                                        ['type' => 'text', 'text' => $order->customer_name, 'wrap' => true, 'color' => '#666666', 'size' => 'sm', 'flex' => 5]
                                    ]
                                ],
                                [
                                    'type' => 'box',
                                    'layout' => 'baseline',
                                    'spacing' => 'sm',
                                    'contents' => [
                                        ['type' => 'text', 'text' => '付款', 'color' => '#aaaaaa', 'size' => 'sm', 'flex' => 1],
                                        ['type' => 'text', 'text' => $paymentText, 'wrap' => true, 'color' => $statusColor, 'size' => 'sm', 'flex' => 5]
                                    ]
                                ]
                            ]
                        ],
                        'footer' => [
                            'type' => 'box',
                            'layout' => 'vertical',
                            'spacing' => 'sm',
                            'contents' => [
                                [
                                    'type' => 'button',
                                    'style' => 'link',
                                    'height' => 'sm',
                                    'action' => [
                                        'type' => 'uri',
                                        'label' => '開啟後台查看',
                                        // 這裡請填入您的後台網址
                                        'uri' => url('/admin/orders')
                                    ]
                                ]
                            ],
                            'flex' => 0
                        ]
                    ]
                ]
            ]
        ];

        try {
            Http::withToken($this->token)->post($this->apiUrl, $messagePayload);
        } catch (\Exception $e) {
            Log::error('Line Bot Flex 發送錯誤: ' . $e->getMessage());
            // 如果 Flex 失敗，降級傳送純文字
            $this->sendText("新訂單 {$order->order_number} \n金額: {$order->total_amount}");
        }
    }
}
