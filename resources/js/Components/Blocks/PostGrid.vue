<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({ data: Object });
const posts = ref([]);
const isLoading = ref(true);

onMounted(async () => {
    try {
        const response = await axios.get('/api/posts/block', {
            params: {
                type: props.data.type,
                limit: props.data.limit
            }
        });
        posts.value = response.data;
    } catch (e) {
        console.error('無法載入文章', e);
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <!-- 背景色控制 -->
    <div :class="data.bg_color === 'gray' ? 'bg-gray-100' : 'bg-white'" class="py-16">
        <div class="container mx-auto px-4">
            
            <h2 class="text-3xl font-bold text-center mb-10">{{ data.heading }}</h2>
            
            <div v-if="isLoading" class="text-center text-gray-500 py-10">
                載入中...
            </div>

            <div v-else-if="posts.length > 0" class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div v-for="post in posts" :key="post.id" 
                     class="group bg-white border rounded-lg overflow-hidden shadow hover:shadow-lg transition flex flex-col">
                    
                    <Link :href="`/posts/${post.slug}`" class="block h-48 bg-gray-200 overflow-hidden relative">
                        <img v-if="post.image_url" :src="post.image_url" 
                             class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400">No Image</div>
                        
                        <!-- 標籤 (區分 News / Case) -->
                        <div class="absolute top-2 right-2 text-xs px-2 py-1 rounded text-white"
                             :class="post.category === 'news' ? 'bg-blue-600' : 'bg-green-600'">
                            {{ post.category === 'news' ? 'NEWS' : 'CASE' }}
                        </div>
                    </Link>

                    <div class="p-6 flex flex-col flex-grow">
                        <h3 class="font-bold text-lg mb-2 truncate group-hover:text-blue-600 transition">
                            <Link :href="`/posts/${post.slug}`">{{ post.title }}</Link>
                        </h3>
                        <p class="text-gray-500 text-sm mt-auto">{{ post.date }}</p>
                    </div>
                </div>
            </div>

            <div v-else class="text-center text-gray-500">
                目前沒有相關文章。
            </div>

        </div>
    </div>
</template>
