<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    order: Object,
});

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);
</script>

<template>
    <Head title="下單成功" />
    <MainLayout>
        <div class="container mx-auto px-4 py-20 text-center">
            <div class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-6 text-4xl">✓</div>
            <h1 class="text-3xl font-bold mb-4">訂單已成立！</h1>
            <div class="bg-white max-w-md mx-auto p-6 rounded-lg shadow border mb-8 text-left">
                <p class="mb-2"><strong>訂單編號：</strong> {{ order.order_number }}</p>
                <p class="mb-2"><strong>總金額：</strong> NT$ {{ formatPrice(order.total_amount) }}</p>
                <p class="mb-2">
                    <strong>狀態：</strong>
                    <span v-if="order.status === 'pending'" class="text-yellow-600 font-bold">待付款/處理中</span>
                    <span v-else-if="order.status === 'processing'" class="text-green-600 font-bold">已付款/處理中</span>
                    <span v-else>{{ order.status }}</span>
                </p>
            </div>
            <Link href="/shop" class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700">繼續購物</Link>
        </div>

        <!-- 如果選擇銀行轉帳，顯示轉帳資訊 -->
        <div v-if="order.payment_method === 'bank_transfer'" class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg text-yellow-900 text-sm">
            <h4 class="font-bold mb-2 text-lg">🏦 匯款資訊</h4>
            <p>銀行代碼：<strong>822 (中國信託)</strong></p>
            <p>銀行帳號：<strong>1234-5678-9012</strong></p>
            <p>戶名：<strong>ABC手機維修</strong></p>
            <hr class="border-yellow-200 my-2">
            <p class="text-xs">請於匯款後，透過 Line 告知您的「訂單編號」與「帳號末五碼」，以利我們快速對帳出貨。</p>
        </div>
    </MainLayout>
</template>
