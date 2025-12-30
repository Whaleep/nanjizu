<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    device: Object
});

// 使用 Inertia useForm 處理表單
const form = useForm({
    device_model: `${props.device.brand.name} ${props.device.name}`,
    customer_name: '',
    phone: '',
    message: ''
});

const submit = () => {
    // 這裡我們 POST 到原本 V1 的路由 /v1/inquiry (因為邏輯一樣)
    // Inertia 會自動處理回應 (例如 redirect back)
    form.post('/v1/inquiry', {
        onSuccess: () => {
            form.reset('customer_name', 'phone', 'message');
            // 注意：V1 Controller 是 return back()->with('success')
            // 這個 flash message 會透過 Middleware 傳給前端，我們在 Layout 或這裡顯示
            alert('預約成功！我們將盡快聯繫您。');
        }
    });
};

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);
</script>

<template>
    <Head :title="`${device.name} 維修價格`" />
    <MainLayout>
        <div class="container mx-auto px-4 py-8">
            <!-- 麵包屑 -->
            <div class="mb-6">
                <Link href="/repair" class="text-blue-600 hover:underline flex items-center gap-1">
                    ← 返回選擇機型
                </Link>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-10">

                <!-- 左側：報價表 -->
                <div class="lg:col-span-2">
                    <div class="flex items-baseline gap-4 mb-2">
                        <h1 class="text-3xl font-bold">{{ device.name }}</h1>
                        <span class="text-gray-500 text-lg">維修價目表</span>
                    </div>

                    <p class="text-gray-500 mb-6 text-sm bg-yellow-50 p-3 rounded border border-yellow-200">
                        ⚠️ 價格僅供參考，實際狀況請以現場工程師檢測為主。
                    </p>

                    <div class="bg-white shadow rounded-lg overflow-hidden border">
                        <table class="w-full text-left">
                            <thead class="bg-gray-50 border-b">
                                <tr>
                                    <th class="p-4 font-bold text-gray-700">維修項目</th>
                                    <th class="p-4 font-bold text-gray-700">價格</th>
                                    <th class="p-4 font-bold text-gray-700">備註</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="price in device.prices" :key="price.id" class="hover:bg-blue-50 transition border-b last:border-0">
                                    <td class="p-4 font-medium">{{ price.repair_item.name }}</td>
                                    <td class="p-4 text-red-600 font-bold">NT$ {{ formatPrice(price.price) }}</td>
                                    <td class="p-4 text-sm text-gray-500">{{ price.note }}</td>
                                </tr>
                                <tr v-if="device.prices.length === 0">
                                    <td colspan="3" class="p-8 text-center text-gray-500">目前尚無公開報價，請直接填寫右側表單諮詢。</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 右側：預約表單 -->
                <div class="lg:col-span-1">
                    <div class="bg-white p-6 shadow-lg rounded-lg sticky top-24 border-t-4 border-blue-600">
                        <h3 class="text-xl font-bold mb-4">線上預約 / 諮詢</h3>

                        <form @submit.prevent="submit">

                            <div class="mb-4">
                                <label class="block text-gray-700 font-bold mb-2">您的姓名</label>
                                <input type="text" v-model="form.customer_name" required class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                                <div v-if="form.errors.customer_name" class="text-red-500 text-xs mt-1">{{ form.errors.customer_name }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 font-bold mb-2">聯絡電話</label>
                                <input type="text" v-model="form.phone" required class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none">
                                <div v-if="form.errors.phone" class="text-red-500 text-xs mt-1">{{ form.errors.phone }}</div>
                            </div>

                            <div class="mb-4">
                                <label class="block text-gray-700 font-bold mb-2">故障狀況描述</label>
                                <textarea v-model="form.message" rows="3" class="w-full border rounded px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none" placeholder="例如：螢幕破裂..."></textarea>
                            </div>

                            <button type="submit" :disabled="form.processing"
                                    class="w-full bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700 transition disabled:opacity-50">
                                {{ form.processing ? '送出中...' : '送出預約' }}
                            </button>
                        </form>
                    </div>
                </div>

            </div>
        </div>
    </MainLayout>
</template>
