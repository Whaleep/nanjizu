@extends('layouts.shop')

@section('title', '線上商店 - ABC')

@section('shop_content')
  <h1 class="text-3xl font-bold text-center mb-10">線上商店</h1>

  <!-- 搜尋框 -->
  <form action="{{ route('v1.shop.index') }}" method="GET" class="max-w-md mx-auto mb-12">
    <div class="relative">
      <input type="text" name="q"
        class="w-full border-2 border-gray-200 rounded-full pl-5 pr-12 py-3 focus:outline-none focus:border-blue-500 transition"
        placeholder="搜尋手機型號或配件...">
      <button type="submit"
        class="absolute right-2 top-2 bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
      </button>
    </div>
  </form>

  <!-- 熱門分類 -->
  <div class="mb-12">
    <h2 class="text-2xl font-bold mb-6 text-gray-800">瀏覽分類</h2>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
      @foreach ($categories as $category)
        <a href="{{ route('v1.shop.category', $category->slug) }}"
          class="block bg-white border rounded-lg p-4 text-center hover:shadow-md hover:border-blue-500 transition">
          <span class="font-medium text-gray-700">{{ $category->name }}</span>
        </a>
      @endforeach
    </div>
  </div>

  <!-- 所有商品列表 -->
  <div>
    <h2 class="text-2xl font-bold mb-6 text-gray-800">所有商品</h2>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-4 gap-6">
      @foreach ($products as $product)
        <a href="{{ route('v1.shop.product', $product->slug) }}"
          class="bg-white border rounded-lg overflow-hidden hover:shadow-lg transition group flex flex-col">
          <!-- 圖片區塊 -->
          <div class="aspect-square bg-gray-100 overflow-hidden relative">
            @if ($product->image)
              <img src="/storage/{{ $product->image }}"
                class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
            @else
              <div class="flex items-center justify-center h-full text-gray-400">無圖片</div>
            @endif
          </div>
          <!-- 資訊區塊 -->
          <div class="p-4 flex-grow flex flex-col justify-between">
            <div>
              <div class="text-xs text-gray-500 mb-1">{{ $product->category->name ?? '' }}</div>
              <h3 class="font-bold text-lg mb-2 group-hover:text-blue-600 leading-tight">{{ $product->name }}</h3>
            </div>

            <!-- 價格顯示 -->
            @php
              $min = $product->variants->min('price');
              $max = $product->variants->max('price');
            @endphp
            <p class="text-red-600 font-bold mt-2">
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

    <!-- 分頁連結 -->
    <div class="mt-12">
      {{ $products->links() }}
    </div>
  </div>

@endsection
