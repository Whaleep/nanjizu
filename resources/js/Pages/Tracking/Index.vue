<script setup>
import { useForm, Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    prefilledOrderNumber: String
});

const form = useForm({
    order_number: props.prefilledOrderNumber || '',
    customer_phone: '',
});

const submit = () => {
    form.post('/tracking');
};
</script>

<template>
    <Head title="訂單查詢" />
    <MainLayout>
        <div class="container mx-auto px-4 py-20 flex justify-center">
            <div class="w-full max-w-md bg-white p-8 rounded-lg shadow-lg border border-gray-100">
                <h1 class="text-2xl font-bold mb-2 text-center text-gray-800">訂單狀態查詢</h1>
                <p class="text-sm text-gray-500 text-center mb-8">請輸入訂單編號與訂購時填寫的電話號碼</p>

                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">訂單編號</label>
                        <input type="text" v-model="form.order_number"
                               placeholder="例如: ORD2025..."
                               class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none uppercase">
                        <div v-if="form.errors.order_number" class="text-red-500 text-sm mt-1">{{ form.errors.order_number }}</div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 font-bold mb-2">聯絡電話</label>
                        <input type="text" v-model="form.customer_phone"
                               placeholder="訂購人電話"
                               class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-blue-500 outline-none">
                    </div>

                    <button type="submit" :disabled="form.processing"
                            class="w-full bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 transition shadow-md disabled:opacity-50">
                        {{ form.processing ? '查詢中...' : '查詢訂單' }}
                    </button>
                </form>
            </div>
        </div>
    </MainLayout>
</template>
