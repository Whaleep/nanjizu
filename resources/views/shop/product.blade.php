@extends('layouts.shop')

@section('title', $product->name . ' - ABC')

@section('shop_content')
  <div class="container mx-auto px-4 py-12" x-data="productData({{ $product->variants }})">

    <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
      <!-- 左側：圖片 -->
      <div class="bg-gray-100 rounded-2xl overflow-hidden border aspect-square flex items-center justify-center">
        @if ($product->image)
          <img src="/storage/{{ $product->image }}" class="w-full h-full object-cover">
        @else
          <span class="text-gray-400 text-4xl">No Image</span>
        @endif
      </div>

      <!-- 右側：資訊與操作 -->
      <div>
        <!-- 麵包屑 -->
        <nav class="text-sm text-gray-500 mb-6 flex flex-wrap items-center gap-2">
          <a href="{{ route('shop.index') }}" class="hover:text-blue-600">商店</a>
          <span>/</span>

          @foreach ($product->category->ancestors as $ancestor)
            @if (!$loop->last)
              <a href="{{ route('shop.category', $ancestor->slug) }}"
                class="hover:text-blue-600">{{ $ancestor->name }}</a>
              <span>/</span>
            @else
              <!-- 最後一層 (自己) 不用連結 -->
              <span class="text-gray-900 font-bold">{{ $ancestor->name }}</span>
            @endif
          @endforeach
        </nav>

        <h1 class="text-3xl font-bold mb-4">{{ $product->name }}</h1>

        <!-- 新增：顯示全系列價格範圍 -->
        <p class="text-gray-500 text-sm mb-4">
          全系列價格：
          @if ($product->variants->min('price') == $product->variants->max('price'))
            NT$ {{ number_format($product->variants->min('price')) }}
          @else
            NT$ {{ number_format($product->variants->min('price')) }} ~
            {{ number_format($product->variants->max('price')) }}
          @endif
        </p>

        <!-- 價格顯示 (動態顯示選中規格的價格) -->
        <div class="text-3xl text-red-600 font-bold mb-6">
          NT$ <span x-text="formatPrice(selectedVariant.price)"></span>
        </div>

        <!-- 規格選擇器 -->
        <div class="mb-8">
          <h3 class="text-sm font-bold text-gray-700 mb-3">規格 / 樣式</h3>
          <div class="flex flex-wrap gap-3">
            <template x-for="variant in variants" :key="variant.id">
              <button @click="selectedVariant = variant"
                :class="selectedVariant.id === variant.id ? 'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600' :
                    'border-gray-200 hover:border-gray-300 text-gray-700'"
                class="px-4 py-2 border rounded-lg font-medium transition relative" :disabled="variant.stock <= 0">

                <span x-text="variant.name"></span>

                <!-- 缺貨標示 -->
                <template x-if="variant.stock <= 0">
                  <span class="absolute -top-2 -right-2 bg-gray-500 text-white text-xs px-1 rounded">缺貨</span>
                </template>
              </button>
            </template>
          </div>
        </div>

        <!-- 庫存狀態 -->
        <div class="mb-8 text-sm">
          <p>
            庫存狀況：
            <span x-show="selectedVariant.stock > 0" class="text-green-600 font-bold">
              現貨供應 (<span x-text="selectedVariant.stock"></span> 件)
            </span>
            <span x-show="selectedVariant.stock <= 0" class="text-red-500 font-bold">
              暫無庫存
            </span>
          </p>
        </div>

        <!-- 修改後的購買區塊 -->
        <div x-data="{ isLoading: false }">
          <form @submit.prevent="addToCart" class="flex gap-4">
            <!-- 移除 @csrf，改在 fetch header 加入 -->

            <div class="w-32">
              <input type="number" x-model="quantity" min="1" :max="selectedVariant.stock"
                class="w-full border border-gray-300 rounded-lg px-4 py-3 text-center font-bold">
            </div>

            <button type="submit" :disabled="selectedVariant.stock <= 0 || isLoading"
              :class="selectedVariant.stock > 0 ? 'bg-blue-600 hover:bg-blue-700' : 'bg-gray-400 cursor-not-allowed'"
              class="flex-1 text-white font-bold py-3 rounded-lg transition shadow-lg flex items-center justify-center gap-2">

              <!-- Loading Spinner -->
              <svg x-show="isLoading" class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg"
                fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4">
                </circle>
                <path class="opacity-75" fill="currentColor"
                  d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
              </svg>

              <span x-text="isLoading ? '處理中...' : (selectedVariant.stock > 0 ? '加入購物車' : '暫無庫存')"></span>
            </button>
          </form>

          <!-- 簡單的成功訊息提示 (Toast) -->
          <div x-show="showSuccess" x-cloak x-transition.opacity.duration.500ms
            class="fixed bottom-4 right-4 bg-green-600 text-white px-6 py-3 rounded-lg shadow-xl z-50 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
            </svg>
            已加入購物車！
          </div>
        </div>

        <!-- 顯示操作反饋 (Flash Message) -->
        @if (session('success'))
          <div class="mt-4 p-4 bg-green-100 text-green-700 rounded border border-green-400">
            {{ session('success') }} <a href="{{ route('cart.index') }}" class="underline font-bold">查看購物車</a>
          </div>
        @endif
        @if (session('error'))
          <div class="mt-4 p-4 bg-red-100 text-red-700 rounded border border-red-400">
            {{ session('error') }}
          </div>
        @endif

        <!-- 商品描述 -->
        <div class="mt-10 border-t pt-8 prose max-w-none text-gray-600">
          <h3 class="text-lg font-bold text-gray-900 mb-4">商品介紹</h3>
          {!! $product->description !!}
        </div>
      </div>
    </div>
  </div>

  <!-- Alpine Script 更新 -->
  <script>
    function productData(variants) {
      return {
        variants: variants,
        selectedVariant: variants[0] || {},
        quantity: 1,
        isLoading: false,
        showSuccess: false,

        formatPrice(price) {
          return new Intl.NumberFormat('zh-TW').format(price);
        },

        addToCart() {
          if (this.selectedVariant.stock <= 0) return;

          this.isLoading = true;

          fetch('{{ route('cart.add') }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              body: JSON.stringify({
                variant_id: this.selectedVariant.id,
                quantity: this.quantity
              })
            })
            .then(response => response.json())
            .then(data => {
              this.isLoading = false;
              if (data.success) {
                // 1. 顯示成功訊息
                this.showSuccess = true;
                setTimeout(() => this.showSuccess = false, 3000);

                // 2. 發送事件通知 Navbar 更新購物車數量
                // 我們使用 window event 來做跨元件溝通
                window.dispatchEvent(new CustomEvent('cart-updated', {
                  detail: {
                    count: data.cartCount
                  }
                }));
              } else {
                alert('加入失敗，請重試');
              }
            })
            .catch(error => {
              this.isLoading = false;
              console.error('Error:', error);
            });
        }
      }
    }
  </script>
@endsection
