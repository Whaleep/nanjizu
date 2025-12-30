@extends('layouts.app')

@section('title', '購物車 - 男機組')

@section('content')
  <div class="container mx-auto px-4 py-12" x-data="cartManager()">
    <h1 class="text-3xl font-bold mb-8 flex items-center gap-2">
      <span>🛒</span> 您的購物車
    </h1>

    @if ($cartItems->count() > 0)
      <div class="flex flex-col lg:flex-row gap-10">
        <!-- 購物車列表 -->
        <div class="lg:w-2/3">
          <div class="bg-white shadow rounded-lg overflow-hidden border">
            <table class="w-full text-left">
              <thead class="bg-gray-50 border-b">
                <tr>
                  <th class="p-4 font-bold text-gray-600">商品資訊</th>
                  <th class="p-4 font-bold text-gray-600">單價</th>
                  <th class="p-4 font-bold text-gray-600 text-center">數量</th>
                  <th class="p-4 font-bold text-gray-600 text-right">小計</th>
                  <th class="p-4 font-bold text-gray-600"></th>
                </tr>
              </thead>
              <tbody>
                @foreach ($cartItems as $item)
                  <tr class="border-b last:border-0 hover:bg-gray-50 transition" id="row-{{ $item->variant_id }}">
                    <td class="p-4">
                      <div class="flex items-center gap-4">
                        <div class="w-16 h-16 bg-gray-100 rounded overflow-hidden flex-shrink-0 border">
                          @if ($item->image)
                            <img src="/storage/{{ $item->image }}" class="w-full h-full object-cover">
                          @else
                            <div class="w-full h-full flex items-center justify-center text-xs text-gray-400">無圖</div>
                          @endif
                        </div>
                        <div>
                          <h3 class="font-bold text-gray-900">{{ $item->product_name }}</h3>
                          <p class="text-sm text-gray-500">{{ $item->variant_name }}</p>
                        </div>
                      </div>
                    </td>
                    <td class="p-4 text-gray-700">NT$ {{ number_format($item->price) }}</td>
                    <td class="p-4 text-center">
                      <!-- AJAX 數量調整 -->
                      <input type="number" value="{{ $item->quantity }}" min="1" max="{{ $item->stock }}"
                        @change="updateQuantity('{{ $item->variant_id }}', $event.target.value)"
                        class="w-16 border rounded text-center py-1 focus:ring-blue-500 focus:border-blue-500">
                    </td>
                    <td class="p-4 text-right font-bold text-gray-900">
                      <!-- 給一個 ID 方便 JS 更新內容 -->
                      NT$ <span id="subtotal-{{ $item->variant_id }}">{{ number_format($item->subtotal) }}</span>
                    </td>
                    <td class="p-4 text-right">
                      <button @click="removeItem('{{ $item->variant_id }}')"
                        class="text-red-500 hover:text-red-700 text-sm font-bold transition">
                        移除
                      </button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>

        <!-- 結帳區塊 -->
        <div class="lg:w-1/3">
          <div class="bg-white shadow rounded-lg p-6 border sticky top-24">
            <h3 class="text-lg font-bold mb-4 border-b pb-2">訂單摘要</h3>
            <div class="flex justify-between mb-2 text-gray-600">
              <span>商品總計</span>
              <span>NT$ <span x-text="total"></span></span>
            </div>
            <div class="flex justify-between mb-6 text-xl font-bold text-gray-900 border-t pt-4">
              <span>總金額</span>
              <span class="text-red-600">NT$ <span x-text="total"></span></span>
            </div>

            <a href="{{ route('checkout.index') }}"
              class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-bold hover:bg-blue-700 transition shadow-lg">
              前往結帳
            </a>
            <a href="{{ route('shop.index') }}" class="block w-full text-center py-3 mt-2 text-gray-500 hover:underline">
              繼續購物
            </a>
          </div>
        </div>
      </div>
    @else
      <!-- 空購物車畫面 (後端判斷即可) -->
      <div class="text-center py-20 bg-gray-50 rounded-lg border border-dashed border-gray-300">
        <div class="text-6xl mb-4">🛒</div>
        <p class="text-xl text-gray-500 mb-6">購物車是空的</p>
        <a href="{{ route('shop.index') }}"
          class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 transition">
          去商店逛逛
        </a>
      </div>
    @endif

    <!-- 操作成功提示 (Toast) -->
    <div x-show="toast.visible" x-cloak x-transition.opacity.duration.300ms
      class="fixed bottom-4 right-4 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-xl z-50 flex items-center gap-3">
      <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
      </svg>
      <span x-text="toast.message"></span>
    </div>
  </div>

  <script>
    function cartManager() {
      return {
        total: '{{ number_format($total) }}',

        // 新增 Toast 狀態
        toast: {
          visible: false,
          message: ''
        },

        // 顯示 Toast 的輔助函式
        showToast(message) {
          this.toast.message = message;
          this.toast.visible = true;
          setTimeout(() => {
            this.toast.visible = false;
          }, 2000); // 2秒後自動消失
        },

        updateQuantity(variantId, newQty) {
          if (newQty < 1) return;

          fetch('{{ route('cart.update') }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              body: JSON.stringify({
                variant_id: variantId,
                quantity: newQty
              })
            })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                document.getElementById('subtotal-' + variantId).innerText = data.itemSubtotal;
                this.total = data.total;
                window.dispatchEvent(new CustomEvent('cart-updated', {
                  detail: {
                    count: data.cartCount
                  }
                }));

                // === 觸發提示 ===
                this.showToast('已更新數量');
              } else {
                alert(data.message || '更新失敗');
                location.reload();
              }
            });
        },

        removeItem(variantId) {
          if (!confirm('確定要移除此商品嗎？')) return;

          fetch('{{ route('cart.remove') }}', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
              },
              body: JSON.stringify({
                variant_id: variantId
              })
            })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                const row = document.getElementById('row-' + variantId);
                if (row) row.remove();
                this.total = data.total;
                window.dispatchEvent(new CustomEvent('cart-updated', {
                  detail: {
                    count: data.cartCount
                  }
                }));

                if (data.cartCount == 0) location.reload();

                // === 觸發提示 ===
                this.showToast('商品已移除');
              }
            });
        }
      }
    }
  </script>
@endsection
