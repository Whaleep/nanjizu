<script setup>
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

// 引入所有 Block 元件
import blockComponents from '@/Components/Blocks';

defineProps({
    page: Object,
});

const components = blockComponents;

</script>

<template>
    <Head title="首頁" />
    <MainLayout>
        <!-- 優先使用資料庫中的 Page Builder 內容 -->
        <template v-if="page && page.content && page.content.length > 0">
            <div v-for="(block, index) in page.content" :key="index">
                <component 
                    :is="components[block.type]" 
                    v-if="components[block.type]" 
                    :data="block.data"
                />
            </div>
        </template>

        <!-- 如果後台還沒設定內容，則顯示預設的範例區塊 -->
        <template v-else>
            <!-- Hero Carousel -->
            <component :is="components.hero_carousel" :data="{ 
                slides: [
                    {
                        image: '/images/hero-bg.jpg',
                        heading: '您的手機急診室',
                        subheading: 'iPhone / Android / MacBook / iPad 專業快速維修',
                        button_text: '查詢維修價格',
                        button_url: '/repair'
                    },
                    {
                        image: '/images/hero-shop.png',
                        heading: '精選配件 限時優惠',
                        subheading: '保護貼、手機殼、充電線，通通都有',
                        button_text: '前往商店',
                        button_url: '/shop'
                    }
                ],
                autoplay_delay: 5000,
                height: 'h-[500px] md:h-[600px]'
            }" />

            <!-- 快速入口 (Icon Links) -->
            <component :is="components.icon_links" :data="{
                columns: 4,
                items: [
                    { label: '手機維修', url: '/repair', icon: '🛠', color: 'blue' },
                    { label: '線上商店', url: '/shop', icon: '🛒', color: 'green' },
                    { label: '送修流程', url: '/process', icon: '📦', color: 'purple' },
                    { label: '門市據點', url: '/stores', icon: '📍', color: 'orange' },
                ]
            }" />
            
            <!-- Feature Wall (Bento Grid) -->
            <component :is="components.feature_wall" :data="{
                heading: '探索南極組',
                subheading: '除了維修，我們還為您準備了更多',
                items: [
                    { 
                        image: '/images/repair-process.png', 
                        title: '專業維修中心', 
                        description: '透明化的維修流程，原廠級設備與技術',
                        url: '/repair',
                        cols: 2,
                        rows: 2
                    },
                    { 
                        image: '/images/hero-shop.png', 
                        title: '嚴選配件', 
                        description: '保護您的愛機，展現個人風格',
                        url: '/shop',
                        cols: 1,
                        rows: 1
                    },
                    { 
                        image: '/images/hero-bg.jpg', 
                        title: '二手良品', 
                        description: '經過嚴格檢測的二手機，高CP值的選擇',
                        url: '/shop',
                        cols: 1,
                        rows: 1
                    }
                ]
            }" />

            <!-- 最新消息 -->
            <component :is="components.post_grid" :data="{ type: 'news', limit: 3, heading: '最新消息', bg_color: 'gray' }" />
        </template>
    </MainLayout>
</template>
