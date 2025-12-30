@extends('layouts.app')

@section('title', $device->name . ' 維修價格表 - ABC')
@section('description', '提供 ' . $device->name . ' 完整維修報價，包含換螢幕、換電池、主機板維修等項目。現場快速維修，價格透明。')
@section('keywords', $device->name . '維修, ' . $device->brand->name . '維修, 換電池, 換螢幕')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- 麵包屑導航 -->
    <div class="mb-6">
        <a href="{{ route('repair.index') }}" class="text-blue-600 hover:underline flex items-center gap-1">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
            返回選擇機型
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

        <!-- 左側：報價單 -->
        <div class="lg:col-span-2">
            <div class="flex items-baseline gap-4 mb-2">
                 <h1 class="text-3xl font-bold">{{ $device->name }}</h1>
                 <span class="text-gray-500 text-lg">維修價目表</span>
            </div>

            <p class="text-gray-500 mb-6 text-sm bg-yellow-50 p-3 rounded border border-yellow-200">
                ⚠️ 價格僅供參考，實際狀況請以現場工程師檢測為主。
            </p>

            <div class="bg-white shadow rounded-lg overflow-hidden border">
                <table class="w-full text-left border-collapse">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="p-4 border-b font-bold text-gray-700">維修項目</th>
                            <th class="p-4 border-b font-bold text-gray-700">價格</th>
                            <th class="p-4 border-b font-bold text-gray-700">備註</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($device->prices as $price)
                            <tr class="hover:bg-blue-50 transition">
                                <td class="p-4 border-b font-medium">{{ $price->repairItem->name }}</td>
                                <td class="p-4 border-b text-red-600 font-bold">NT$ {{ number_format($price->price) }}</td>
                                <td class="p-4 border-b text-sm text-gray-500">{{ $price->note }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="p-8 text-center text-gray-500">
                                    目前尚無公開報價，請直接填寫右側表單諮詢。
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- 右側：預約表單 -->
        <div class="lg:col-span-1">
            <div class="bg-white p-6 shadow-lg rounded-lg sticky top-24 border-t-4 border-blue-600">
                <h3 class="text-xl font-bold mb-4 flex items-center gap-2">
                    <span>📅</span> 線上預約 / 諮詢
                </h3>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 text-sm">
                        {{ session('success') }}
                    </div>
                @endif

                <form action="{{ route('inquiry.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="device_model" value="{{ $device->brand->name }} {{ $device->name }}">

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">您的姓名</label>
                        <input type="text" name="customer_name" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">聯絡電話</label>
                        <input type="text" name="phone" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 text-sm font-bold mb-2">故障狀況描述</label>
                        <textarea name="message" rows="3" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500" placeholder="例如：螢幕破裂、無法充電..."></textarea>
                    </div>

                    <button type="submit" class="w-full bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700 transition shadow">
                        送出預約
                    </button>
                    <p class="text-xs text-gray-400 mt-2 text-center">收到後我們將盡快與您聯繫</p>
                </form>
            </div>
        </div>

    </div>
</div>
@endsection
