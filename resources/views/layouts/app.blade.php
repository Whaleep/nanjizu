<!DOCTYPE html>
<html lang="zh-TW">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <!-- SEO Meta Tags -->
  <title>@yield('title', 'ABC - 專業手機維修 | 二手機買賣')</title>
  <meta name="description" content="@yield('description', '屏東專業手機維修推薦，iPhone、Android、MacBook 現場快速維修。提供透明報價、二手機買賣服務。')">
  <meta name="keywords" content="@yield('keywords', '手機維修, iPhone維修, 換電池, 螢幕破裂, 二手機, 屏東維修')">

  <!-- Open Graph / Facebook / Line (社群分享預覽) -->
  <meta property="og:type" content="website">
  <meta property="og:url" content="{{ url()->current() }}">
  <meta property="og:title" content="@yield('title', 'ABC - 專業手機維修')">
  <meta property="og:description" content="@yield('description', '屏東專業手機維修推薦，現場快速取件，價格透明。')">
  <meta property="og:image" content="@yield('og_image', asset('images/hero-bg.jpg'))"> {{-- 預設使用首頁大圖 --}}

  <!-- Favicon -->
  <link rel="icon" href="/images/logo.png" type="image/png">

  <!-- CSS & JS -->
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  <script src="https://unpkg.com/@alpinejs/collapse@3.x.x/dist/cdn.min.js"></script>
  <script src="//unpkg.com/alpinejs" defer></script>
</head>

<body class="bg-gray-50 flex flex-col min-h-screen text-gray-800">

  <!-- 導航列 (Navbar) -->
  <nav class="bg-white shadow sticky top-0 z-50" x-data="{ mobileMenuOpen: false }">
    <div class="container mx-auto px-4">
      <div class="flex justify-between items-center h-16">

        <!-- 1. 左側：Logo -->
        <div class="flex-shrink-0 flex items-center">
          <a href="/" class="flex items-center gap-2">
            <img src="/images/logo.png" alt="男機組 Logo" class="h-10 w-auto object-contain">
            <div class="flex flex-col">
              <span class="font-bold text-xl text-blue-600 tracking-wider leading-none">ABC</span>
              <span class="text-xs text-gray-500 font-normal hidden sm:inline-block leading-none mt-1">CER Phone
                Repair</span>
            </div>
          </a>
        </div>

        <!-- 2. 右側區塊：包含 電腦選單 + 購物車 + 手機漢堡 -->
        <div class="flex items-center gap-4">

          <!-- A. 電腦版連結 (手機版隱藏) -->
          <div class="hidden md:flex space-x-6 items-center">
            <a href="{{ route('v1.about') }}" class="nav-link {{ request()->routeIs('about') ? 'active' : '' }}">關於我們</a>
            <a href="{{ route('v1.news.index') }}"
              class="nav-link {{ request()->routeIs('news.*') ? 'active' : '' }}">最新消息</a>
            <a href="{{ route('v1.cases.index') }}"
              class="nav-link {{ request()->routeIs('cases.*') ? 'active' : '' }}">維修案例</a>
            <a href="{{ route('v1.repair.index') }}"
              class="nav-link {{ request()->routeIs('repair.*') ? 'active' : '' }}">維修報價</a>
            <a href="{{ route('v1.shop.index') }}"
              class="nav-link {{ request()->routeIs('shop.*') ? 'active' : '' }}">線上商店</a>
            <a href="{{ route('v1.process') }}"
              class="nav-link {{ request()->routeIs('process') ? 'active' : '' }}">送修流程</a>
            <a href="{{ route('v1.stores.index') }}"
              class="nav-link {{ request()->routeIs('stores.*') ? 'active' : '' }}">服務據點</a>
          </div>

          <!-- B. 購物車按鈕 (電腦/手機都顯示，且永遠在右側群組中) -->
          <div x-data="{ count: {{ array_sum(session('cart', [])) }} }" @cart-updated.window="count = $event.detail.count"
            class="relative flex items-center">
            <a href="{{ route('v1.cart.index') }}" class="p-2 text-gray-600 hover:text-blue-600 block relative">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z">
                </path>
              </svg>

              <span x-show="count > 0" x-text="count" x-cloak
                class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full border-2 border-white">
              </span>
            </a>
          </div>

          <!-- C. 手機版漢堡按鈕 (電腦版隱藏) -->
          <div class="flex items-center md:hidden">
            <button @click="mobileMenuOpen = !mobileMenuOpen"
              class="text-gray-600 hover:text-blue-600 focus:outline-none p-2">
              <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path x-show="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 6h16M4 12h16M4 18h16"></path>
                <path x-show="mobileMenuOpen" x-cloak stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"></path>
              </svg>
            </button>
          </div>

        </div> <!-- 右側區塊結束 -->
      </div>
    </div>

    <!-- 手機版下拉選單 (保持原樣，僅調整樣式) -->
    <div x-show="mobileMenuOpen" x-cloak x-transition:enter="transition ease-out duration-200"
      x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0"
      class="md:hidden bg-white border-t absolute w-full left-0 shadow-lg z-40">
      <div class="px-2 pt-2 pb-4 space-y-1">
        <a href="{{ route('v1.about') }}"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">關於我們</a>
        <a href="{{ route('v1.news.index') }}"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">最新消息</a>
        <a href="{{ route('v1.cases.index') }}"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">維修案例</a>
        <a href="{{ route('v1.repair.index') }}"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">維修報價</a>
        <a href="{{ route('v1.shop.index') }}"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">線上商店</a>
        <a href="{{ route('v1.process') }}"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">送修流程</a>
        <a href="{{ route('v1.stores.index') }}"
          class="block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50">服務據點</a>
      </div>
    </div>
  </nav>

  <!-- 這裡是挖空的區域，每個頁面的內容會填入這裡 -->
  <main class="flex-grow">
    @yield('content')
  </main>

  <!-- 全站共用頁尾 -->
  <footer class="bg-gray-800 text-gray-300 py-8 mt-auto">
    <div class="container mx-auto px-4">
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
        <div>
          <h4 class="text-white text-lg font-bold mb-4">關於ABC</h4>
          <p class="text-sm leading-relaxed">我們專注於提供高品質的手機、平板與電腦維修服務。透明報價，現場快速取件，是您最值得信賴的手機急診室。</p>
        </div>
        <div>
          <h4 class="text-white text-lg font-bold mb-4">快速連結</h4>
          <ul class="space-y-2 text-sm">
            <li><a href="{{ route('v1.repair.index') }}" class="hover:text-white">維修價格查詢</a></li>
            <li><a href="{{ route('v1.shop.index') }}" class="hover:text-white">線上商店</a></li>
            <li><a href="{{ route('v1.process') }}" class="hover:text-white">送修流程說明</a></li>
          </ul>
        </div>
        <div>
          <h4 class="text-white text-lg font-bold mb-4">聯絡我們</h4>
          <p class="text-sm mb-2">如有任何問題，歡迎親臨門市或來電洽詢。</p>
          <a href="{{ route('v1.stores.index') }}"
            class="inline-block bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700">
            查找最近分店
          </a>
        </div>
      </div>
      <div class="border-t border-gray-700 pt-8 text-center text-sm">
        <p>&copy; {{ date('Y') }} ABC CER Phone Repair. All rights reserved.</p>
      </div>
    </div>
  </footer>


  <!-- 懸浮聯絡按鈕 (Fixed Bottom Left) -->
  <div class="fixed bottom-6 left-6 z-50" x-data="{ open: false }">

    <!-- 展開後的選單列表 -->
    <div x-show="open" x-cloak x-transition:enter="transition ease-out duration-300"
      x-transition:enter-start="opacity-0 translate-y-10 scale-90"
      x-transition:enter-end="opacity-100 translate-y-0 scale-100"
      x-transition:leave="transition ease-in duration-200"
      x-transition:leave-start="opacity-100 translate-y-0 scale-100"
      x-transition:leave-end="opacity-0 translate-y-10 scale-90"
      class="flex flex-col-reverse gap-3 mb-4 items-center">

      <!-- Line -->
      <a href="https://line.me/ti/p/@yourlineid" target="_blank"
        class="w-12 h-12 bg-[#06C755] text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition"
        title="Line">
        <span class="font-bold text-xs">Line</span>
      </a>

      <!-- Facebook -->
      <a href="https://www.facebook.com/yourpage" target="_blank"
        class="w-12 h-12 bg-[#1877F2] text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition"
        title="Facebook">
        <span class="font-bold text-xs">FB</span>
      </a>

      <!-- Instagram -->
      <a href="https://instagram.com/yourig" target="_blank"
        class="w-12 h-12 bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-500 text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition"
        title="Instagram">
        <span class="font-bold text-xs">IG</span>
      </a>

      <!-- Threads -->
      <a href="https://www.threads.net/@yourthreads" target="_blank"
        class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition"
        title="Threads">
        <span class="font-bold text-xs">Th</span>
      </a>

      <!-- TikTok (抖音) -->
      <a href="https://www.tiktok.com/@yourtiktok" target="_blank"
        class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition border border-gray-700"
        title="TikTok">
        <span class="font-bold text-xs">Tik</span>
      </a>

      <!-- 電話 -->
      <a href="tel:0912345678"
        class="w-12 h-12 bg-green-600 text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition"
        title="撥打電話">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z">
          </path>
        </svg>
      </a>
    </div>

    <!-- 主要按鈕 (開關) -->
    <button @click="open = !open"
      class="w-14 h-14 bg-blue-600 text-white rounded-full flex items-center justify-center shadow-xl hover:bg-blue-700 transition transform hover:scale-105">
      <!-- 聯絡圖示 -->
      <svg x-show="!open" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
          d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z">
        </path>
      </svg>
      <!-- 關閉圖示 -->
      <svg x-show="open" x-cloak class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
      </svg>
    </button>
  </div>

</body>

</html>
