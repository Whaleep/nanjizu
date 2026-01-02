<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    cartItems: Array,
    total: Number,
});
const page = usePage();
const user = page.props.auth.user; // 取得登入者

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

const form = useForm({
    customer_name: user ? user.name : '',
    customer_phone: user ? user.phone : '',
    customer_email: user ? user.email : '',
    customer_address: user ? user.address : '',
    notes: '',
    payment_method: 'cod', // 預設貨到付款
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

            <div class="max-w-4xl mx-auto grid grid-cols-1 md:grid-cols-2 gap-10">
                <!-- 左側：表單 -->
                <div>
                    <!--
                        技巧：使用傳統 Form 提交到 /v1/checkout
                        這樣後端回傳綠界 HTML 時，瀏覽器會直接渲染並執行跳轉
                    -->
                    <form action="/checkout" method="POST" id="real-checkout-form">
                        <!-- CSRF Token (Laravel Blade 會自動加，Vue 要手動加) -->
                        <input type="hidden" name="_token" :value="$page.props.csrf_token">

                        <div class="bg-white p-6 rounded-lg shadow border mb-6">
                            <h3 class="text-xl font-bold mb-4">收件人資訊</h3>

                            <div class="mb-4">
                                <label class="block font-bold mb-2">姓名 *</label>
                                <input type="text" name="customer_name" required v-model="form.customer_name" class="w-full border rounded px-3 py-2">
                            </div>
                            <div class="mb-4">
                                <label class="block font-bold mb-2">電話 *</label>
                                <input type="text" name="customer_phone" required v-model="form.customer_phone" class="w-full border rounded px-3 py-2">
                            </div>
                            <div class="mb-4">
                                <label class="block font-bold mb-2">Email</label>
                                <input type="email" name="customer_email" v-model="form.customer_email" class="w-full border rounded px-3 py-2">
                            </div>
                            <div class="mb-4">
                                <label class="block font-bold mb-2">地址 *</label>
                                <textarea name="customer_address" required v-model="form.customer_address" class="w-full border rounded px-3 py-2" rows="2"></textarea>
                            </div>
                            <div class="mb-4">
                                <label class="block font-bold mb-2">備註</label>
                                <textarea name="notes" v-model="form.notes" class="w-full border rounded px-3 py-2" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-lg shadow border mb-6">
                            <h3 class="text-xl font-bold mb-4">付款方式</h3>
                            <div class="space-y-2">
                                <label class="flex items-center gap-2 p-3 border rounded cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="bank_transfer" v-model="form.payment_method">
                                    <span>銀行轉帳 / ATM (人工對帳)</span>
                                </label>
                                <label class="flex items-center gap-2 p-3 border rounded cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="cod" v-model="form.payment_method">
                                    <span>貨到付款</span>
                                </label>
                                <label class="flex items-center gap-2 p-3 border rounded cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="bank_transfer" v-model="form.payment_method">
                                    <span>銀行匯款</span>
                                </label>
                                <label class="flex items-center gap-2 p-3 border rounded cursor-pointer hover:bg-gray-50">
                                    <input type="radio" name="payment_method" value="ecpay" v-model="form.payment_method">
                                    <span>綠界支付</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-4 rounded-lg hover:bg-blue-700 transition shadow-lg text-lg">
                            提交訂單 (NT$ {{ formatPrice(total) }})
                        </button>
                    </form>
                </div>

                <!-- 右側：摘要 (純顯示) -->
                <div>
                    <div class="bg-gray-50 p-6 rounded-lg border sticky top-24">
                        <h3 class="text-xl font-bold mb-4 border-b pb-2">購買清單</h3>
                        <ul class="space-y-4 mb-6">
                            <li v-for="item in cartItems" :key="item.variant_id" class="flex justify-between">
                                <div>
                                    <div class="font-bold">{{ item.product_name }}</div>
                                    <div class="text-sm text-gray-500">{{ item.variant_name }} x {{ item.quantity }}</div>
                                </div>
                                <div class="font-bold">NT$ {{ formatPrice(item.subtotal) }}</div>
                            </li>
                        </ul>
                        <div class="flex justify-between text-xl font-bold border-t pt-4">
                            <span>總金額</span>
                            <span class="text-red-600">NT$ {{ formatPrice(total) }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
