@extends('layouts.app')

@section('content')
  <div class="bg-gray-50 min-h-screen flex flex-col" x-data="{ shopSidebarOpen: false }">

    <!-- === 商店專屬導航列 (Level 2 Navbar) - Desktop Only === -->
    <div class="bg-white border-b hidden md:block shadow-sm sticky top-16 z-40">
      <div class="container mx-auto px-4">
        <ul class="flex space-x-8 text-sm font-medium">

          @foreach ($menuItems as $item)
            <li class="relative group py-3">

              {{-- 判斷連結目標 --}}
              @php
                $url = '#';
                $hasChildren = false;
                $children = collect([]);

                if ($item->type === 'category') {
                    $cat = \App\Models\ShopCategory::find($item->target_id);
                    if ($cat) {
                        $url = route('v1.shop.category', $cat->slug);
                        $children = $cat->children()->where('is_visible', true)->orderBy('sort_order')->get();
                        $hasChildren = $children->isNotEmpty();
                    }
                } elseif ($item->type === 'tag') {
                    $tag = \App\Models\ProductTag::find($item->target_id);
                    if ($tag) {
                        // 這裡需要支援 tag 路由，或是帶參數的 category 路由
                        // 暫時導向 shop.index 帶 tag 參數，或者您需要新建一個 route('shop.tag', $slug)
                        // 簡單作法：搜尋頁帶 tag 參數
                        $url = route('v1.shop.index', ['tag' => $tag->slug]);
                    }
                } elseif ($item->type === 'link') {
                    $url = $item->url;
                }
              @endphp

              <a href="{{ $url }}" class="flex items-center gap-1 hover:text-blue-600 transition">
                {{ $item->name }}
                @if ($hasChildren)
                  <svg class="w-3 h-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                  </svg>
                @endif
              </a>

              {{-- 下拉選單 (只針對分類有效) --}}
              @if ($hasChildren)
                <div class="absolute left-0 top-full pt-1 hidden group-hover:block w-48 z-50">
                  <div class="bg-white border shadow-xl rounded-lg py-2">
                    @foreach ($children as $child)
                      <a href="{{ route('v1.shop.category', $child->slug) }}"
                        class="block px-4 py-2 text-gray-700 hover:bg-blue-50 hover:text-blue-600">
                        {{ $child->name }}
                      </a>
                    @endforeach
                  </div>
                </div>
              @endif
            </li>
          @endforeach

        </ul>
      </div>
    </div>

    <!-- === 手機版側滑選單按鈕 (Mobile Only) === -->
    <div class="md:hidden bg-white border-b p-3 flex items-center justify-between sticky top-16 z-30 shadow-sm">
      <button @click="shopSidebarOpen = true"
        class="flex items-center gap-2 text-gray-700 font-bold bg-gray-100 px-4 py-2 rounded-lg">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path>
        </svg>
        <span>商品分類</span>
      </button>

      <!-- 手機版也可顯示簡單的搜尋按鈕或購物車數量 -->
      <div class="text-sm text-gray-500">
        瀏覽商店
      </div>
    </div>

    <div class="container mx-auto px-4 py-6 flex gap-8 items-start">

      <!-- === 左側邊欄 (Desktop Sidebar) === -->
      <aside class="hidden md:block w-64 flex-shrink-0 bg-white rounded-lg shadow-sm border p-4 sticky top-32">
        <h3 class="font-bold text-lg mb-4 border-b pb-2 text-gray-800">商店選單</h3>

        <ul class="space-y-1">
          @foreach ($menuItems as $item)
            {{-- 邏輯判斷區塊：準備資料 (與手機版共用邏輯) --}}
            @php
              $url = '#';
              $children = collect([]);
              $hasChildren = false;

              if ($item->type === 'category') {
                  $cat = \App\Models\ShopCategory::find($item->target_id);
                  if ($cat) {
                      $url = route('v1.shop.category', $cat->slug);
                      // 取得子分類
                      $children = $cat->children()->where('is_visible', true)->orderBy('sort_order')->get();
                      $hasChildren = $children->isNotEmpty();
                  }
              } elseif ($item->type === 'tag') {
                  $tag = \App\Models\ProductTag::find($item->target_id);
                  if ($tag) {
                      // 導向帶 tag 參數的首頁 (或搜尋頁)
                      $url = route('v1.shop.index', ['tag' => $tag->slug]);
                  }
              } elseif ($item->type === 'link') {
                  $url = $item->url;
              }
            @endphp

            <li x-data="{ expanded: false }">
              <div class="flex justify-between items-center group hover:bg-gray-50 p-2 rounded transition">
                {{-- 主連結 --}}
                <a href="{{ $url }}" class="font-medium text-gray-700 hover:text-blue-600 flex-grow">
                  {{ $item->name }}
                </a>

                {{-- 展開按鈕 (僅當有子分類時顯示) --}}
                @if ($hasChildren)
                  <button class="p-1 text-gray-400 hover:text-gray-600 focus:outline-none"
                    @click.prevent="expanded = !expanded">
                    <svg :class="expanded ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none"
                      stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                @endif
              </div>

              {{-- 子選單 (Collapse) --}}
              @if ($hasChildren)
                <ul x-show="expanded" x-collapse class="pl-4 mt-1 space-y-1 text-sm border-l-2 border-gray-100 ml-2">
                  @foreach ($children as $child)
                    <li>
                      <a href="{{ route('v1.shop.category', $child->slug) }}"
                        class="block py-1.5 px-2 text-gray-600 hover:text-blue-600 rounded hover:bg-gray-50 transition">
                        {{ $child->name }}
                      </a>
                    </li>
                  @endforeach
                </ul>
              @endif
            </li>
          @endforeach
        </ul>
      </aside>

      <!-- === 手機版抽屜式選單 (Mobile Drawer) === -->
      <!-- 遮罩 -->
      <div x-show="shopSidebarOpen" class="fixed inset-0 bg-black bg-opacity-50 z-50 md:hidden" x-transition.opacity
        @click="shopSidebarOpen = false" x-cloak></div>

      <!-- 抽屜本體 -->
      <div x-show="shopSidebarOpen"
        class="fixed inset-y-0 left-0 w-4/5 max-w-xs bg-white shadow-xl z-50 md:hidden overflow-y-auto transform transition-transform duration-300"
        x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
        x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" x-cloak>

        <div class="p-4 border-b flex justify-between items-center bg-gray-50">
          <h2 class="font-bold text-lg text-gray-800">商店選單</h2>
          <button @click="shopSidebarOpen = false" class="text-gray-500 hover:text-gray-800">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <ul class="p-4 space-y-4">
          @foreach ($menuItems as $item)
            {{-- 邏輯判斷區塊：準備資料 --}}
            @php
              $url = '#';
              $children = collect([]);
              $hasChildren = false;

              if ($item->type === 'category') {
                  $cat = \App\Models\ShopCategory::find($item->target_id);
                  if ($cat) {
                      $url = route('v1.shop.category', $cat->slug);
                      $children = $cat->children()->where('is_visible', true)->orderBy('sort_order')->get();
                      $hasChildren = $children->isNotEmpty();
                  }
              } elseif ($item->type === 'tag') {
                  $tag = \App\Models\ProductTag::find($item->target_id);
                  if ($tag) {
                      // 導向帶 tag 參數的首頁 (或搜尋頁)
                      $url = route('v1.shop.index', ['tag' => $tag->slug]);
                  }
              } elseif ($item->type === 'link') {
                  $url = $item->url;
              }
            @endphp

            <li x-data="{ expanded: false }">
              <div class="flex justify-between items-center text-base">
                {{-- 主選單連結 --}}
                <a href="{{ $url }}" class="font-bold text-gray-800 flex-grow py-2">
                  {{ $item->name }}
                </a>

                {{-- 如果有子選單 (僅限分類)，顯示展開箭頭 --}}
                @if ($hasChildren)
                  <button @click="expanded = !expanded" class="p-2 text-gray-500">
                    <svg :class="expanded ? 'rotate-180' : ''" class="w-5 h-5 transition-transform" fill="none"
                      stroke="currentColor" viewBox="0 0 24 24">
                      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                  </button>
                @endif
              </div>

              {{-- 子選單列表 --}}
              @if ($hasChildren)
                <ul x-show="expanded" x-collapse
                  class="pl-4 mt-1 space-y-2 border-l-2 border-gray-200 ml-2 bg-gray-50 rounded-r-lg">
                  {{-- 再次顯示父分類連結，方便使用者點擊 --}}
                  <li class="py-2 pr-2 border-b border-gray-200">
                    <a href="{{ $url }}" class="text-blue-600 text-sm font-bold flex items-center gap-1">
                      查看全部 {{ $item->name }} &rarr;
                    </a>
                  </li>

                  @foreach ($children as $child)
                    <li class="py-1 pr-2">
                      <a href="{{ route('v1.shop.category', $child->slug) }}"
                        class="block text-gray-600 text-sm hover:text-blue-600">
                        {{ $child->name }}
                      </a>
                    </li>
                  @endforeach
                </ul>
              @endif
            </li>
          @endforeach
        </ul>
      </div>

      <!-- === 右側內容區 (Slot) === -->
      <main class="flex-grow w-full min-w-0">
        @yield('shop_content')
      </main>

    </div>
  </div>
@endsection
