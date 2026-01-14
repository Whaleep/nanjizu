<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';
import ProductGridLayout from '@/Components/Shop/ProductGridLayout.vue';

defineProps({ products: Object });
</script>

<template>
    <Head title="我的收藏" />
    <DashboardLayout>
        <h1 class="text-2xl font-bold mb-6">我的收藏清單</h1>

        <ProductGridLayout :products="products" />

        <div v-if="products.data.length === 0" class="text-center text-gray-500">
            您還沒有收藏任何商品
            <Link href="/shop" class="text-blue-600 hover:underline block mt-2">去逛逛</Link>
        </div>

    </DashboardLayout>
</template>

