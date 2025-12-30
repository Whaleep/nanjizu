<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

defineProps({
    latestPosts: Array
});

// 日期格式化
const formatDate = (date) => new Date(date).toLocaleDateString('zh-TW');
</script>

<template>
    <Head title="首頁" />
    <MainLayout>

        <!-- Hero Banner -->
        <!-- 注意：背景圖路徑要正確，若是在 public/images 下 -->
        <div class="relative w-full h-[500px] md:h-[600px] bg-cover bg-center bg-no-repeat flex items-center justify-center"
             style="background-image: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('/images/hero-bg.jpg');">

            <div class="container mx-auto px-4 text-center text-white relative z-10">
                <h1 class="text-4xl md:text-6xl font-bold mb-6 tracking-wide drop-shadow-md">您的手機急診室</h1>
                <p class="text-xl md:text-2xl mb-10 text-gray-200 drop-shadow-md">iPhone / Android / MacBook / iPad 專業快速維修</p>

                <div class="flex flex-col md:flex-row justify-center gap-6">
                    <Link href="/repair" class="bg-blue-600 border border-blue-600 text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-blue-700 transition shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        查詢維修價格
                    </Link>
                    <Link href="/shop" class="bg-transparent border-2 border-white text-white px-8 py-4 rounded-lg font-bold text-lg hover:bg-white hover:text-blue-900 transition shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                        線上商店
                    </Link>
                </div>
            </div>
        </div>

        <!-- 快速入口 -->
        <div class="container mx-auto px-4 py-16">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-6 text-center">
                <Link href="/repair" class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition group border hover:border-blue-500">
                    <div class="w-16 h-16 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-blue-600 group-hover:text-white transition text-2xl">🛠</div>
                    <h3 class="font-bold text-lg">手機維修</h3>
                </Link>
                <Link href="/shop" class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition group border hover:border-green-500">
                    <div class="w-16 h-16 bg-green-100 text-green-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-green-600 group-hover:text-white transition text-2xl">🛒</div>
                    <h3 class="font-bold text-lg">線上商店</h3>
                </Link>
                <Link href="/process" class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition group border hover:border-purple-500">
                    <div class="w-16 h-16 bg-purple-100 text-purple-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-purple-600 group-hover:text-white transition text-2xl">📦</div>
                    <h3 class="font-bold text-lg">送修流程</h3>
                </Link>
                <Link href="/stores" class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition group border hover:border-orange-500">
                    <div class="w-16 h-16 bg-orange-100 text-orange-600 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-orange-600 group-hover:text-white transition text-2xl">📍</div>
                    <h3 class="font-bold text-lg">門市據點</h3>
                </Link>
            </div>
        </div>

        <!-- 最新消息 -->
        <div class="bg-gray-100 py-16">
            <div class="container mx-auto px-4">
                <h2 class="text-3xl font-bold text-center mb-10">最新消息</h2>

                <div v-if="latestPosts.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                    <div v-for="post in latestPosts" :key="post.id" class="bg-white rounded-lg overflow-hidden shadow hover:shadow-lg transition">
                        <Link :href="`/posts/${post.slug}`" class="block h-48 bg-gray-200 overflow-hidden">
                            <img v-if="post.image" :src="`/storage/${post.image}`" class="w-full h-full object-cover hover:scale-105 transition duration-500">
                            <div v-else class="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
                        </Link>
                        <div class="p-6">
                            <h3 class="font-bold text-lg mb-2 truncate">
                                <Link :href="`/posts/${post.slug}`" class="hover:text-blue-600">{{ post.title }}</Link>
                            </h3>
                            <p class="text-gray-500 text-sm">{{ post.published_at ? formatDate(post.published_at) : '' }}</p>
                        </div>
                    </div>
                </div>
                <div v-else class="text-center text-gray-500">目前沒有最新消息。</div>
            </div>
        </div>

    </MainLayout>
</template>
