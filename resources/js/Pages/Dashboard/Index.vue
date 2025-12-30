<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

defineProps({ recentOrders: Array });
const formatPrice = (p) => new Intl.NumberFormat('zh-TW').format(p);
</script>

<template>
    <Head title="會員中心" />
    <DashboardLayout>
        <h1 class="text-2xl font-bold mb-6">歡迎回來！</h1>

        <div class="bg-white rounded-lg shadow border p-6 mb-8">
            <h2 class="text-lg font-bold mb-4">最近訂單</h2>

            <div v-if="recentOrders.length > 0" class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b text-gray-500">
                            <th class="py-3">訂單編號</th>
                            <th class="py-3">日期</th>
                            <th class="py-3">狀態</th>
                            <th class="py-3 text-right">金額</th>
                            <th class="py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="order in recentOrders" :key="order.id" class="border-b last:border-0">
                            <td class="py-3 font-bold">{{ order.order_number }}</td>
                            <td class="py-3">{{ new Date(order.created_at).toLocaleDateString() }}</td>
                            <td class="py-3">
                                <span v-if="order.status === 'pending'" class="text-yellow-600 bg-yellow-100 px-2 py-1 rounded text-xs">待處理</span>
                                <span v-else-if="order.status === 'processing'" class="text-blue-600 bg-blue-100 px-2 py-1 rounded text-xs">處理中</span>
                                <span v-else-if="order.status === 'completed'" class="text-green-600 bg-green-100 px-2 py-1 rounded text-xs">已完成</span>
                                <span v-else>{{ order.status }}</span>
                            </td>
                            <td class="py-3 text-right">NT$ {{ formatPrice(order.total_amount) }}</td>
                            <td class="py-3 text-right">
                                <Link :href="`/dashboard/orders/${order.order_number}`" class="text-blue-600 hover:underline">查看</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-4 text-center">
                    <Link href="/dashboard/orders" class="text-blue-600 hover:underline text-sm">查看所有訂單 &rarr;</Link>
                </div>
            </div>
            <div v-else class="text-center py-8 text-gray-500">目前沒有訂單紀錄。</div>
        </div>
    </DashboardLayout>
</template>
