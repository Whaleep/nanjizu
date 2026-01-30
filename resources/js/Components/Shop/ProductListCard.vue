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
    <div class="group flex flex-row gap-3 md:gap-6 bg-white border border-gray-100 rounded-xl p-3 md:p-4 hover:shadow-lg transition duration-300 items-start relative">
        
        <!-- 左側：圖片區 -->
        <Link :href="link" class="shrink-0 relative block w-24 h-24 md:w-32 md:h-32 bg-gray-50 rounded-lg overflow-hidden border border-gray-200">
            <img v-if="image" :src="image.startsWith('http') || image.startsWith('/') ? image : `/storage/${image}`" class="w-full h-full object-cover group-hover:scale-110 transition duration-500">
            <div v-else class="w-full h-full flex items-center justify-center text-xs text-gray-400 bg-gray-100">無圖</div>
        </Link>

        <!-- 右側：資訊與操作區 -->
        <div class="flex-grow flex flex-col justify-between min-h-[6rem] md:min-h-[8rem]">
            
            <!-- 上半部：標題與規格 -->
            <div>
                <Link :href="link" class="text-sm md:text-lg font-bold text-gray-800 line-clamp-2 hover:text-blue-600 mb-1 leading-tight tracking-tight">
                    {{ title }}
                </Link>
                <div v-if="subtitle" class="text-xs md:text-sm text-gray-500 bg-gray-100 inline-block px-2 py-0.5 rounded">
                    {{ subtitle }}
                </div>
            </div>

            <!-- 額外資訊 Slot (特惠提示/贈品資訊) -->
            <div v-if="$slots['extra-info']" class="mt-2 text-xs md:text-sm relative z-10">
                <slot name="extra-info" />
            </div>

            <!-- 下半部：價格與操作 -->
            <div class="mt-3 flex flex-col sm:flex-row sm:items-end justify-between gap-3 border-t border-gray-50 pt-2 md:border-none md:pt-0">
                
                <!-- 價格 -->
                <div class="text-red-600 font-bold text-base md:text-xl flex items-baseline gap-1">
                    <span v-if="price > 0" class="text-xs text-gray-400 font-normal">NT$</span>
                    {{ price > 0 ? formatPrice(price) : 'FREE' }}
                </div>

                <!-- 操作區插槽 -->
                <div class="w-full sm:w-auto">
                    <slot name="actions" />
                </div>
            </div>
        </div>
    </div>
</template>
