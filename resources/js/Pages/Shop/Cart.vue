<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import axios from 'axios';

const props = defineProps({
    cartItems: Array,
    total: Number,
});

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

// 更新數量
const updateQuantity = async (variantId, qty) => {
    if (qty < 1) return;
    try {
        await axios.post('/v1/cart/update', { variant_id: variantId, quantity: qty });
        // 重新載入頁面資料 (Inertia 方式)
        router.reload({ only: ['cartItems', 'total', 'cartCount'] });
    } catch (error) {
        alert('更新失敗: ' + (error.response?.data?.message || '未知錯誤'));
    }
};

// 移除商品
const removeItem = async (variantId) => {
    if (!confirm('確定移除?')) return;
    try {
        await axios.post('/v1/cart/remove', { variant_id: variantId });
        router.reload();
    } catch (error) {
        alert('移除失敗');
    }
};
</script>

<template>
    <Head title="購物車" />
    <MainLayout>
        <div class="container mx-auto px-4 py-12">
            <h1 class="text-3xl font-bold mb-8 flex items-center gap-2"><span>🛒</span> 您的購物車</h1>

            <div v-if="cartItems.length > 0" class="flex flex-col lg:flex-row gap-10">
                <!-- 列表 -->
                <div class="lg:w-2/3 bg-white shadow rounded-lg overflow-hidden border">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="p-4 font-bold text-gray-600">商品</th>
                                <th class="p-4 font-bold text-gray-600 hidden sm:table-cell">單價</th>
                                <th class="p-4 font-bold text-gray-600 text-center">數量</th>
                                <th class="p-4 font-bold text-gray-600 text-right">小計</th>
                                <th class="p-4"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="item in cartItems" :key="item.variant_id" class="border-b last:border-0 hover:bg-gray-50">
                                <td class="p-4">
                                    <div class="flex items-center gap-4">
                                        <div class="w-16 h-16 bg-gray-100 rounded border flex-shrink-0">
                                            <img v-if="item.image" :src="`/storage/${item.image}`" class="w-full h-full object-cover">
                                            <div v-else class="w-full h-full flex items-center justify-center text-xs text-gray-400">無圖</div>
                                        </div>
                                        <div>
                                            <div class="font-bold">{{ item.product_name }}</div>
                                            <div class="text-sm text-gray-500">{{ item.variant_name }}</div>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 text-gray-700 hidden sm:table-cell">NT$ {{ formatPrice(item.price) }}</td>
                                <td class="p-4 text-center">
                                    <input type="number" :value="item.quantity" min="1" :max="item.stock"
                                           @change="updateQuantity(item.variant_id, $event.target.value)"
                                           class="w-16 border rounded text-center py-1">
                                </td>
                                <td class="p-4 text-right font-bold text-gray-900">NT$ {{ formatPrice(item.subtotal) }}</td>
                                <td class="p-4 text-right">
                                    <button @click="removeItem(item.variant_id)" class="text-red-500 hover:text-red-700 text-sm font-bold">移除</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- 結帳區 -->
                <div class="lg:w-1/3">
                    <div class="bg-white shadow rounded-lg p-6 border sticky top-24">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2">訂單摘要</h3>
                        <div class="flex justify-between mb-6 text-xl font-bold text-gray-900">
                            <span>總金額</span>
                            <span class="text-red-600">NT$ {{ formatPrice(total) }}</span>
                        </div>
                        <Link href="/checkout" class="block w-full bg-blue-600 text-white text-center py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                            前往結帳
                        </Link>
                        <Link href="/shop" class="block w-full text-center py-3 mt-2 text-gray-500 hover:underline">繼續購物</Link>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-20 bg-gray-50 rounded-lg border border-dashed">
                <p class="text-xl text-gray-500 mb-6">購物車是空的</p>
                <Link href="/shop" class="bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700">去商店逛逛</Link>
            </div>
        </div>
    </MainLayout>
</template>
