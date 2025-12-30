@extends('layouts.shop')

@section('title', $category->name . ' - 線上商店')

@section('shop_content')

  <!-- 新增：搜尋框 (與首頁一致) -->
  <form action="{{ route('shop.index') }}" method="GET" class="max-w-md mx-auto mb-8">
    <div class="relative">
      <input type="text" name="q"
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

  <!-- 麵包屑導航 (Breadcrumbs) -->
  <nav class="text-sm text-gray-500 mb-6 flex flex-wrap items-center gap-2">
    <a href="{{ route('shop.index') }}" class="hover:text-blue-600">商店</a>
    <span>/</span>

    @foreach ($category->ancestors as $ancestor)
      @if (!$loop->last)
        <a href="{{ route('shop.category', $ancestor->slug) }}" class="hover:text-blue-600">{{ $ancestor->name }}</a>
        <span>/</span>
      @else
        <!-- 最後一層 (自己) 不用連結 -->
        <span class="text-gray-900 font-bold">{{ $ancestor->name }}</span>
      @endif
    @endforeach
  </nav>

  <h1 class="text-3xl font-bold mb-8">{{ $category->name }}</h1>

  <!-- 情況 A: 如果有子分類，顯示子分類 -->
  @if ($subcategories->count() > 0)
    <div class="mb-12">
      <h3 class="text-xl font-bold mb-4 border-l-4 border-blue-600 pl-3">子分類</h3>
      <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
        @foreach ($subcategories as $sub)
          <a href="{{ route('shop.category', $sub->slug) }}"
            class="block bg-white border p-4 rounded-lg text-center hover:shadow-md hover:border-blue-500 transition">
            <span class="font-medium">{{ $sub->name }}</span>
          </a>
        @endforeach
      </div>
    </div>
  @endif

  <!-- 標籤篩選區 -->
  @if ($tags->count() > 0)
    <div class="mb-8 flex flex-wrap gap-2">
      <a href="{{ route('shop.category', $category->slug) }}"
        class="px-4 py-1 rounded-full border {{ !request('tag') ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-500' }}">
        全部
      </a>
      @foreach ($tags as $tag)
        <a href="{{ route('shop.category', [$category->slug, 'tag' => $tag->slug]) }}"
          class="px-4 py-1 rounded-full border {{ request('tag') == $tag->slug ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 border-gray-300 hover:border-blue-500' }}">
          {{ $tag->name }}
        </a>
      @endforeach
    </div>
  @endif

  <!-- 情況 B: 如果有商品，顯示商品列表 -->
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

    <div class="mt-8">
      {{ $products->links() }}
    </div>
  @else
    @if ($subcategories->count() == 0)
      <p class="text-gray-500 py-10 text-center">此分類暫無商品。</p>
    @endif
  @endif

@endsection
