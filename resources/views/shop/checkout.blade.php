@extends('layouts.app')

@section('title', '結帳 - ABC')

@section('content')
  <div class="container mx-auto px-4 py-12">
    <h1 class="text-3xl font-bold mb-8 text-center">填寫結帳資料</h1>

    @if (session('error'))
      <div class="max-w-4xl mx-auto bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
        {{ session('error') }}
      </div>
    @endif

    <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-10">

      <!-- 左側：表單 -->
      <div>
        <form action="{{ route('checkout.store') }}" method="POST" id="checkout-form">
          @csrf

          <div class="bg-white p-6 rounded-lg shadow border mb-6">
            <h3 class="text-xl font-bold mb-4">收件人資訊</h3>

            <div class="mb-4">
              <label class="block text-gray-700 font-bold mb-2">姓名 <span class="text-red-500">*</span></label>
              <input type="text" name="customer_name" required value="{{ old('customer_name') }}"
                class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
              <label class="block text-gray-700 font-bold mb-2">電話 <span class="text-red-500">*</span></label>
              <input type="text" name="customer_phone" required value="{{ old('customer_phone') }}"
                class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
              <label class="block text-gray-700 font-bold mb-2">Email (選填)</label>
              <input type="email" name="customer_email" value="{{ old('customer_email') }}"
                class="w-full border rounded px-3 py-2">
            </div>

            <div class="mb-4">
              <label class="block text-gray-700 font-bold mb-2">收件地址 / 取貨方式 <span class="text-red-500">*</span></label>
              <textarea name="customer_address" required class="w-full border rounded px-3 py-2" rows="2"
                placeholder="請輸入寄送地址，或是填寫「店面自取」">{{ old('customer_address') }}</textarea>
            </div>

            <div class="mb-4">
              <label class="block text-gray-700 font-bold mb-2">備註</label>
              <textarea name="notes" class="w-full border rounded px-3 py-2" rows="2">{{ old('notes') }}</textarea>
            </div>
          </div>

          <div class="bg-white p-6 rounded-lg shadow border mb-6">
            <h3 class="text-xl font-bold mb-4">付款方式</h3>
            <div class="space-y-2">
              <label class="flex items-center gap-2 p-3 border rounded cursor-pointer hover:bg-gray-50">
                <input type="radio" name="payment_method" value="cod" checked>
                <span>貨到付款 / 現場付款</span>
              </label>
              <label class="flex items-center gap-2 p-3 border rounded cursor-pointer hover:bg-gray-50">
                <input type="radio" name="payment_method" value="bank_transfer">
                <span>銀行匯款 (ATM)</span>
              </label>
              <label class="flex items-center gap-2 p-3 border rounded cursor-pointer hover:bg-gray-50">
                <input type="radio" name="payment_method" value="ecpay">
                <span>信用卡 / 超商代碼 (綠界支付)</span>
              </label>
            </div>
          </div>

          <button type="submit"
            class="w-full bg-blue-600 text-white font-bold py-4 rounded-lg hover:bg-blue-700 transition shadow-lg text-lg">
            提交訂單 (NT$ {{ number_format($total) }})
          </button>
        </form>
      </div>

      <!-- 右側：訂單摘要 -->
      <div>
        <div class="bg-gray-50 p-6 rounded-lg border sticky top-24">
          <h3 class="text-xl font-bold mb-4 border-b pb-2">購買清單</h3>
          <ul class="space-y-4 mb-6">
            @foreach ($cartItems as $item)
              <li class="flex justify-between items-start">
                <div>
                  <div class="font-bold">{{ $item->product_name }}</div>
                  <div class="text-sm text-gray-500">{{ $item->variant_name }} x {{ $item->quantity }}</div>
                </div>
                <div class="font-bold">
                  NT$ {{ number_format($item->subtotal) }}
                </div>
              </li>
            @endforeach
          </ul>
          <div class="flex justify-between text-xl font-bold border-t pt-4">
            <span>總金額</span>
            <span class="text-red-600">NT$ {{ number_format($total) }}</span>
          </div>
        </div>
      </div>

    </div>
  </div>
@endsection
