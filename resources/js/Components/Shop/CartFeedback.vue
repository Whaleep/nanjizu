<script setup>
import { ref, watch, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    item: {
        type: Object,
        required: true
    },
    show: {
        type: Boolean,
        default: false
    }
});

const emit = defineEmits(['close']);

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

// Auto-close logic
const timer = ref(null);
const animationKey = ref(0); // 用來強制重啟 CSS 動畫的 Key

const startTimer = () => {
    stopTimer();
    animationKey.value++; // 每次啟動/重啟計時器時，透過變更 Key 強制元素重新渲染，從而重啟 CSS 動畫
    timer.value = setTimeout(() => {
        emit('close');
    }, 3000);
};

const stopTimer = () => {
    if (timer.value) clearTimeout(timer.value);
};

// watch 監視器：監視 show 屬性的變動
// 當 show 變成 true 時，啟動 3 秒倒數計時
watch(() => props.show, (newVal) => {
    if (newVal) {
        startTimer();
    } else {
        stopTimer();
    }
}, { immediate: true });

onUnmounted(() => {
    stopTimer();
});
</script>

<template>
    <transition
        enter-active-class="transition ease-out duration-300 transform"
        enter-from-class="translate-x-full opacity-0"
        enter-to-class="translate-x-0 opacity-100"
        leave-active-class="transition ease-in duration-200 transform"
        leave-from-class="translate-x-0 opacity-100"
        leave-to-class="translate-x-full opacity-0"
    >
        <div v-if="show" 
             @mouseenter="stopTimer" 
             @mouseleave="startTimer"
             class="fixed top-20 right-4 z-[60] w-80 bg-white rounded-xl shadow-2xl border border-blue-100 overflow-hidden feedback-card">
            
            <!-- Header -->
            <div class="bg-blue-600 px-4 py-2 flex justify-between items-center">
                <div class="flex items-center gap-2 text-white">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span class="font-bold text-sm">已加入購物車</span>
                </div>
                <button @click="$emit('close')" class="text-white/80 hover:text-white transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Content -->
            <div class="p-4">
                <div class="flex gap-4 mb-4">
                    <div class="w-20 h-20 flex-shrink-0 bg-gray-100 rounded-lg overflow-hidden border">
                        <!-- item.image 若已經包含 /storage/ 則不需再加，否則補上 -->
                        <img v-if="item.image" :src="item.image.startsWith('/') ? item.image : `/storage/${item.image}`" class="w-full h-full object-cover">
                        <div v-else class="w-full h-full flex items-center justify-center text-gray-400 text-xs">無圖</div>
                    </div>
                    <div class="flex-grow min-w-0">
                        <h4 class="font-bold text-gray-800 text-sm line-clamp-2 mb-1">{{ item.product_name }}</h4>
                        <div class="text-xs text-gray-500 mb-1">規格：{{ item.variant_name || '單一規格' }}</div>
                        <div class="flex justify-between items-end">
                            <div class="text-xs text-gray-500">數量：{{ item.quantity }}</div>
                            <div class="text-blue-600 font-bold">NT$ {{ formatPrice(item.price) }}</div>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <button @click="$emit('close')" 
                            class="flex-1 px-4 py-2 border border-gray-200 rounded-lg text-sm font-medium text-gray-600 hover:bg-gray-50 transition">
                        繼續購物
                    </button>
                    <Link href="/cart" 
                          class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg text-sm font-bold text-center hover:bg-blue-700 transition">
                        查看購物車
                    </Link>
                </div>
            </div>

            <!-- Progress bar: 加上 :key 以便在計時重啟時同步啟動動畫 -->
            <div class="h-1 bg-blue-50">
                <div :key="animationKey" class="h-full bg-blue-500 progress-bar-countdown"></div>
            </div>
        </div>
    </transition>
</template>

<style scoped>
.progress-bar-countdown {
    width: 100%;
    animation: countdown 3s linear forwards;
    transform-origin: left;
}

@keyframes countdown {
    from { width: 100%; }
    to { width: 0%; }
}

/* 懸停時隱藏進度條 (因為計時已停止)，或保持現狀。
   由於我們在 mouseleave 會重啟動畫，這裡就不需要 animation-play-state: paused 了，
   因為滑鼠在上面的時候動畫會消失(元素Key不變但計時器停止)，
   或是我們決定滑鼠在上面的時候進度條就停在 100%。 */
.feedback-card:hover .progress-bar-countdown {
    animation: none;
    width: 100%;
}
</style>
