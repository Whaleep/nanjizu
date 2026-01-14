<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            image: '',
            heading: '',
            subheading: '',
            button_text: '',
            button_url: '',
            align: 'center', // 'left' | 'center' | 'right'
            height: 'h-[400px] md:h-[500px]',
            overlay_opacity: 50, // 0-100
        })
    }
});

const getImageUrl = (img) => {
    if (!img) return '';
    if (img.startsWith('http') || img.startsWith('/')) return img;
    return `/storage/${img}`;
};

const alignClass = {
    'left': 'items-start text-left',
    'center': 'items-center text-center',
    'right': 'items-end text-right',
};
</script>

<template>
    <div class="relative w-full bg-cover bg-center bg-no-repeat overflow-hidden group"
         :class="data.height || 'h-[400px] md:h-[500px]'"
         :style="`background-image: url('${getImageUrl(data.image)}')`">
        
        <!-- Overlay -->
        <div class="absolute inset-0 bg-black transition-opacity duration-500"
             :style="`opacity: ${(data.overlay_opacity !== undefined ? data.overlay_opacity : 50) / 100}`">
        </div>

        <!-- Content -->
        <div class="container mx-auto px-6 h-full relative z-10 flex flex-col justify-center"
             :class="alignClass[data.align || 'center']">
            
            <h2 v-if="data.heading" 
                class="text-4xl md:text-5xl lg:text-6xl font-bold mb-6 text-white tracking-tight drop-shadow-xl transform transition duration-700 hover:scale-[1.01]">
                {{ data.heading }}
            </h2>
            
            <p v-if="data.subheading" 
               class="text-lg md:text-xl text-gray-200 mb-8 max-w-2xl leading-relaxed drop-shadow-md">
                {{ data.subheading }}
            </p>
            
            <Link v-if="data.button_text && data.button_url" 
                  :href="data.button_url"
                  class="bg-blue-600 border border-transparent text-white px-8 py-3 rounded-lg font-bold text-lg hover:bg-blue-700 transition-all shadow-lg hover:shadow-blue-500/30 transform hover:-translate-y-1">
                {{ data.button_text }}
            </Link>
        </div>
    </div>
</template>
