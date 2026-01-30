<script setup>
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';
import { ref, computed } from 'vue';

const props = defineProps({
    cartItems: Array,
    summary: {
        type: Object,
        default: () => ({
            subtotal: 0,
            promo_discount: 0,
            coupon_discount: 0,
            total: 0 // 這是已扣折扣、未加運費的金額
        })
    },
    shippingMethods: Array,
    savedAddress: Object, // 保存的地址
});
const page = usePage();
const user = page.props.auth.user; // 取得登入者

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

const form = useForm({
    customer_name: props.savedAddress?.name || (user ? user.name : ''),
    customer_phone: props.savedAddress?.phone || (user ? user.phone : ''),
    customer_email: props.savedAddress?.email || (user ? user.email : ''),
    customer_address: props.savedAddress?.address || (user ? user.address : ''),
    notes: '',
    payment_method: 'bank_transfer', 
    shipping_method_id: null,
});

// 計算當前運費
const currentShippingFee = computed(() => {
    if (!form.shipping_method_id) return 0;
    
    const method = props.shippingMethods.find(m => m.id === form.shipping_method_id);
    if (!method) return 0;

    // 判斷免運 (注意：這裡通常是用折扣後的 total 還是原始 subtotal 判斷？依貴司規定，通常是用折扣後 total)
    // 假設是用折扣後金額 (props.summary.total) 來判斷免運
    if (method.free_shipping_threshold && props.summary.total >= method.free_shipping_threshold) {
        return 0;
    }
    return method.fee;
});

// 計算最終總金額
const finalTotal = computed(() => {
    return Math.max(0, props.summary.total + currentShippingFee.value);
});

const submit = () => {
    // 這裡我們直接 POST 到 V1 的 checkout.store 路由
    // 但因為 V1 會回傳 redirect 或 HTML (綠界)，Inertia 會自動處理 redirect
    // 如果是綠界 (回傳 HTML)，Inertia 可能會顯示成 modal 或 raw html，這部分稍微 tricky
    // 最簡單解法：使用傳統 form submit 針對 checkout

    // 為了相容綠界的 HTML 回傳跳轉，我們這裡「不使用」Inertia 的 form.post
    // 而是建立一個真實的 form 並 submit，這樣瀏覽器才能處理綠界的整頁跳轉。
    document.getElementById('real-checkout-form').submit();
};
</script>

<template>
    <Head title="結帳" />
    <MainLayout>
        <div class="container mx-auto px-4 py-12">

            <!-- 新增：訪客警語 -->
            <div v-if="!$page.props.auth.user" class="max-w-4xl mx-auto mb-8 bg-orange-50 border-l-4 border-orange-500 p-4 rounded-r shadow-sm">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-orange-400" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-orange-700">
                            <span class="font-bold">注意：</span>
                            您目前是訪客身分。下單後將<strong class="underline">無法</strong>登入系統查詢訂單狀態或歷史紀錄。
                            <br class="hidden sm:block">
                            建議您先
                            <Link href="/login" class="font-bold underline hover:text-orange-900">登入</Link> 或
                            <Link href="/register" class="font-bold underline hover:text-orange-900">註冊會員</Link>。
                        </p>
                    </div>
                </div>
            </div>

            <h1 class="text-3xl font-bold mb-8 text-center">填寫結帳資料</h1>

            <div class="max-w-5xl mx-auto grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- 左側：表單 (佔 2/3) -->
                <div class="lg:col-span-2">
                    <form action="/checkout" method="POST" id="real-checkout-form">
                        <input type="hidden" name="_token" :value="$page.props.csrf_token">

                        <!-- 運送方式 -->
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">🚚 運送方式</h3>
                            <div class="space-y-3">
                                <label v-for="method in shippingMethods" :key="method.id" 
                                    class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition relative overflow-hidden"
                                    :class="form.shipping_method_id === method.id ? 'border-blue-500 ring-1 ring-blue-500 bg-blue-50/50' : 'border-gray-200'">
                                    
                                    <div class="flex items-center gap-3">
                                        <input type="radio" name="shipping_method_id" :value="method.id" v-model="form.shipping_method_id" required class="text-blue-600 focus:ring-blue-500">
                                        <div>
                                            <div class="font-bold text-gray-800">{{ method.name }}</div>
                                            <div v-if="method.free_shipping_threshold" class="text-xs text-gray-500 mt-0.5">
                                                滿 ${{ formatPrice(method.free_shipping_threshold) }} 免運
                                            </div>
                                        </div>
                                    </div>

                                    <!-- 顯示運費邏輯 -->
                                    <div class="font-bold text-gray-700">
                                        <span v-if="method.free_shipping_threshold && summary.total >= method.free_shipping_threshold" class="text-green-600 flex flex-col items-end">
                                            <span>免運費</span>
                                            <span class="line-through text-gray-400 text-xs font-normal">${{ method.fee }}</span>
                                        </span>
                                        <span v-else>
                                            + ${{ method.fee }}
                                        </span>
                                    </div>
                                </label>
                            </div>
                            <div v-if="!form.shipping_method_id" class="text-red-500 text-sm mt-2">請選擇一種運送方式</div>
                        </div>

                        <!-- 收件資訊 -->
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-6">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">
                                <span>👤</span> 收件人資訊
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <div class="col-span-1">
                                    <label class="block text-sm font-bold mb-1.5 text-gray-700">姓名 *</label>
                                    <input type="text" 
                                           name="customer_name" 
                                           required 
                                           v-model="form.customer_name" 
                                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-gray-700 leading-tight focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                                </div>
                                <div class="col-span-1">
                                    <label class="block text-sm font-bold mb-1.5 text-gray-700">電話 *</label>
                                    <input type="text" 
                                           name="customer_phone" 
                                           required 
                                           v-model="form.customer_phone" 
                                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-gray-700 leading-tight focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-bold mb-1.5 text-gray-700">Email (接收訂單通知)</label>
                                    <input type="email" 
                                           name="customer_email" 
                                           v-model="form.customer_email" 
                                           class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-gray-700 leading-tight focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors">
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-bold mb-1.5 text-gray-700">地址 *</label>
                                    <textarea name="customer_address" 
                                              required 
                                              v-model="form.customer_address" 
                                              rows="3" 
                                              class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-gray-700 leading-tight focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors"></textarea>
                                </div>
                                <div class="col-span-1 md:col-span-2">
                                    <label class="block text-sm font-bold mb-1.5 text-gray-700">備註</label>
                                    <textarea name="notes" 
                                              v-model="form.notes" 
                                              rows="2" 
                                              class="w-full border border-gray-300 rounded-lg px-4 py-2.5 text-gray-700 leading-tight focus:outline-none focus:border-blue-500 focus:ring-1 focus:ring-blue-500 transition-colors" 
                                              placeholder="有什麼想告訴賣家的嗎？"></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- 付款方式 -->
                        <div class="bg-white p-6 rounded-lg shadow-sm border border-gray-200 mb-8">
                            <h3 class="text-lg font-bold mb-4 flex items-center gap-2">💳 付款方式</h3>
                            <div class="grid grid-cols-1 gap-3">
                                <label class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                    :class="form.payment_method === 'bank_transfer' ? 'border-blue-500 ring-1 ring-blue-500 bg-blue-50/50' : 'border-gray-200'">
                                    <input type="radio" name="payment_method" value="bank_transfer" v-model="form.payment_method" class="text-blue-600 focus:ring-blue-500">
                                    <span class="font-medium text-gray-800">銀行轉帳 (人工對帳)</span>
                                </label>

                                <label class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                    :class="form.payment_method === 'cod' ? 'border-blue-500 ring-1 ring-blue-500 bg-blue-50/50' : 'border-gray-200'">
                                    <input type="radio" name="payment_method" value="cod" v-model="form.payment_method" class="text-blue-600 focus:ring-blue-500">
                                    <span class="font-medium text-gray-800">貨到付款</span>
                                </label>

                                <label class="flex items-center gap-3 p-4 border rounded-lg cursor-pointer hover:bg-gray-50 transition"
                                    :class="form.payment_method === 'ecpay' ? 'border-blue-500 ring-1 ring-blue-500 bg-blue-50/50' : 'border-gray-200'">
                                    <input type="radio" name="payment_method" value="ecpay" v-model="form.payment_method" class="text-blue-600 focus:ring-blue-500">
                                    <span class="font-medium text-gray-800">綠界支付 (信用卡/超商代碼)</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" :disabled="!form.shipping_method_id" 
                            class="w-full bg-blue-600 text-white font-bold py-4 rounded-xl hover:bg-blue-700 transition shadow-lg text-lg disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2">
                            <span>提交訂單</span>
                            <span class="bg-blue-800 px-2 py-0.5 rounded text-sm">NT$ {{ formatPrice(finalTotal) }}</span>
                        </button>
                    </form>
                </div>

                <!-- 右側：訂單摘要 (佔 1/3) -->
                <div class="lg:col-span-1">
                    <div class="bg-gray-50 p-6 rounded-lg border border-gray-200 sticky top-24">
                        <h3 class="text-lg font-bold mb-4 border-b pb-2 border-gray-200">購買清單</h3>
                        
                        <!-- 商品列表 (含贈品) -->
                        <ul class="space-y-3 mb-6 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                            <li v-for="item in cartItems" :key="item.cart_item_key" class="flex justify-between items-start text-sm">
                                <div class="flex items-start gap-2">
                                    <!-- 簡易小圖 (選用) -->
                                    <div class="w-10 h-10 bg-white rounded border overflow-hidden shrink-0 hidden sm:block">
                                        <img :src="item.image" class="w-full h-full object-cover">
                                    </div>
                                    <div>
                                        <div class="font-medium text-gray-900 leading-tight">
                                            {{ item.product_name }}
                                            <span v-if="item.is_gift" class="ml-1 text-[10px] bg-green-100 text-green-700 px-1.5 py-0.5 rounded border border-green-200 align-top">贈品</span>
                                        </div>
                                        <div class="text-xs text-gray-500 mt-0.5">{{ item.variant_name }} x {{ item.quantity }}</div>
                                    </div>
                                </div>
                                <div class="font-bold text-gray-700 whitespace-nowrap">
                                    <span v-if="item.is_gift" class="text-green-600">免費</span>
                                    <span v-else>NT$ {{ formatPrice(item.subtotal) }}</span>
                                </div>
                            </li>
                        </ul>

                        <!-- 金額計算 -->
                        <div class="space-y-2 border-t border-gray-200 pt-4 text-sm text-gray-600">
                            <div class="flex justify-between">
                                <span>商品小計</span>
                                <span>NT$ {{ formatPrice(summary.subtotal) }}</span>
                            </div>

                            <div v-if="summary.promo_discount > 0" class="flex justify-between text-green-600">
                                <span>滿額折扣</span>
                                <span>- NT$ {{ formatPrice(summary.promo_discount) }}</span>
                            </div>

                            <div v-if="summary.coupon_discount > 0" class="flex justify-between text-blue-600">
                                <span>優惠券折扣</span>
                                <span>- NT$ {{ formatPrice(summary.coupon_discount) }}</span>
                            </div>

                            <div class="flex justify-between items-center">
                                <span>運費</span>
                                <span v-if="form.shipping_method_id" class="font-medium">
                                    NT$ {{ formatPrice(currentShippingFee) }}
                                </span>
                                <span v-else class="text-xs text-orange-500">(尚未選擇)</span>
                            </div>
                        </div>

                        <!-- 最終總額 -->
                        <div class="flex justify-between mt-4 pt-4 border-t-2 border-dashed border-gray-300 text-xl font-bold text-gray-900">
                            <span>總金額</span>
                            <span class="text-red-600">NT$ {{ formatPrice(finalTotal) }}</span>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </MainLayout>
</template>

<style scoped>
.custom-scrollbar::-webkit-scrollbar {
    width: 4px;
}
.custom-scrollbar::-webkit-scrollbar-track {
    background: #f1f1f1; 
}
.custom-scrollbar::-webkit-scrollbar-thumb {
    background: #cbd5e1; 
    border-radius: 4px;
}
.custom-scrollbar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8; 
}
</style>
