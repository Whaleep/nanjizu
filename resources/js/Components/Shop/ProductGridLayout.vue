<script setup>
// 純展示用的 Grid 元件，這個元件不負責抓資料，只負責把傳進來的 Array 畫成漂亮的網格。
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import ProductCard from '@/Components/Shop/ProductCard.vue';

const props = defineProps({
    products: {
        type: [Array, Object],
        default: () => [],
    },
    // 預留擴充：是否顯示標題或其他控制項
    heading: String,
    // 空狀態訊息
    emptyMessage: {
        type: String,
        default: null,
    },
});

const items = computed(() => {
    if (Array.isArray(props.products)) {
        return props.products;
    }
    // 如果是 Laravel Paginator 物件 (或是 Resource Collection)
    return props.products?.data || [];
});

const paginator = computed(() => {
    // 只有當傳入的是物件且有 links 時才視為有分頁
    if (!Array.isArray(props.products) && props.products?.links) {
        return props.products;
    }
    return null;
});
</script>

<template>
    <div class="w-full">
        <!-- 標題 (選填) -->
        <h2 v-if="heading" class="text-3xl font-bold text-center mb-10">{{ heading }}</h2>
        
        <!-- Grid 本體 -->
        <div v-if="items.length > 0" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <ProductCard 
                v-for="product in items" 
                :key="product.id" 
                :product="product" 
            />
        </div>
        
        <!-- 空狀態 -->
        <div v-else-if="emptyMessage" class="text-center text-gray-500 py-10 border border-dashed rounded-lg">
            {{ emptyMessage }}
        </div>

        <!-- 分頁 -->
        <div v-if="paginator && paginator.links.length > 3" class="mt-10 flex justify-center gap-1">
            <template v-for="(link, index) in paginator.links" :key="index">
                <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-4 py-2 border rounded-md text-sm" :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" />
                <span v-else v-html="link.label" class="px-4 py-2 border rounded-md text-sm text-gray-400 bg-gray-50"></span>
            </template>
        </div>

    </div>
</template>