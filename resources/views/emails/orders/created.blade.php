<!DOCTYPE html>
<html>

<head>
  <style>
    body {
      font-family: sans-serif;
      color: #333;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
      margin: 20px 0;
    }

    .table th,
    .table td {
      border: 1px solid #ddd;
      padding: 8px;
      text-align: left;
    }

    .header {
      background: #f4f4f4;
      padding: 20px;
      text-align: center;
    }
  </style>
</head>

<body>
  <div class="header">
    <h1>感謝您的購買！</h1>
    <p>我們已收到您的訂單，將盡快為您安排出貨。</p>
  </div>

  <p><strong>訂單編號：</strong> {{ $order->order_number }}</p>
  <p><strong>收件人：</strong> {{ $order->customer_name }}</p>
  <p><strong>付款方式：</strong> {{ $order->payment_method }}</p>

  <table class="table">
    <thead>
      <tr>
        <th>商品</th>
        <th>規格</th>
        <th>數量</th>
        <th>小計</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($order->items as $item)
        <tr>
          <td>{{ $item->product_name }}</td>
          <td>{{ $item->variant_name }}</td>
          <td>{{ $item->quantity }}</td>
          <td>${{ number_format($item->subtotal) }}</td>
        </tr>
      @endforeach
    </tbody>
  </table>

  <p style="text-align: right; font-size: 1.2em; font-weight: bold;">
    總金額：NT$ {{ number_format($order->total_amount) }}
  </p>

  @if ($order->payment_method == 'bank_transfer')
    <div style="background: #fff3cd; padding: 10px; border: 1px solid #ffeeba;">
      <strong>匯款資訊：</strong><br>
      銀行代碼：822<br>
      帳號：1234-5678-9012
    </div>
  @endif

  <div style="margin: 30px 0; text-align: center;">
    <p>您可以點擊下方按鈕查詢訂單最新狀態：</p>
    <a href="{{ route('tracking.index', ['order_number' => $order->order_number]) }}"
      style="background-color: #2563eb; color: white; padding: 12px 24px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;">
      查詢訂單狀態
    </a>
  </div>

  <p>有任何問題，歡迎直接回覆此郵件或透過 Line 聯繫我們。</p>
</body>

</html>
