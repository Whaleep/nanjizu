<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ECPayService
{
    protected $merchantId;
    protected $hashKey;
    protected $hashIv;
    protected $actionUrl;

    public function __construct()
    {
        $this->merchantId = config('services.ecpay.merchant_id');
        $this->hashKey = config('services.ecpay.hash_key');
        $this->hashIv = config('services.ecpay.hash_iv');
        $this->actionUrl = config('services.ecpay.action_url');
    }

    /**
     * 產生送往綠界的表單資料
     */
    public function generateAutoSubmitForm($order)
    {
        // 1. 準備參數
        $data = [
            'MerchantID' => $this->merchantId,
            'MerchantTradeNo' => $order->order_number, // 訂單編號 (不可重複)
            'MerchantTradeDate' => now()->timezone('Asia/Taipei')->format('Y/m/d H:i:s'),
            'PaymentType' => 'aio',
            'TotalAmount' => (int) $order->total_amount,
            'TradeDesc' => 'ABC商品訂單', // 交易描述
            'ItemName' => '手機配件商品一批', // 這裡建議簡化，避免字元編碼問題
            'ReturnURL' => route('payment.callback'), // 背景通知網址 (Webhook)
            'ClientBackURL' => route('checkout.success', $order->id), // 結帳完成後跳轉回來的網址
            'ChoosePayment' => 'ALL', // 預設顯示所有付款方式
            'EncryptType' => '1',
        ];

        // 2. 產生檢查碼 (CheckMacValue)
        $data['CheckMacValue'] = $this->generateCheckMacValue($data);

        // 記錄送出的資料
        Log::info('ECPay 送出資料:', $data);

        // 3. 產生自動送出的 HTML 表單
        return $this->buildHtmlForm($data);
    }

    /**
     * 產生檢查碼 (綠界的核心加密邏輯)
     */
    public function generateCheckMacValue($params)
    {
        // A. 依照 Key 排序
        ksort($params);

        // B. 組合成 Query String
        $s = 'HashKey=' . $this->hashKey;
        foreach ($params as $key => $value) {
            $s .= '&' . $key . '=' . $value;
        }
        $s .= '&HashIV=' . $this->hashIv;

        // C. URL Encode (綠界比較特別，需轉為小寫並置換特殊字元)
        $s = urlencode($s);
        $s = strtolower($s);

        // 修正 URL Encode 的差異 (依照綠界文件)
        $s = str_replace('%2d', '-', $s);
        $s = str_replace('%5f', '_', $s);
        $s = str_replace('%2e', '.', $s);
        $s = str_replace('%21', '!', $s);
        $s = str_replace('%2a', '*', $s);
        $s = str_replace('%28', '(', $s);
        $s = str_replace('%29', ')', $s);

        // D. SHA256 加密並轉大寫
        return strtoupper(hash('sha256', $s));
    }

    /**
     * 驗證綠界回傳的資料是否正確
     */
    public function verifyCheckMacValue($data)
    {
        if (!isset($data['CheckMacValue'])) {
            return false;
        }

        $receivedCheckMacValue = $data['CheckMacValue'];
        unset($data['CheckMacValue']); // 驗證時要先拿掉檢查碼欄位

        $calculatedCheckMacValue = $this->generateCheckMacValue($data);

        return $receivedCheckMacValue === $calculatedCheckMacValue;
    }

    private function buildHtmlForm($data)
    {
        $html = '<form id="ecpay-form" action="' . $this->actionUrl . '" method="POST" style="display:none;">';
        foreach ($data as $key => $value) {
            $html .= '<input type="hidden" name="' . $key . '" value="' . $value . '">';
        }
        $html .= '</form>';
        $html .= '<script>document.getElementById("ecpay-form").submit();</script>'; // 自動送出

        return $html;
    }
}
