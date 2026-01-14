<script setup>
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import ProductGridLayout from '@/Components/Shop/ProductGridLayout.vue';

const props = defineProps({
    category: Object,
    subcategories: Array,
    products: Object,
    tags: Array,
    currentTag: String,
});

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);
const getMinPrice = (variants) => variants.length ? Math.min(...variants.map(v => v.price)) : 0;
</script>

<template>
    <Head :title="category.name" />

    <ShopLayout>
        <!-- 麵包屑 -->
        <div class="text-sm text-gray-500 mb-6 flex items-center gap-2">
            <Link href="/shop" class="hover:text-blue-600">商店</Link>
            <span>/</span>
            <span class="font-bold text-gray-900">{{ category.name }}</span>
        </div>

        <!-- 搜尋框 -->
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

        <h1 class="text-3xl font-bold mb-8">{{ category.name }}</h1>

        <!-- 子分類 -->
        <div v-if="subcategories.length > 0" class="mb-12">
            <h3 class="text-xl font-bold mb-4 border-l-4 border-blue-600 pl-3">子分類</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <Link v-for="sub in subcategories" :key="sub.id"
                      :href="`/shop/category/${sub.slug}`"
                      class="block bg-white border p-4 rounded-lg text-center hover:shadow-md hover:border-blue-500 transition">
                    {{ sub.name }}
                </Link>
            </div>
        </div>

        <!-- 標籤篩選 -->
        <div v-if="tags.length > 0" class="mb-8 flex flex-wrap gap-2">
            <Link :href="`/shop/category/${category.slug}`"
                  class="px-4 py-1 rounded-full border transition"
                  :class="!currentTag ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 hover:border-blue-500'">
                全部
            </Link>
            <Link v-for="tag in tags" :key="tag.id"
                  :href="`/shop/category/${category.slug}?tag=${tag.slug}`"
                  class="px-4 py-1 rounded-full border transition"
                  :class="currentTag === tag.slug ? 'bg-blue-600 text-white border-blue-600' : 'bg-white text-gray-600 hover:border-blue-500'">
                {{ tag.name }}
            </Link>
        </div>

        <!-- 商品列表 -->
        <ProductGridLayout :products="products" empty-message="此分類暫無商品" />

    </ShopLayout>
</template>
