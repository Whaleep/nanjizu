<script setup>
import { ref } from 'vue'; // 引入 ref
import { Head, Link, router } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';

const props = defineProps({
    products: Object,
    categories: Array, // 這是後端傳來的最上層分類
    filters: Object
});

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);
const getMinPrice = (variants) => variants.length ? Math.min(...variants.map(v => v.price)) : 0;

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

            <!-- 1. 搜尋框 (新增) -->
            <form @submit.prevent="handleSearch" class="max-w-md mx-auto mb-10">
                <div class="relative">
                    <input type="text" v-model="search"
                           class="w-full border-2 border-gray-200 rounded-full pl-5 pr-12 py-3 focus:outline-none focus:border-blue-500 transition"
                           placeholder="搜尋商品...">
                    <button type="submit" class="absolute right-2 top-2 bg-blue-600 text-white p-2 rounded-full hover:bg-blue-700 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </button>
                </div>
            </form>

            <!-- 2. 熱門分類 Grid (新增 - 只有在沒搜尋沒篩選時顯示) -->
            <div v-if="!filters.q && !filters.tag" class="mb-12">
                <h1 class="text-3xl font-bold mb-6">熱門分類</h1>
                <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                    <Link v-for="cat in categories" :key="cat.id"
                          :href="`/shop/category/${cat.slug}`"
                          class="group block text-center">
                        <div class="aspect-square bg-gray-100 rounded-2xl overflow-hidden mb-4 border hover:shadow-lg transition flex items-center justify-center">
                            <img v-if="cat.image" :src="`/storage/${cat.image}`" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                            <span v-else class="text-4xl text-gray-300 font-bold">{{ cat.name.charAt(0) }}</span>
                        </div>
                        <h2 class="text-xl font-bold group-hover:text-blue-600">{{ cat.name }}</h2>
                    </Link>
                </div>
                <h2 class="text-2xl font-bold mt-12 mb-6">最新上架</h2>
            </div>

            <!-- 3. 搜尋/標籤 標題 -->
            <div v-else class="mb-8 text-center">
                <h1 class="text-3xl font-bold mb-4">
                    <span v-if="filters.tag">標籤：{{ filters.tag }}</span>
                    <span v-else>搜尋：{{ filters.q }}</span>
                </h1>
                <Link href="/shop" class="text-blue-600 hover:underline">清除篩選</Link>
            </div>

            <!-- 4. 商品列表 (保持不變) -->
            <div v-if="products.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <div v-for="product in products.data" :key="product.id" class="bg-white border rounded-xl overflow-hidden hover:shadow-lg transition group">
                    <Link :href="`/shop/product/${product.slug}`" class="block aspect-square bg-gray-100 overflow-hidden relative">
                        <img v-if="product.image" :src="`/storage/${product.image}`" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <div v-else class="flex items-center justify-center w-full h-full text-gray-400">無圖</div>
                    </Link>
                    <div class="p-4">
                        <h3 class="font-bold text-lg mb-2 group-hover:text-blue-600 line-clamp-2">
                            <Link :href="`/shop/product/${product.slug}`">{{ product.name }}</Link>
                        </h3>
                        <p class="text-red-600 font-bold">NT$ {{ formatPrice(getMinPrice(product.variants)) }} 起</p>
                    </div>
                </div>
            </div>

            <div v-else class="py-20 text-center bg-white rounded-lg border border-dashed">
                <p class="text-gray-500 text-xl">沒有找到相關商品。</p>
            </div>

            <!-- 分頁 -->
            <div v-if="products.links.length > 3" class="mt-10 flex justify-center gap-1">
                <template v-for="(link, index) in products.links" :key="index">
                    <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-4 py-2 border rounded-md text-sm" :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" />
                    <span v-else v-html="link.label" class="px-4 py-2 border rounded-md text-sm text-gray-400 bg-gray-50"></span>
                </template>
            </div>
        </div>
    </ShopLayout>
</template>
