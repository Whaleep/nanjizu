@extends('layouts.shop')

@section('title', '搜尋結果: ' . $query . ' - 線上商店')

@section('shop_content')

  <nav class="text-sm text-gray-500 mb-6">
    <a href="{{ route('shop.index') }}" class="hover:text-blue-600">商店首頁</a>
    <span>/</span>
    <span class="text-gray-900 font-bold">搜尋 "{{ $query }}"</span>
  </nav>

  <h1 class="text-3xl font-bold mb-8">搜尋結果：{{ $query }}</h1>

  <!-- 搜尋框 (讓使用者可以再次搜尋) -->
  <form action="{{ route('shop.index') }}" method="GET" class="max-w-md mx-auto mb-10">
    <div class="relative">
      <input type="text" name="q" value="{{ $query }}"
        class="w-full border-2 border-gray-200 rounded-full pl-5 pr-12 py-3 focus:outline-none focus:border-blue-500 transition"
        placeholder="搜尋商品...">
      <button type="submit"
        class="absolute right-2 top-2 bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </button>
    </div>
  </form>

  @if ($products->count() > 0)
    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
      @foreach ($products as $product)
        <a href="{{ route('shop.product', $product->slug) }}"
          class="bg-white border rounded-lg overflow-hidden hover:shadow-lg transition group">
          <div class="aspect-square bg-gray-200 overflow-hidden relative">
            @if ($product->image)
              <img src="/storage/{{ $product->image }}"
                class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
            @else
              <div class="flex items-center justify-center h-full text-gray-400">無圖片</div>
            @endif
          </div>
          <!-- 價格顯示區塊 -->
          <div class="p-4">
            <h3 class="font-bold text-lg mb-2 group-hover:text-blue-600">{{ $product->name }}</h3>

            @php
              $min = $product->variants->min('price');
              $max = $product->variants->max('price');
            @endphp

            <p class="text-red-600 font-bold">
              @if ($min == $max)
                NT$ {{ number_format($min) }}
              @else
                NT$ {{ number_format($min) }} - {{ number_format($max) }}
              @endif
            </p>
          </div>
        </a>
      @endforeach
    </div>
    <div class="mt-8">{{ $products->withQueryString()->links() }}</div>
  @else
    <div class="py-20 text-center bg-gray-50 rounded-lg">
      <p class="text-gray-500 text-xl">找不到與 "{{ $query }}" 相關的商品。</p>
    </div>
  @endif

@endsection
