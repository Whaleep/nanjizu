<script setup>
import { Link } from '@inertiajs/vue3';

const props = defineProps({
    data: {
        type: Object,
        default: () => ({
            items: [], // Array of { label, url, icon, color_theme }
            columns: 4, // 2, 3, 4
        })
    }
});

// Tailwind CSS classes map for color themes
const colorMap = {
    blue: {
        bg: 'bg-blue-100',
        text: 'text-blue-600',
        border_hover: 'hover:border-blue-500',
        group_hover_bg: 'group-hover:bg-blue-600',
    },
    green: {
        bg: 'bg-green-100',
        text: 'text-green-600',
        border_hover: 'hover:border-green-500',
        group_hover_bg: 'group-hover:bg-green-600',
    },
    purple: {
        bg: 'bg-purple-100',
        text: 'text-purple-600',
        border_hover: 'hover:border-purple-500',
        group_hover_bg: 'group-hover:bg-purple-600',
    },
    orange: {
        bg: 'bg-orange-100',
        text: 'text-orange-600',
        border_hover: 'hover:border-orange-500',
        group_hover_bg: 'group-hover:bg-orange-600',
    },
    red: {
        bg: 'bg-red-100',
        text: 'text-red-600',
        border_hover: 'hover:border-red-500',
        group_hover_bg: 'group-hover:bg-red-600',
    },
    gray: {
        bg: 'bg-gray-100',
        text: 'text-gray-600',
        border_hover: 'hover:border-gray-500',
        group_hover_bg: 'group-hover:bg-gray-600',
    }
};

const getTheme = (color) => colorMap[color] || colorMap.blue;

const gridCols = {
    2: 'grid-cols-2',
    3: 'grid-cols-2 md:grid-cols-3',
    4: 'grid-cols-2 md:grid-cols-4',
};
</script>

<template>
    <div class="container mx-auto px-4 py-16">
        <div class="grid gap-6 text-center" :class="gridCols[data.columns || 4]">
            <Link v-for="(item, index) in data.items" :key="index" 
                  :href="item.url" 
                  class="p-6 bg-white rounded-xl shadow hover:shadow-lg transition group border border-transparent"
                  :class="getTheme(item.color).border_hover">
                
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4 transition text-3xl"
                     :class="[
                        getTheme(item.color).bg, 
                        getTheme(item.color).text,
                        getTheme(item.color).group_hover_bg,
                        'group-hover:text-white'
                     ]">
                    <!-- Check if icon is an emoji (simple string) or if we need support for SVG/Images later -->
                    <!-- For now assuming Emoji/Text as per requirement -->
                    {{ item.icon }}
                </div>
                
                <h3 class="font-bold text-lg text-gray-800 group-hover:text-black transition">
                    {{ item.label }}
                </h3>
            </Link>
        </div>
    </div>
</template>
