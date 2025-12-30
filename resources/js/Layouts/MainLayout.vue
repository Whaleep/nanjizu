<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

// 取得全域共享資料 (Shared Data)
const page = usePage();
// 注意：在 template 中可以直接用 $page.props.cartCount 存取，script 中用 page.props

// 手機版選單開關
const mobileMenuOpen = ref(false);
</script>

<template>
    <div class="bg-gray-50 min-h-screen flex flex-col font-sans text-gray-900">

        <!-- === Navbar === -->
        <nav class="bg-white shadow sticky top-0 z-50">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center h-16">

                    <!-- Logo -->
                    <div class="flex-shrink-0 flex items-center">
                        <Link href="/" class="flex items-center gap-2">
                            <img src="/images/logo.png" alt="Logo" class="h-10 w-auto object-contain">
                            <div class="flex flex-col">
                                <span class="font-bold text-xl text-blue-600 tracking-wider leading-none">ABC</span>
                                <span class="text-xs text-gray-500 font-normal hidden sm:inline-block leading-none mt-1">CER Phone Repair</span>
                            </div>
                        </Link>
                    </div>

                    <!-- 右側功能區 -->
                    <div class="flex items-center gap-4">

                        <!-- 電腦版選單 -->
                        <div class="hidden md:flex space-x-6 items-center">
                            <Link href="/about" class="nav-link" :class="{ 'active': $page.url === '/about' }">關於我們</Link>
                            <Link href="/news" class="nav-link" :class="{ 'active': $page.url.startsWith('/news') }">最新消息</Link>
                            <Link href="/cases" class="nav-link" :class="{ 'active': $page.url.startsWith('/cases') }">維修案例</Link>
                            <Link href="/repair" class="nav-link" :class="{ 'active': $page.url.startsWith('/repair') }">維修報價</Link>
                            <Link href="/shop" class="nav-link" :class="{ 'active': $page.url.startsWith('/shop') }">線上商店</Link>
                            <Link href="/process" class="nav-link" :class="{ 'active': $page.url === '/process' }">送修流程</Link>
                            <Link href="/stores" class="nav-link" :class="{ 'active': $page.url === '/stores' }">服務據點</Link>
                        </div>

                        <!-- 購物車圖示 (即時更新) -->
                        <Link href="/cart" class="relative p-2 text-gray-600 hover:text-blue-600 transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>

                            <!-- 紅點 -->
                            <span v-if="$page.props.cartCount > 0"
                                  class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full border-2 border-white">
                                {{ $page.props.cartCount }}
                            </span>
                        </Link>

                        <!-- 手機版漢堡按鈕 -->
                        <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-gray-600 p-2 focus:outline-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!mobileMenuOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path>
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <!-- 手機版下拉選單 -->
            <div v-show="mobileMenuOpen" class="md:hidden bg-white border-t">
                <div class="px-2 pt-2 pb-4 space-y-1">
                    <Link href="/about" class="mobile-link">關於我們</Link>
                    <Link href="/news" class="mobile-link">最新消息</Link>
                    <Link href="/cases" class="mobile-link">維修案例</Link>
                    <Link href="/repair" class="mobile-link">維修報價</Link>
                    <Link href="/shop" class="mobile-link">線上商店</Link>
                    <Link href="/process" class="mobile-link">送修流程</Link>
                    <Link href="/stores" class="mobile-link">服務據點</Link>
                </div>
            </div>
        </nav>


        <!-- === 主要內容 (Slot) === -->
        <main class="flex-grow">
            <!-- 這裡就是每一頁不同的內容會插入的地方 -->
            <slot />
        </main>

        <!-- === Footer === -->
        <footer class="bg-gray-800 text-gray-300 py-12 mt-auto">
            <div class="container mx-auto px-4">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
                    <!-- 關於我們 -->
                    <div>
                        <h4 class="text-white text-lg font-bold mb-4">關於男機組</h4>
                        <p class="text-sm leading-relaxed text-gray-400">
                            我們專注於提供高品質的手機、平板與電腦維修服務。透明報價，現場快速取件，是您最值得信賴的手機急診室。
                        </p>
                    </div>

                    <!-- 快速連結 -->
                    <div>
                        <h4 class="text-white text-lg font-bold mb-4">快速連結</h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <li><Link href="/repair" class="hover:text-white transition">維修價格查詢</Link></li>
                            <li><Link href="/shop" class="hover:text-white transition">線上商店</Link></li>
                            <li><Link href="/process" class="hover:text-white transition">送修流程說明</Link></li>
                            <li><Link href="/about" class="hover:text-white transition">關於我們</Link></li>
                        </ul>
                    </div>

                    <!-- 聯絡我們 -->
                    <div>
                        <h4 class="text-white text-lg font-bold mb-4">聯絡我們</h4>
                        <p class="text-sm text-gray-400 mb-2">如有任何問題，歡迎親臨門市或來電洽詢。</p>
                        <Link href="/stores" class="inline-block bg-blue-600 text-white px-4 py-2 rounded text-sm hover:bg-blue-700 transition mt-2">
                            查找最近分店
                        </Link>
                    </div>
                </div>

                <div class="border-t border-gray-700 pt-8 text-center text-sm text-gray-500">
                    <p>&copy; {{ new Date().getFullYear() }} 男機組 CER Phone Repair. All rights reserved.</p>
                </div>
            </div>
        </footer>

    </div>
</template>

<style scoped>
.nav-link {
    @apply text-gray-600 hover:text-blue-600 font-medium transition;
}
.nav-link.active {
    @apply text-blue-600 font-bold;
}
.mobile-link {
    @apply block px-3 py-2 rounded-md text-base font-medium text-gray-700 hover:text-blue-600 hover:bg-gray-50;
}
</style>
