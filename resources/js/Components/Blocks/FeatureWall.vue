<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            heading: '',
            subheading: '',
            items: [], // Array of { image, title, description, url, span: 'col-span-1' | 'col-span-2' }
        })
    }
});

const getImageUrl = (img) => {
    if (!img) return '';
    if (img.startsWith('http') || img.startsWith('/')) return img;
    return `/storage/${img}`;
};
</script>

<template>
    <div class="container mx-auto px-4 py-16">
        <div class="text-center mb-12" v-if="data.heading">
            <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ data.heading }}</h2>
            <p v-if="data.subheading" class="text-gray-500 max-w-2xl mx-auto">{{ data.subheading }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 auto-rows-[300px]">
            <Link v-for="(item, index) in data.items" :key="index"
                  :href="item.url || '#'"
                  class="relative group rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-500 block"
                  :class="[
                      item.cols ? `md:col-span-${item.cols}` : 'md:col-span-1',
                      item.rows ? `md:row-span-${item.rows}` : 'md:row-span-1',
                  ]">
                
                <!-- Background Image -->
                <div class="absolute inset-0 bg-cover bg-center transition-transform duration-700 group-hover:scale-110"
                     :style="`background-image: url('${getImageUrl(item.image)}')`">
                </div>

                <!-- Gradient Overlay -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent opacity-80 group-hover:opacity-90 transition"></div>

                <!-- Content -->
                <div class="absolute bottom-0 left-0 w-full p-8 translate-y-2 group-hover:translate-y-0 transition-transform duration-300">
                    <h3 class="text-white text-2xl font-bold mb-2">{{ item.title }}</h3>
                    <p class="text-gray-200 text-sm opacity-0 group-hover:opacity-100 transition-opacity duration-300 delay-100">
                        {{ item.description }}
                    </p>
                </div>
            </Link>
        </div>
    </div>
</template>
