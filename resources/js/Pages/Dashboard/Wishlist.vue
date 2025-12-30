<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineProps({ products: Object });
const formatPrice = (p) => new Intl.NumberFormat('zh-TW').format(p);
const getMinPrice = (variants) => variants.length ? Math.min(...variants.map(v => v.price)) : 0;
</script>

<template>
    <Head title="我的收藏" />
    <DashboardLayout>
        <h1 class="text-2xl font-bold mb-6">我的收藏清單</h1>

        <div v-if="products.data.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-6">
            <div v-for="product in products.data" :key="product.id" class="bg-white border rounded-lg overflow-hidden hover:shadow-lg transition group">
                <Link :href="`/shop/product/${product.slug}`" class="block aspect-square bg-gray-100 relative">
                    <img v-if="product.image" :src="`/storage/${product.image}`" class="w-full h-full object-cover">
                    <div v-else class="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
                </Link>
                <div class="p-4">
                    <h3 class="font-bold text-lg mb-2 truncate">
                        <Link :href="`/shop/product/${product.slug}`">{{ product.name }}</Link>
                    </h3>
                    <p class="text-red-600 font-bold">NT$ {{ formatPrice(getMinPrice(product.variants)) }} 起</p>
                </div>
            </div>
        </div>
        <div v-else class="text-center py-12 text-gray-500">
            您還沒有收藏任何商品。
            <Link href="/shop" class="text-blue-600 hover:underline block mt-2">去逛逛</Link>
        </div>

        <!-- 分頁 -->
        <!-- (可複製之前的分頁元件代碼) -->
    </DashboardLayout>
</template>

