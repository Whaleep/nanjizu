<script setup>
// 純展示用的 Grid 元件，這個元件不負責抓資料，只負責把傳進來的 Array 畫成漂亮的網格。
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import ProductCard from '@/Components/Shop/ProductCard.vue';
import ProductCardSmall from '@/Components/Shop/ProductCardSmall.vue';

const props = defineProps({
    products: {
        type: [Array, Object],
        default: () => [],
    },
    // 預留擴充：是否顯示標題或其他控制項
    heading: String,
    // 顯示變體：standard (預設), small
    variant: {
        type: String,
        default: 'standard',
    },
    // 空狀態訊息
    emptyMessage: {
        type: String,
        default: null,
    },
    // 是否顯示操作按鈕 (例如加入購物車)
    showAction: {
        type: Boolean,
        default: true,
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

// Carousel 滾動邏輯
const scrollContainer = ref(null);
const scroll = (direction) => {
    if (!scrollContainer.value) return;
    const container = scrollContainer.value;
    const scrollAmount = container.offsetWidth * 0.8;
    container.scrollBy({
        left: direction === 'left' ? -scrollAmount : scrollAmount,
        behavior: 'smooth'
    });
};
</script>

<template>
    <div class="w-full">
        <!-- 標題 (選填) -->
        <h2 v-if="heading" class="text-3xl font-bold text-center mb-10">{{ heading }}</h2>
        
        <!-- Grid / Carousel 本體 -->
        <div v-if="items.length > 0" class="relative group/grid">
            
            <!-- 小卡片橫向滾動模式 (Carousel) -->
            <template v-if="variant === 'small'">
                <!-- 左箭頭 -->
                <button @click="scroll('left')" 
                        class="absolute left-0 top-1/2 -translate-y-1/2 -translate-x-4 z-10 w-10 h-10 bg-white shadow-lg rounded-full items-center justify-center text-gray-400 hover:text-blue-600 hover:scale-110 transition opacity-0 group-hover/grid:opacity-100 hidden md:flex border border-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                </button>

                <!-- 滾動容器 -->
                <div ref="scrollContainer" 
                     class="flex gap-4 overflow-x-auto pb-4 scroll-smooth hide-scrollbar snap-x snap-mandatory px-4 md:px-0">
                    <div v-for="product in items" 
                         :key="`small-${product.id}`" 
                         class="min-w-[160px] max-w-[180px] flex-shrink-0 snap-start">
                        <ProductCardSmall 
                            :product="product" 
                            :show-action="showAction"
                        />
                    </div>
                </div>

                <!-- 右箭頭 -->
                <button @click="scroll('right')" 
                        class="absolute right-0 top-1/2 -translate-y-1/2 translate-x-4 z-10 w-10 h-10 bg-white shadow-lg rounded-full items-center justify-center text-gray-400 hover:text-blue-600 hover:scale-110 transition opacity-0 group-hover/grid:opacity-100 hidden md:flex border border-gray-100">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                </button>
            </template>

            <!-- 標準卡片模式 (Grid) -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                <ProductCard v-for="product in items" 
                    :key="`standard-${product.id}`" 
                    :product="product" 
                    :show-action="showAction"
                />
            </div>
        </div>
        
        <!-- 空狀態 -->
        <div v-else-if="emptyMessage" class="text-center text-gray-500 py-10 border border-dashed rounded-lg">
            {{ emptyMessage }}
        </div>

        <!-- 分頁 (僅在標準 Grid 模式且有分頁時才顯示，Carousel 通常不顯示這個) -->
        <div v-if="variant === 'standard' && paginator && paginator.links.length > 3" class="mt-10 flex justify-center gap-1">
            <template v-for="(link, index) in paginator.links" :key="index">
                <Link v-if="link.url" :href="link.url" v-html="link.label" class="px-4 py-2 border rounded-md text-sm" :class="link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50'" />
                <span v-else v-html="link.label" class="px-4 py-2 border rounded-md text-sm text-gray-400 bg-gray-50"></span>
            </template>
        </div>

    </div>
</template>

<style scoped>
/* 隱藏滾動條但保持滾動功能 */
.hide-scrollbar::-webkit-scrollbar {
    display: none;
}
.hide-scrollbar {
    -ms-overflow-style: none;  /* IE and Edge */
    scrollbar-width: none;  /* Firefox */
}
</style>
