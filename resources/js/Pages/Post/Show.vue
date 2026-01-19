<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    post: Object
});

import blockComponents from '@/Components/Blocks';
const components = blockComponents;

const formatDate = (date) => new Date(date).toLocaleString('zh-TW', { hour12: false, dateStyle: 'long', timeStyle: 'short' });
</script>

<template>
    <Head :title="post.title" />
    <MainLayout>
        <div class="container mx-auto px-4 py-12">
            <div class="max-w-3xl mx-auto bg-white p-8 rounded-lg shadow-lg border">

                <div class="mb-6 border-b pb-4">
                    <span class="text-xs font-semibold px-2.5 py-0.5 rounded"
                          :class="post.category === 'news' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'">
                        {{ post.category === 'news' ? '最新消息' : '維修案例' }}
                    </span>
                    <h1 class="text-3xl font-bold mt-3 mb-2 text-gray-900">{{ post.title }}</h1>
                    <p class="text-gray-500 text-sm">
                        {{ post.published_at ? formatDate(post.published_at) : '' }}
                    </p>
                </div>

                <div v-if="post.featured_image_url || post.image" class="mb-8 rounded-lg overflow-hidden shadow-sm">
                    <img :src="post.featured_image_url ? post.featured_image_url : (post.image.startsWith('http') ? post.image : `/storage/${post.image}`)" class="w-full h-auto object-cover">
                </div>

                <!-- 文章內容區 (ContentBlocks) -->
                <div v-if="post.content && post.content.length > 0" class="space-y-4">
                    <div v-for="(block, index) in post.content" :key="index">
                        <component :is="components[block.type]" v-if="components[block.type]" :data="block.data" />
                    </div>
                </div>
                
                <!-- 相容舊資料 (純 HTML) -->
                <div v-else-if="typeof post.content === 'string'" class="prose prose-lg max-w-none text-gray-800" v-html="post.content"></div>

                <div class="mt-12 pt-8 border-t flex justify-between items-center">
                    <Link :href="post.category === 'news' ? '/news' : '/cases'"
                          class="text-blue-600 hover:underline font-bold flex items-center gap-1">
                        &larr; 返回列表
                    </Link>
                    <Link href="/repair" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition shadow">
                        立即預約維修
                    </Link>
                </div>
            </div>
        </div>
    </MainLayout>
</template>
