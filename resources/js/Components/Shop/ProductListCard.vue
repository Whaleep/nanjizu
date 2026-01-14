<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({
    image: String,
    title: String,
    subtitle: String,
    price: [Number, String],
    link: {
        type: String,
        default: '#'
    }
});

const formatPrice = (p) => new Intl.NumberFormat('zh-TW').format(p);
</script>

<template>
    <div class="group flex flex-row gap-3 md:gap-6 bg-white border rounded-xl p-3 md:p-4 hover:shadow-md transition items-start">
        
        <!-- 左側：圖片區 -->
        <Link :href="link" class="shrink-0 relative block w-24 h-24 md:w-32 md:h-32 bg-gray-100 rounded-lg overflow-hidden border">
            <img v-if="image" :src="`/storage/${image}`" class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
            <div v-else class="w-full h-full flex items-center justify-center text-xs text-gray-400">無圖</div>
        </Link>

        <!-- 右側：資訊與操作區 -->
        <div class="flex-grow flex flex-col justify-between min-h-[6rem] md:min-h-[8rem]">
            
            <!-- 上半部：標題與規格 -->
            <div>
                <Link :href="link" class="text-sm md:text-lg font-bold text-gray-800 line-clamp-2 hover:text-blue-600 mb-1 leading-tight">
                    {{ title }}
                </Link>
                <div v-if="subtitle" class="text-xs md:text-sm text-gray-500 bg-gray-100 inline-block px-2 py-0.5 rounded">
                    {{ subtitle }}
                </div>
            </div>

            <!-- 下半部：價格與操作 (Flex 佈局：手機版垂直排列，電腦版水平排列) -->
            <div class="mt-2 flex flex-col sm:flex-row sm:items-end justify-between gap-3">
                
                <!-- 價格 -->
                <div class="text-red-600 font-bold text-base md:text-xl">
                    NT$ {{ formatPrice(price) }}
                </div>

                <!-- 操作區插槽 (由父層決定放什麼) -->
                <div class="w-full sm:w-auto">
                    <slot name="actions" />
                </div>
            </div>
        </div>
    </div>
</template>
