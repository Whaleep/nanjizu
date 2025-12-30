<script setup>
import { Head, Link } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({ orders: Object });
const formatPrice = (p) => new Intl.NumberFormat('zh-TW').format(p);
const formatDate = (date) => new Date(date).toLocaleDateString('zh-TW');
</script>

<template>
    <Head title="我的訂單" />
    <DashboardLayout>
        <h1 class="text-2xl font-bold mb-6">我的訂單紀錄</h1>

        <div class="bg-white rounded-lg shadow border overflow-hidden">
            <div v-if="orders.data.length > 0">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b text-gray-500 uppercase">
                            <tr>
                                <th class="py-3 px-4">訂單編號</th>
                                <th class="py-3 px-4">下單日期</th>
                                <th class="py-3 px-4">狀態</th>
                                <th class="py-3 px-4 text-right">總金額</th>
                                <th class="py-3 px-4"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="order in orders.data" :key="order.id" class="hover:bg-gray-50 transition">
                                <td class="py-3 px-4 font-bold text-blue-600">
                                    {{ order.order_number }}
                                </td>
                                <td class="py-3 px-4">{{ formatDate(order.created_at) }}</td>
                                <td class="py-3 px-4">
                                    <span v-if="order.status === 'pending'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">待付款/處理</span>
                                    <span v-else-if="order.status === 'processing'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">處理中</span>
                                    <span v-else-if="order.status === 'shipped'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">已出貨</span>
                                    <span v-else-if="order.status === 'completed'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">已完成</span>
                                    <span v-else-if="order.status === 'cancelled'" class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">已取消</span>
                                </td>
                                <td class="py-3 px-4 text-right font-bold">NT$ {{ formatPrice(order.total_amount) }}</td>
                                <td class="py-3 px-4 text-right">
                                    <Link :href="`/dashboard/orders/${order.order_number}`" class="text-sm border border-gray-300 rounded px-3 py-1 hover:bg-gray-100 transition">
                                        詳情
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 分頁 -->
                <div v-if="orders.links.length > 3" class="p-4 border-t flex justify-center">
                    <div class="flex gap-1">
                        <template v-for="(link, index) in orders.links" :key="index">
                            <Link v-if="link.url" :href="link.url" v-html="link.label"
                                class="px-3 py-1 border rounded text-xs"
                                :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" />
                            <span v-else v-html="link.label" class="px-3 py-1 border rounded text-xs text-gray-400 bg-gray-50"></span>
                        </template>
                    </div>
                </div>
            </div>

            <div v-else class="p-10 text-center text-gray-500">
                您目前還沒有任何訂單。
                <Link href="/shop" class="text-blue-600 hover:underline block mt-2">去商店逛逛</Link>
            </div>
        </div>
    </DashboardLayout>
</template>
