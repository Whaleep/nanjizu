<script setup>
import { ref } from 'vue';
import MainLayout from '@/Layouts/MainLayout.vue'; // 引入 Layout
import { Head } from '@inertiajs/vue3'; // 用來設定 <title>

defineProps({
    message: String
});

const count = ref(0);
</script>

<template>
    <Head title="測試頁面" />

    <!-- 使用 Layout 包住內容 -->
    <MainLayout>
        <div class="flex flex-col items-center justify-center py-20">
            <h1 class="text-4xl font-bold text-blue-600 mb-4">Inertia V2 測試</h1>
            <p class="text-xl text-gray-700 mb-6">{{ message }}</p>

            <div class="p-6 bg-white rounded-lg shadow-lg border">
                <p class="mb-4">嘗試點擊按鈕，頁面不會重整：</p>
                <button
                    @click="count++"
                    class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                    計數器：{{ count }}
                </button>
            </div>
        </div>
    </MainLayout>
</template>
