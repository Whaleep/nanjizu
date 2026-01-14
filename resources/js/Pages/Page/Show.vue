<script setup>
import { Head } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

// 引入所有區塊元件
import blockComponents from '@/Components/Blocks';

const props = defineProps({
    page: Object
});

// 定義區塊對應表 (Map)
const components = blockComponents;
</script>

<template>
    <Head :title="page.seo_title || page.title">
        <meta name="description" :content="page.seo_description" />
    </Head>

    <MainLayout>
        <!-- 遍歷 content 陣列 -->
        <div v-for="(block, index) in page.content" :key="index">

            <!-- 動態渲染元件 -->
            <!-- :is="components[block.type]" 會根據 type 找到對應的 Vue Component -->
            <component
                :is="components[block.type]"
                v-if="components[block.type]"
                :data="block.data"
            />

        </div>
    </MainLayout>
</template>
