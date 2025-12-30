<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import DashboardLayout from '@/Layouts/DashboardLayout.vue';

const props = defineProps({ user: Object });

const form = useForm({
    name: props.user.name,
    email: props.user.email,
    phone: props.user.phone || '',
    address: props.user.address || '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post('/dashboard/profile', {
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
            alert('資料更新成功！');
        }
    });
};
</script>

<template>
    <Head title="個人資料" />
    <DashboardLayout>
        <h1 class="text-2xl font-bold mb-6">編輯個人資料</h1>

        <div class="bg-white rounded-lg shadow border p-6">
            <form @submit.prevent="submit">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                    <div>
                        <label class="block font-bold mb-2">姓名</label>
                        <input type="text" v-model="form.name" class="w-full border rounded px-3 py-2">
                        <div v-if="form.errors.name" class="text-red-500 text-sm mt-1">{{ form.errors.name }}</div>
                    </div>
                    <div>
                        <label class="block font-bold mb-2">Email</label>
                        <input type="email" v-model="form.email" class="w-full border rounded px-3 py-2">
                        <div v-if="form.errors.email" class="text-red-500 text-sm mt-1">{{ form.errors.email }}</div>
                    </div>
                    <div>
                        <label class="block font-bold mb-2">電話</label>
                        <input type="text" v-model="form.phone" class="w-full border rounded px-3 py-2">
                    </div>
                    <div>
                        <label class="block font-bold mb-2">預設收件地址</label>
                        <input type="text" v-model="form.address" class="w-full border rounded px-3 py-2">
                    </div>
                </div>

                <div class="border-t pt-6 mb-6">
                    <h3 class="font-bold text-gray-700 mb-4">變更密碼 (不修改請留空)</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-gray-600 mb-2">新密碼</label>
                            <input type="password" v-model="form.password" class="w-full border rounded px-3 py-2">
                            <div v-if="form.errors.password" class="text-red-500 text-sm mt-1">{{ form.errors.password }}</div>
                        </div>
                        <div>
                            <label class="block text-gray-600 mb-2">確認新密碼</label>
                            <input type="password" v-model="form.password_confirmation" class="w-full border rounded px-3 py-2">
                        </div>
                    </div>
                </div>

                <button type="submit" :disabled="form.processing" class="bg-blue-600 text-white px-6 py-2 rounded font-bold hover:bg-blue-700 transition">
                    儲存變更
                </button>
            </form>
        </div>
    </DashboardLayout>
</template>
