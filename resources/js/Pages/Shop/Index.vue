<script setup>
import { ref } from 'vue'; // 引入 ref
import { Head, Link, router } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import ProductGridLayout from '@/Components/Shop/ProductGridLayout.vue';

const props = defineProps({
    products: Object,
    categories: Array, // 這是後端傳來的最上層分類
    filters: Object
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

            <!-- 搜尋區塊 -->
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

            <!-- 熱門分類 Grid -->
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

            <!-- 搜尋/標籤 標題 -->
            <div v-else class="mb-8 text-center">
                <h1 class="text-3xl font-bold mb-4">
                    <span v-if="filters.tag">標籤：{{ filters.tag }}</span>
                    <span v-else>搜尋：{{ filters.q }}</span>
                </h1>
                <Link href="/shop" class="text-blue-600 hover:underline">清除篩選</Link>
            </div>

            <!-- 商品列表 -->
            <ProductGridLayout 
                :products="products" 
                :empty-message="filters.q || filters.tag ? '找不到相關商品' : '暫無商品'"
            />

        </div>
    </ShopLayout>
</template>
