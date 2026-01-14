<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Swiper, SwiperSlide } from 'swiper/vue';
import { Autoplay, Pagination, Navigation, EffectFade } from 'swiper/modules';

// Import Swiper styles
import 'swiper/css';
import 'swiper/css/pagination';
import 'swiper/css/navigation';
import 'swiper/css/effect-fade';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            slides: [], // Array of { image, heading, subheading, button_text, button_url }
            height: 'h-[500px] md:h-[600px]',
            autoplay_delay: 5000,
        })
    }
});

const modules = [Autoplay, Pagination, Navigation, EffectFade];

const getImageUrl = (img) => {
    if (!img) return '';
    if (img.startsWith('http') || img.startsWith('/')) return img;
    return `/storage/${img}`;
};
</script>

<template>
    <div class="w-full relative group">
        <swiper
            :modules="modules"
            :slides-per-view="1"
            :space-between="0"
            effect="fade"
            :loop="true"
            :autoplay="{
                delay: data.autoplay_delay || 5000,
                disableOnInteraction: false,
            }"
            :pagination="{
                clickable: true,
                dynamicBullets: true,
            }"
            :navigation="{
                nextEl: '.swiper-button-next-custom',
                prevEl: '.swiper-button-prev-custom',
            }"
            class="w-full"
            :class="data.height || 'h-[500px] md:h-[600px]'"
        >
            <swiper-slide v-for="(slide, index) in data.slides" :key="index" class="relative">
                <!-- Background Image -->
                <div class="absolute inset-0 bg-cover bg-center transition-transform duration-[5s] hover:scale-105"
                     :style="`background-image: url('${getImageUrl(slide.image)}')`">
                </div>

                <!-- Overlay -->
                <div class="absolute inset-0 bg-black/40 bg-gradient-to-t from-black/60 to-transparent"></div>

                <!-- Content -->
                <div class="relative z-10 container mx-auto px-6 h-full flex flex-col justify-center items-center text-center text-white">
                    <h2 v-if="slide.heading" 
                        class="text-4xl md:text-6xl font-bold mb-4 tracking-tight drop-shadow-lg opacity-0 animate-fade-in-up"
                        style="animation-delay: 0.2s; animation-fill-mode: forwards;">
                        {{ slide.heading }}
                    </h2>
                    
                    <p v-if="slide.subheading" 
                       class="text-lg md:text-2xl mb-8 max-w-2xl font-light drop-shadow-md opacity-0 animate-fade-in-up"
                       style="animation-delay: 0.4s; animation-fill-mode: forwards;">
                        {{ slide.subheading }}
                    </p>

                    <Link v-if="slide.button_text && slide.button_url" 
                          :href="slide.button_url"
                          class="inline-block bg-white text-gray-900 hover:bg-blue-600 hover:text-white px-8 py-3 rounded-full font-bold text-lg transition-all duration-300 shadow-[0_4px_14px_0_rgba(255,255,255,0.39)] hover:shadow-[0_6px_20px_rgba(37,99,235,0.23)] hover:-translate-y-1 opacity-0 animate-fade-in-up"
                          style="animation-delay: 0.6s; animation-fill-mode: forwards;">
                        {{ slide.button_text }}
                    </Link>
                </div>
            </swiper-slide>
        </swiper>

        <!-- Custom Navigation Buttons -->
        <div class="swiper-button-prev-custom absolute left-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white cursor-pointer hover:bg-white/30 transition shadow-lg opacity-0 group-hover:opacity-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </div>
        <div class="swiper-button-next-custom absolute right-4 top-1/2 -translate-y-1/2 z-20 w-12 h-12 bg-white/10 backdrop-blur-md rounded-full flex items-center justify-center text-white cursor-pointer hover:bg-white/30 transition shadow-lg opacity-0 group-hover:opacity-100">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </div>
    </div>
</template>

<style>
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translate3d(0, 40px, 0);
    }
    to {
        opacity: 1;
        transform: translate3d(0, 0, 0);
    }
}

.animate-fade-in-up {
    animation-name: fadeInUp;
    animation-duration: 0.8s;
}

/* Swiper Pagination Customization */
.swiper-pagination-bullet {
    background: white;
    opacity: 0.5;
    width: 10px;
    height: 10px;
}
.swiper-pagination-bullet-active {
    opacity: 1;
    background: #2563eb; /* Blue-600 */
    width: 24px;
    border-radius: 5px;
    transition: width 0.3s;
}
</style>
