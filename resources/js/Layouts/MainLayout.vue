<script setup>
import { ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';

// 取得全域共享資料 (Shared Data)
const page = usePage();
// 手機版選單開關
const mobileMenuOpen = ref(false);
// 控制懸浮按鈕開關
const fabOpen = ref(false);

// 初始化邏輯 (優先讀取 localStorage) ===
const initCartCount = () => {
    // 嘗試從 localStorage 讀取
    const stored = typeof window !== 'undefined' ? localStorage.getItem('cartCount') : null;

    // 如果 localStorage 有值，就用它的；否則用後端傳來的 props；再沒有就是 0
    if (stored !== null) {
        return parseInt(stored);
    }
    return page.props.cartCount || 0;
};

const currentCartCount = ref(initCartCount());

// === 修改 2: 監聽更新事件 (寫入 localStorage) ===
const updateCartCount = (event) => {
    const newCount = event.detail.count;
    currentCartCount.value = newCount;
    // 同步寫入 localStorage
    localStorage.setItem('cartCount', newCount);
};

// 背景校正函式
const fetchCartCount = async () => {
    try {
        const response = await axios.get('/cart/count'); // 確保路由正確 (加上 v1 前綴如果有的話)
        // 只有當後端回傳的數字跟目前顯示的不一樣時，才更新 (避免不必要的閃爍)
        if (currentCartCount.value !== response.data.count) {
            currentCartCount.value = response.data.count;
            localStorage.setItem('cartCount', response.data.count);
        }
    } catch (error) {
        console.error('無法更新購物車數量', error);
    }
};

// 監聽 Props (當 Inertia 頁面正常跳轉時)
watch(() => page.props.cartCount, (newCount) => {
    // 如果 Props 有變動，也視為最新來源
    if (newCount !== undefined) {
        currentCartCount.value = newCount;
        localStorage.setItem('cartCount', newCount);
    }
});

onMounted(() => {
    window.addEventListener('cart-updated', updateCartCount);

    // 額外監聽 'storage' 事件：這是為了讓「多個分頁」同步
    // 如果使用者開了兩個分頁，在 A 分頁加購物車，B 分頁也會自動更新
    window.addEventListener('storage', (event) => {
        if (event.key === 'cartCount') {
            currentCartCount.value = parseInt(event.newValue);
        }
    });

    // 背景執行一次校正
    fetchCartCount();
});

onUnmounted(() => {
    window.removeEventListener('cart-updated', updateCartCount);
    // 記得移除 storage 監聽
    // window.removeEventListener('storage', ...); // 略，Vue 組件銷毀通常不需太擔心全域事件記憶體洩漏，但嚴謹點可移除
});
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
                            <span v-if="currentCartCount > 0"
                                  class="absolute top-0 right-0 inline-flex items-center justify-center px-1.5 py-0.5 text-xs font-bold leading-none text-white transform translate-x-1/4 -translate-y-1/4 bg-red-600 rounded-full border-2 border-white">
                                {{ currentCartCount }}
                            </span>
                        </Link>

                        <!-- 使用者選單 -->
                        <div class="hidden md:flex items-center gap-4 border-l pl-4 ml-2">
                            <!-- 未登入：顯示登入/註冊 -->
                            <template v-if="!$page.props.auth.user">
                                <Link href="/login" class="text-sm font-bold text-gray-600 hover:text-blue-600">登入</Link>
                                <Link href="/register" class="text-sm font-bold bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">註冊</Link>
                            </template>

                            <!-- 已登入：顯示名字與登出 -->
                            <template v-else>
                                <div class="relative group">
                                    <button class="flex items-center gap-1 text-sm font-bold text-gray-700 hover:text-blue-600">
                                        {{ $page.props.auth.user.name }}
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                    </button>

                                    <!-- 下拉選單 -->
                                    <div class="absolute right-0 top-full pt-2 hidden group-hover:block w-48">
                                        <div class="bg-white border shadow-xl rounded-lg py-2">
                                            <Link href="/dashboard" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">會員中心</Link>

                                            <!-- 登出必須用 Link method="post" -->
                                            <Link href="/logout" method="post" as="button" class="block w-full text-left px-4 py-2 text-sm text-red-600 hover:bg-gray-50">
                                                登出
                                            </Link>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>



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
                        <h4 class="text-white text-lg font-bold mb-4">關於ABC</h4>
                        <p class="text-sm leading-relaxed text-gray-400">
                            我們專注於提供高品質的手機、平板與電腦維修服務。透明報價，現場快速取件，是您最值得信賴的手機急診室。
                        </p>
                    </div>

                    <!-- 快速連結 -->
                    <div>
                        <h4 class="text-white text-lg font-bold mb-4">快速連結</h4>
                        <ul class="space-y-2 text-sm text-gray-400">
                            <!-- <li><Link href="/repair" class="hover:text-white transition">維修價格查詢</Link></li> -->
                            <!-- <li><Link href="/shop" class="hover:text-white transition">線上商店</Link></li> -->
                            <li><Link href="/process" class="hover:text-white transition">送修流程說明</Link></li>
                            <li><Link href="/about" class="hover:text-white transition">關於我們</Link></li>
                            <li><Link href="/tracking" class="hover:text-white font-bold text-blue-400">訂單查詢</Link></li>
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
                    <p>&copy; {{ new Date().getFullYear() }} ABC CER Phone Repair. All rights reserved.</p>
                </div>
            </div>
        </footer>

        <!-- 懸浮聯絡按鈕 (Vue Version) -->
        <div class="fixed bottom-6 left-6 z-50 flex flex-col-reverse items-center gap-3">

            <!-- 展開後的選單列表 (使用 Vue Transition) -->
            <Transition
                enter-active-class="transition ease-out duration-300"
                enter-from-class="opacity-0 translate-y-10 scale-90"
                enter-to-class="opacity-100 translate-y-0 scale-100"
                leave-active-class="transition ease-in duration-200"
                leave-from-class="opacity-100 translate-y-0 scale-100"
                leave-to-class="opacity-0 translate-y-10 scale-90"
            >
                <div v-show="fabOpen" class="flex flex-col-reverse gap-3 items-center">

                    <!-- Line -->
                    <a href="https://line.me/ti/p/@yourlineid" target="_blank" class="w-12 h-12 bg-[#06C755] text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition" title="Line">
                        <span class="font-bold text-xs">Line</span>
                    </a>

                    <!-- Facebook -->
                    <a href="https://www.facebook.com/yourpage" target="_blank" class="w-12 h-12 bg-[#1877F2] text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition" title="Facebook">
                        <span class="font-bold text-xs">FB</span>
                    </a>

                    <!-- Instagram -->
                    <a href="https://instagram.com/yourig" target="_blank" class="w-12 h-12 bg-gradient-to-tr from-yellow-400 via-red-500 to-purple-500 text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition" title="Instagram">
                        <span class="font-bold text-xs">IG</span>
                    </a>

                    <!-- Threads -->
                    <a href="https://www.threads.net/@yourthreads" target="_blank" class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition" title="Threads">
                        <span class="font-bold text-xs">Th</span>
                    </a>

                    <!-- TikTok -->
                    <a href="https://www.tiktok.com/@yourtiktok" target="_blank" class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition border border-gray-700" title="TikTok">
                        <span class="font-bold text-xs">Tik</span>
                    </a>

                    <!-- 電話 -->
                    <a href="tel:0912345678" class="w-12 h-12 bg-green-600 text-white rounded-full flex items-center justify-center shadow-lg hover:scale-110 transition" title="撥打電話">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                    </a>
                </div>
            </Transition>

            <!-- 主要按鈕 (開關) -->
            <button @click="fabOpen = !fabOpen"
                    class="w-14 h-14 bg-blue-600 text-white rounded-full flex items-center justify-center shadow-xl hover:bg-blue-700 transition transform hover:scale-105 z-50">
                <!-- 聯絡圖示 (當關閉時顯示) -->
                <svg v-if="!fabOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
                </svg>
                <!-- 關閉圖示 (當開啟時顯示) -->
                <svg v-else class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

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
