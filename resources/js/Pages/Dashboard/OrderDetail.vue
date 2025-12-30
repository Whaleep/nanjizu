<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({ order: Object });
const formatPrice = (p) => new Intl.NumberFormat('zh-TW').format(p);
</script>

<template>
    <Head :title="`訂單 ${order.order_number}`" />
    <DashboardLayout>
        <div class="mb-6 flex items-center justify-between">
            <h1 class="text-2xl font-bold">訂單詳情</h1>
            <Link href="/dashboard/orders" class="text-gray-500 hover:text-gray-700">&larr; 返回列表</Link>
        </div>

        <div class="bg-white rounded-lg shadow border overflow-hidden mb-6">
            <div class="p-4 bg-gray-50 border-b flex justify-between items-center">
                <span class="font-bold text-gray-700">單號：{{ order.order_number }}</span>
                <span class="text-sm text-gray-500">{{ new Date(order.created_at).toLocaleString() }}</span>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <h4 class="font-bold mb-2 text-gray-900">收件資訊</h4>
                        <p class="text-sm text-gray-600">姓名：{{ order.customer_name }}</p>
                        <p class="text-sm text-gray-600">電話：{{ order.customer_phone }}</p>
                        <p class="text-sm text-gray-600">地址：{{ order.customer_address }}</p>
                    </div>
                    <div>
                        <h4 class="font-bold mb-2 text-gray-900">付款狀態</h4>
                        <p class="text-sm text-gray-600">方式：{{ order.payment_method }}</p>
                        <p class="text-sm text-gray-600">狀態：<span class="font-bold">{{ order.status }}</span></p>
                    </div>
                </div>

                <table class="w-full text-left text-sm mb-4">
                    <thead class="bg-gray-50 border-b">
                        <tr>
                            <th class="py-2 px-2">商品</th>
                            <th class="py-2 px-2 text-right">單價</th>
                            <th class="py-2 px-2 text-center">數量</th>
                            <th class="py-2 px-2 text-right">小計</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in order.items" :key="item.id" class="border-b last:border-0">
                            <td class="py-3 px-2">
                                <div class="font-bold">{{ item.product_name }}</div>
                                <div class="text-xs text-gray-500">{{ item.variant_name }}</div>
                            </td>
                            <td class="py-3 px-2 text-right">NT$ {{ formatPrice(item.price) }}</td>
                            <td class="py-3 px-2 text-center">{{ item.quantity }}</td>
                            <td class="py-3 px-2 text-right">NT$ {{ formatPrice(item.subtotal) }}</td>
                        </tr>
                    </tbody>
                </table>

                <div class="text-right text-xl font-bold border-t pt-4">
                    總計：<span class="text-red-600">NT$ {{ formatPrice(order.total_amount) }}</span>
                </div>
            </div>
        </div>
    </DashboardLayout>
</template>
