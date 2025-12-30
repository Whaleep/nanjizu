@extends('layouts.app')

@section('title', '下單成功 - 男機組')

@section('content')
  <div class="container mx-auto px-4 py-20 text-center">
    <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">
      ✓
    </div>
    <h1 class="text-3xl font-bold mb-4">訂單已成立！</h1>
    <p class="text-gray-600 mb-8 text-lg">感謝您的購買，我們將盡快為您處理。</p>

    <div class="bg-white max-w-md mx-auto p-6 rounded-lg shadow border mb-8 text-left">
      <p class="mb-2"><strong>訂單編號：</strong> {{ $order->order_number }}</p>
      <p class="mb-2"><strong>總金額：</strong> NT$ {{ number_format($order->total_amount) }}</p>
      <p class="mb-2">
        <strong>付款方式：</strong>
        @switch($order->payment_method)
          @case('cod')
            貨到付款 / 現場付款
          @break

          @case('bank_transfer')
            銀行匯款 (ATM)
          @break

          @case('ecpay')
          @case('ecpay_paid')
            信用卡 / 超商代碼 (綠界支付)
          @break

          @default
            {{ $order->payment_method }}
        @endswitch
      </p>

      <p class="mb-2">
        <strong>訂單狀態：</strong>
        @if ($order->status == 'pending')
          <span class="text-yellow-600 font-bold">待付款 / 待處理</span>
          @if ($order->payment_method == 'ecpay')
            <span class="text-xs text-red-500">(若您剛剛付款失敗，請重新下單)</span>
          @endif
        @elseif($order->status == 'processing')
          <span class="text-green-600 font-bold">已付款 / 處理中</span>
        @else
          {{ $order->status }}
        @endif
      </p>

      @if ($order->payment_method == 'bank_transfer')
        <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded text-sm text-yellow-800">
          <strong>匯款資訊：</strong><br>
          銀行代碼：000 (測試銀行)<br>
          帳號：123-456-7890<br>
          請於匯款後透過 Line 告知末五碼。
        </div>
      @endif
    </div>

    <div class="flex justify-center gap-4">
      <a href="{{ route('shop.index') }}"
        class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700">繼續購物</a>
      <a href="/" class="text-gray-600 px-6 py-2 hover:underline">回首頁</a>
    </div>
  </div>
@endsection
