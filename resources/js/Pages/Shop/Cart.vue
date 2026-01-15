<script setup>
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import axios from 'axios';
import ProductListCard from '@/Components/Shop/ProductListCard.vue';

const props = defineProps({
    cartItems: Array,
    subtotal: Number,   // 折扣前
    discount: Number,
    total: Number,      // 折扣後
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
        // Inertia 會重新抓取資料，Vue 會自動更新畫面上的小計與總金額
        router.reload({ only: ['cartItems', 'subtotal', 'discount', 'total', 'cartCount'] });
        // 更新 Navbar 紅點
        window.dispatchEvent(new CustomEvent('cart-updated', { detail: { count: response.data.cartCount } }));
        // 顯示提示
        showToast('已更新數量');

    } catch (error) {
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
        const response = await axios.post('/cart/remove', { variant_id: variantId });
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
                <!-- 購物車列表 (左側) -->
                <div class="lg:w-2/3 space-y-4">
                    <template v-for="item in cartItems" :key="item.variant_id">
                        
                        <ProductListCard
                            :image="item.image"
                            :title="item.product_name"
                            :subtitle="item.variant_name"
                            :price="item.price"
                            :link="`/shop/product/${item.product_slug}`"
                        >
                            <!-- 插槽：放入購物車專用的數量與移除按鈕 -->
                            <template #actions>
                                <div class="flex items-center justify-between sm:justify-end gap-4 w-full">
                                    
                                    <!-- 數量調整器 -->
                                    <div class="flex items-center border border-gray-300 rounded-lg bg-white h-8 md:h-10">
                                        <button @click="updateQuantity(item.variant_id, item.quantity - 1)" 
                                                class="px-2 md:px-3 text-gray-500 hover:bg-gray-100 h-full rounded-l-lg transition">-</button>
                                        
                                        <input type="number" 
                                            :value="item.quantity" 
                                            @change="updateQuantity(item.variant_id, $event.target.value)"
                                            class="w-10 md:w-12 text-center text-sm border-none focus:ring-0 p-0 h-full appearance-none">
                                        
                                        <button @click="updateQuantity(item.variant_id, item.quantity + 1)" 
                                                :disabled="item.quantity >= item.stock"
                                                class="px-2 md:px-3 text-gray-500 hover:bg-gray-100 h-full rounded-r-lg transition disabled:opacity-50">+</button>
                                    </div>

                                    <!-- 小計與移除 -->
                                    <div class="flex flex-col items-end gap-1">
                                        <span class="text-xs md:text-sm font-bold text-gray-900">
                                            小計: NT$ <span :id="`subtotal-${item.variant_id}`">{{ formatPrice(item.subtotal) }}</span>
                                        </span>
                                        
                                        <button @click="removeItem(item.variant_id)" 
                                                class="text-xs md:text-sm text-gray-400 hover:text-red-500 underline decoration-dotted transition">
                                            移除
                                        </button>
                                    </div>
                                </div>
                            </template>
                        </ProductListCard>

                    </template>
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

<style scoped>
/* 移除 Chrome, Safari, Edge, Opera 的 type="number" 預設箭頭 */
input::-webkit-outer-spin-button,
input::-webkit-inner-spin-button {
  -webkit-appearance: none;
  margin: 0;
}

/* 移除 Firefox 的 type="number" 預設箭頭 */
input[type=number] {
  -moz-appearance: textfield;
}
</style>
