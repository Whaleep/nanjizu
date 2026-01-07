<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>訂單 {{ $order->order_number }} - 出貨單</title>
    <style>
        body { font-family: sans-serif; padding: 20px; color: #000; }
        .header { text-align: center; margin-bottom: 30px; border-bottom: 2px solid #000; padding-bottom: 10px; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .box { border: 1px solid #ccc; padding: 15px; }
        .box h3 { margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
        .text-right { text-align: right; }
        .total-section { margin-top: 20px; text-align: right; font-size: 1.2em; font-weight: bold; }

        /* 列印專用設定 */
        @media print {
            body { padding: 0; }
            .no-print { display: none; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="margin-bottom: 20px;">
        <button onclick="window.print()" style="padding: 10px 20px; cursor: pointer;">🖨️ 列印此單</button>
    </div>

    <div class="header">
        <h1>{{ config('app.name') }} - 出貨單</h1>
        <p>訂單編號：{{ $order->order_number }} / 下單時間：{{ $order->created_at->format('Y-m-d H:i') }}</p>
    </div>

    <div class="info-grid">
        <div class="box">
            <h3>收件人資訊</h3>
            <p><strong>姓名：</strong>{{ $order->customer_name }}</p>
            <p><strong>電話：</strong>{{ $order->customer_phone }}</p>
            <p><strong>地址：</strong>{{ $order->customer_address }}</p>
        </div>
        <div class="box">
            <h3>訂單資訊</h3>
            <p><strong>付款方式：</strong>{{ $order->payment_method }}</p>
            <p><strong>狀態：</strong>{{ $order->status }}</p>
            <p><strong>會員帳號：</strong>{{ $order->user ? $order->user->name : '訪客' }}</p>
            @if($order->notes)
                <p><strong>備註：</strong>{{ $order->notes }}</p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>商品名稱</th>
                <th>規格</th>
                <th class="text-right">單價</th>
                <th class="text-right">數量</th>
                <th class="text-right">小計</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->variant_name }}</td>
                <td class="text-right">${{ number_format($item->price) }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">${{ number_format($item->subtotal) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <p>總金額：NT$ {{ number_format($order->total_amount) }}</p>
    </div>

    <div style="margin-top: 50px; text-align: center; font-size: 0.8em; color: #666;">
        感謝您的購買！如有問題請聯繫我們。
    </div>

</body>
</html>
