<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const props = defineProps({
    title: String,
    posts: Object,
    type: String, // 'news' or 'case'
});

const formatDate = (date) => new Date(date).toLocaleDateString('zh-TW');
</script>

<template>
    <Head :title="title" />
    <MainLayout>
        <div class="container mx-auto px-4 py-12">
            <h1 class="text-3xl font-bold text-center mb-10">{{ title }}</h1>

            <div v-if="posts.data.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div v-for="post in posts.data" :key="post.id"
                     class="group bg-white border rounded-lg overflow-hidden shadow hover:shadow-lg transition flex flex-col">

                    <Link :href="`/posts/${post.slug}`" class="block h-56 bg-gray-200 overflow-hidden relative">
                        <img v-if="post.featured_image_url || post.image" 
                             :src="post.featured_image_url ? post.featured_image_url : (post.image.startsWith('http') ? post.image : `/storage/${post.image}`)" 
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <div v-else class="flex items-center justify-center h-full text-gray-400">無圖片</div>

                        <!-- 標籤 -->
                        <div class="absolute top-2 right-2 text-xs px-2 py-1 rounded text-white"
                             :class="type === 'news' ? 'bg-blue-600' : 'bg-green-600'">
                            {{ type === 'news' ? 'NEWS' : 'CASE' }}
                        </div>
                    </Link>

                    <div class="p-6 flex flex-col flex-grow">
                        <h3 class="font-bold text-xl mb-3 group-hover:text-blue-600 transition line-clamp-2">
                            <Link :href="`/posts/${post.slug}`">{{ post.title }}</Link>
                        </h3>
                        <p class="text-gray-500 text-sm mt-auto">
                            發布日期：{{ post.published_at ? formatDate(post.published_at) : '未定' }}
                        </p>
                    </div>
                </div>
            </div>

            <div v-else class="text-center py-20 bg-gray-50 rounded-lg">
                <p class="text-gray-500 text-xl">目前尚無{{ title }}。</p>
            </div>

            <!-- 分頁元件 (簡單版) -->
            <div v-if="posts.links.length > 3" class="mt-12 flex justify-center gap-1">
                <template v-for="(link, index) in posts.links" :key="index">
                    <Link v-if="link.url" :href="link.url" v-html="link.label"
                          class="px-4 py-2 border rounded-md text-sm"
                          :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" />
                    <span v-else v-html="link.label" class="px-4 py-2 border rounded-md text-sm text-gray-400 bg-gray-50"></span>
                </template>
            </div>
        </div>
    </MainLayout>
</template>
