<script setup>
import { ref } from 'vue'; // 引入 ref
import { Head, Link, router } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import ProductGridLayout from '@/Components/Shop/ProductGridLayout.vue';

const props = defineProps({
    products: Object,
    categories: Array,
    filters: Object,
    currentPromotion: Object,
});

// 搜尋處理
const search = ref(props.filters.q || '');
const handleSearch = () => {
    router.get('/shop', { q: search.value }, { preserveState: true });
};

</script>

<template>
    <Head title="線上商店" />

    <ShopLayout>
        <div class="container mx-auto px-4 py-8">

            <!-- 活動 Banner -->
            <div v-if="currentPromotion" class="mb-8 p-6 bg-gradient-to-r from-red-50 to-pink-50 rounded-2xl border border-red-100 relative overflow-hidden shadow-sm">
                <!-- 裝飾背景 -->
                <div class="absolute -right-10 -top-10 w-40 h-40 bg-red-100 rounded-full opacity-50 blur-3xl"></div>
                
                <div class="relative z-10 flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                    <div>
                        <div class="flex items-center gap-2 mb-2">
                            <span class="px-2 py-0.5 bg-red-600 text-white text-xs font-bold rounded shadow-sm">
                                正進行活動
                            </span>
                            <span v-if="currentPromotion.end_at" class="text-xs text-gray-500 flex items-center gap-1">
                                🕒 至 {{ new Date(currentPromotion.end_at).toLocaleDateString() }} 截止
                            </span>
                        </div>
                        <h1 class="text-2xl md:text-3xl font-bold text-gray-800 mb-2">
                            {{ currentPromotion.name }}
                        </h1>
                        <p class="text-gray-600 max-w-2xl">
                            {{ currentPromotion.description || '活動期間內，選購下方指定商品即可享有專屬優惠！' }}
                        </p>
                    </div>

                    <!-- 取消篩選按鈕 -->
                    <Link href="/shop" class="shrink-0 flex items-center gap-2 px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-lg hover:bg-gray-50 hover:text-red-600 transition shadow-sm font-medium text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        回到全商品
                    </Link>
                </div>
            </div>

            <!-- 搜尋區塊 (常駐，但在活動頁可以稍微縮小間距) -->
            <form @submit.prevent="handleSearch" class="max-w-md mx-auto mb-10" :class="currentPromotion ? 'mb-8' : 'mb-10'">
                <div class="relative">
                    <input type="text" v-model="search"
                           class="w-full border-2 border-gray-200 rounded-full pl-5 pr-12 py-3 focus:outline-none focus:border-blue-500 transition"
                           placeholder="搜尋商品...">
                    <button type="submit" class="absolute right-2 top-2 bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </div>
            </form>

            <!-- 3. 情境切換區塊 -->

            <!-- 情境 A: 純首頁 (無搜尋、無 Tag、無活動) -->
            <!-- 顯示：熱門分類 + 最新上架 -->
            <div v-if="!filters.q && !filters.tag && !currentPromotion" class="mb-12">
                <h1 class="text-3xl font-bold mb-6">熱門分類</h1>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <Link v-for="cat in categories" :key="cat.id"
                          :href="`/shop/category/${cat.slug}`"
                          class="group block text-center">
                        <div class="aspect-square bg-gray-100 rounded-2xl overflow-hidden mb-4 border hover:shadow-lg transition flex items-center justify-center">
                            <img v-if="cat.category_icon_url || cat.image" 
                                 :src="cat.category_icon_url ? cat.category_icon_url : (cat.image && cat.image.startsWith('http') ? cat.image : `/storage/${cat.image}`)" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            <span v-else class="text-4xl text-gray-300 font-bold">{{ cat.name.charAt(0) }}</span>
                        </div>
                        <h2 class="text-xl font-bold group-hover:text-blue-600">{{ cat.name }}</h2>
                    </Link>
                </div>
                
                <h2 class="text-2xl font-bold mt-12 mb-6">最新上架</h2>
            </div>

            <!-- 情境 B: 搜尋結果或標籤頁 -->
            <div v-else-if="filters.q || filters.tag" class="mb-8 text-center">
                <h1 class="text-3xl font-bold mb-4">
                    <span v-if="filters.tag">標籤：{{ filters.tag }}</span>
                    <span v-else>搜尋：{{ filters.q }}</span>
                </h1>
                <Link href="/shop" class="text-blue-600 hover:underline">清除篩選</Link>
            </div>

            <!-- 情境 C: 特惠活動頁 (已在最上面顯示 Banner，這裡只需簡單標題) -->
            <div v-else-if="currentPromotion" class="mb-6">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-2">
                    <span>🔥</span> 活動指定商品
                </h2>
            </div>

            <!-- 商品列表 -->
            <ProductGridLayout 
                :products="products" 
                :empty-message="filters.q || filters.tag ? '找不到相關商品' : '暫無商品'"
            />

        </div>
    </ShopLayout>
</template>
