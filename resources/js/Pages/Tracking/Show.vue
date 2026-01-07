<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({ order: Object });
const formatPrice = (p) => new Intl.NumberFormat('zh-TW').format(p);
</script>

<template>
    <Head :title="`訂單 ${order.order_number}`" />
    <MainLayout>
        <div class="container mx-auto px-4 py-12">
            <div class="max-w-3xl mx-auto">
                <div class="mb-6 flex items-center justify-between">
                    <h1 class="text-2xl font-bold">查詢結果</h1>
                    <Link href="/tracking" class="text-gray-500 hover:text-gray-700">&larr; 重新查詢</Link>
                </div>

                <div class="bg-white rounded-lg shadow border overflow-hidden mb-6">
                    <!-- 狀態 header -->
                    <div class="p-6 bg-gray-50 border-b flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <span class="block text-xs text-gray-500 uppercase tracking-wider">訂單編號</span>
                            <span class="font-bold text-xl text-gray-800">{{ order.order_number }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-sm text-gray-600">狀態：</span>
                            <span v-if="order.status === 'pending'" class="px-3 py-1 rounded-full text-sm font-bold bg-yellow-100 text-yellow-800">待付款/處理</span>
                            <span v-else-if="order.status === 'processing'" class="px-3 py-1 rounded-full text-sm font-bold bg-blue-100 text-blue-800">處理中</span>
                            <span v-else-if="order.status === 'shipped'" class="px-3 py-1 rounded-full text-sm font-bold bg-indigo-100 text-indigo-800">已出貨</span>
                            <span v-else-if="order.status === 'completed'" class="px-3 py-1 rounded-full text-sm font-bold bg-green-100 text-green-800">已完成</span>
                            <span v-else>{{ order.status }}</span>
                        </div>
                    </div>

                    <!-- 內容 -->
                    <div class="p-6">
                        <!-- 收件資訊 -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8 pb-8 border-b">
                            <div>
                                <h4 class="font-bold mb-3 text-gray-900">收件資訊</h4>
                                <p class="text-sm text-gray-600 mb-1">姓名：{{ order.customer_name }}</p>
                                <p class="text-sm text-gray-600 mb-1">電話：{{ order.customer_phone }}</p>
                                <p class="text-sm text-gray-600">地址：{{ order.customer_address }}</p>
                            </div>
                            <div>
                                <h4 class="font-bold mb-3 text-gray-900">付款資訊</h4>
                                <p class="text-sm text-gray-600 mb-1">方式：{{ order.payment_method }}</p>
                                <p class="text-sm text-gray-600">下單時間：{{ new Date(order.created_at).toLocaleString() }}</p>

                                <!-- 如果是匯款，顯示帳號 -->
                                <div v-if="order.payment_method === 'bank_transfer'" class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded text-xs text-yellow-800">
                                    匯款銀行：822 (中國信託)<br>
                                    帳號：1234-5678-9012
                                </div>
                            </div>
                        </div>

                        <!-- 商品列表 -->
                        <h4 class="font-bold mb-4 text-gray-900">購買商品</h4>
                        <div class="space-y-4">
                            <div v-for="item in order.items" :key="item.id" class="flex justify-between items-center border-b pb-4 last:border-0 last:pb-0">
                                <div>
                                    <div class="font-bold text-gray-800">{{ item.product_name }}</div>
                                    <div class="text-sm text-gray-500">{{ item.variant_name }} x {{ item.quantity }}</div>
                                </div>
                                <div class="font-bold text-gray-900">NT$ {{ formatPrice(item.subtotal) }}</div>
                            </div>
                        </div>

                        <!-- 總計 -->
                        <div class="mt-6 pt-6 border-t flex justify-between items-center">
                            <span class="text-gray-600 font-bold">訂單總金額</span>
                            <span class="text-2xl font-bold text-red-600">NT$ {{ formatPrice(order.total_amount) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
