<script setup>
import { useForm, Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/register');
};
</script>

<template>
    <Head title="會員註冊" />
    <MainLayout>
        <div class="container mx-auto px-4 py-20 flex justify-center">
            <div class="w-full max-w-md bg-white p-8 rounded-lg shadow border">
                <h1 class="text-2xl font-bold mb-6 text-center">註冊會員</h1>

                <form @submit.prevent="submit">
                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">姓名</label>
                        <input type="text" v-model="form.name" class="w-full border rounded px-3 py-2">
                        <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">Email</label>
                        <input type="email" v-model="form.email" class="w-full border rounded px-3 py-2">
                        <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">電話</label>
                        <input type="text" v-model="form.phone" class="w-full border rounded px-3 py-2">
                    </div>

                    <div class="mb-4">
                        <label class="block text-gray-700 font-bold mb-2">密碼</label>
                        <input type="password" v-model="form.password" class="w-full border rounded px-3 py-2">
                        <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</div>
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-700 font-bold mb-2">確認密碼</label>
                        <input type="password" v-model="form.password_confirmation" class="w-full border rounded px-3 py-2">
                    </div>

                    <button type="submit" :disabled="form.processing" class="w-full bg-blue-600 text-white font-bold py-3 rounded hover:bg-blue-700 transition">
                        註冊
                    </button>
                </form>

                <div class="mt-4 text-center text-sm">
                    已有帳號？ <Link href="/login" class="text-blue-600 hover:underline">登入</Link>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
