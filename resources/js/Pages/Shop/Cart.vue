<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import axios from 'axios';

const props = defineProps({
    cartItems: Array,
    subtotal: Number, // 改名：原本的 total 變成 subtotal
    discount: Number,
    total: Number,
    appliedCoupon: String,
});

// 本地狀態
const couponCode = ref('');
const couponMessage = ref('');
const isCouponLoading = ref(false);
const toast = ref({
    visible: false,
    message: ''
});

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

const showToast = (message) => {
    toast.value.message = message;
    toast.value.visible = true;
    setTimeout(() => {
        toast.value.visible = false;
    }, 5000); // 5秒後消失
};

// 更新數量
const updateQuantity = async (variantId, newQty) => {
    if (newQty < 1) return;
    try {
        const response = await axios.post('/cart/update', {
            variant_id: variantId,
            quantity: newQty
        });
        // await axios.post('/cart/update', { variant_id: variantId, quantity: qty });
        // 重新載入頁面資料 (Inertia 方式)
        // router.reload({ only: ['cartItems', 'subtotal', 'total', 'cartCount'] });
        // document.getElementById('subtotal-' + variantId).innerText = response.data.itemSubtotal;
        // Inertia 會重新抓取資料，Vue 會自動更新畫面上的小計與總金額
        router.reload({ only: ['cartItems', 'subtotal', 'discount', 'total', 'cartCount'] });
        // 更新 Navbar 紅點
        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: response.data.cartCount } }));
        // 顯示提示
        showToast('已更新數量');

    } catch (error) {
        // alert('更新失敗: ' + (error.response?.data?.message || '未知錯誤'));
        // 失敗邏輯
        const msg = error.response?.data?.message || '更新失敗';
        // 1. 顯示錯誤提示 (使用您之前寫的 showToast 或 alert)
        // 建議用 alert 比較強烈，或者用紅色的 Toast
        alert(msg);

        // 2. 因為這是 v-for 迴圈生成的 input，直接操作 DOM 還原數值比較麻煩
        // 最簡單暴力的方法：重新整理頁面，讓數據回到正確狀態
        // 或者使用 router.reload()
        router.reload();
    }
};

// 移除商品
const removeItem = async (variantId) => {
    if (!confirm('確定要移除此商品嗎?')) return;
    try {
        await axios.post('/cart/remove', { variant_id: variantId });
        router.reload();
        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: response.data.cartCount } }));
        showToast('商品已移除');
    } catch (error) {
        alert('移除失敗');
    }
};

// 套用優惠券
const applyCoupon = async () => {
    if (!couponCode.value) return;
    isCouponLoading.value = true;
    couponMessage.value = '';

    try {
        await axios.post('/cart/coupon', { code: couponCode.value });
        router.reload({ only: ['subtotal', 'discount', 'total', 'appliedCoupon'] });
        couponMessage.value = '優惠券套用成功！';
        couponCode.value = ''; // 清空輸入框
    } catch (error) {
        couponMessage.value = error.response?.data?.message || '無效的優惠碼';
    } finally {
        isCouponLoading.value = false;
    }
};

// 移除優惠券
const removeCoupon = async () => {
    try {
        await axios.delete('/cart/coupon');
        router.reload({ only: ['subtotal', 'discount', 'total', 'appliedCoupon'] });
    } catch (error) {
        console.error(error);
    }
};

</script>

<template>
    <Head title="購物車" />
    <MainLayout>

        <!-- Toast 通知元件 -->
        <transition
            enter-active-class="transition ease-out duration-300"
            enter-from-class="transform opacity-0 translate-y-2"
            enter-to-class="transform opacity-100 translate-y-0"
            leave-active-class="transition ease-in duration-200"
            leave-from-class="transform opacity-100 translate-y-0"
            leave-to-class="transform opacity-0 translate-y-2"
        >
            <div v-if="toast.visible" class="fixed top-20 right-4 z-50 bg-gray-800 text-white px-6 py-3 rounded-lg shadow-xl flex items-center gap-3">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <span class="font-medium">{{ toast.message }}</span>
            </div>
        </transition>

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

                        <div class="flex justify-between mb-2 text-gray-600">
                            <span>商品小計</span>
                            <span>NT$ {{ formatPrice(subtotal) }}</span>
                        </div>

                        <!-- 折扣顯示 -->
                        <div v-if="discount > 0" class="flex justify-between mb-2 text-green-600 font-bold">
                            <span>折扣 ({{ appliedCoupon }})</span>
                            <span>- NT$ {{ formatPrice(discount) }}</span>
                        </div>

                        <!-- 優惠券輸入區 -->
                        <div class="my-4 pt-4 border-t">
                            <div v-if="!appliedCoupon">
                                <div class="flex gap-2">
                                    <input type="text" v-model="couponCode" placeholder="輸入優惠碼"
                                        class="w-full border rounded px-3 py-2 text-sm uppercase">
                                    <button @click="applyCoupon" :disabled="isCouponLoading"
                                            class="bg-gray-800 text-white px-3 py-2 rounded text-sm hover:bg-gray-700 disabled:opacity-50">
                                        套用
                                    </button>
                                </div>
                                <p v-if="couponMessage" class="text-xs mt-1" :class="couponMessage.includes('成功') ? 'text-green-600' : 'text-red-500'">
                                    {{ couponMessage }}
                                </p>
                            </div>
                            <div v-else class="flex justify-between items-center bg-green-50 p-2 rounded border border-green-200">
                                <span class="text-sm text-green-800">已套用：<b>{{ appliedCoupon }}</b></span>
                                <button @click="removeCoupon" class="text-red-500 text-xs hover:underline">移除</button>
                            </div>
                        </div>

                        <div class="flex justify-between mb-6 text-xl font-bold text-gray-900 border-t pt-4">
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
