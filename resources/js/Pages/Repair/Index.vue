<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

defineProps({
    brands: Array
});

// 管理每個品牌的 Active Tab (用 Brand ID 當 Key)
// 預設全為 'all'
const activeTabs = ref({});

const setTab = (brandId, tab) => {
    activeTabs.value[brandId] = tab;
};

// 輔助函式：取得當前品牌的 Tab (預設 'all')
const getTab = (brandId) => activeTabs.value[brandId] || 'all';
</script>

<template>
    <Head title="維修報價查詢" />
    <MainLayout>
        <div class="container mx-auto px-4 py-12">
            <h1 class="text-3xl font-bold text-center mb-10">選擇您的裝置</h1>

            <div v-for="brand in brands" :key="brand.id" class="mb-16 bg-white p-6 rounded-xl shadow-sm border">

                <!-- 品牌標題 -->
                <div class="flex items-center mb-6 border-b pb-4">
                    <img v-if="brand.logo" :src="`/storage/${brand.logo}`" class="h-12 w-12 object-contain mr-4">
                    <h2 class="text-3xl font-bold text-gray-800">{{ brand.name }}</h2>
                </div>

                <!-- 系列 Tabs -->
                <div v-if="brand.device_categories.length > 0" class="flex flex-wrap gap-2 mb-6">
                    <button @click="setTab(brand.id, 'all')"
                            class="px-5 py-2 rounded-full font-bold transition text-sm md:text-base"
                            :class="getTab(brand.id) === 'all' ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                        全部
                    </button>
                    <button v-for="cat in brand.device_categories" :key="cat.id"
                            @click="setTab(brand.id, cat.id)"
                            class="px-5 py-2 rounded-full font-bold transition text-sm md:text-base"
                            :class="getTab(brand.id) === cat.id ? 'bg-blue-600 text-white shadow-md' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'">
                        {{ cat.name }}
                    </button>
                </div>

                <!-- 型號列表 -->
                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
                    <!-- 使用 v-show 篩選 -->
                    <template v-for="model in brand.device_models" :key="model.id">
                        <Link :href="`/repair/${model.id}`"
                              v-show="getTab(brand.id) === 'all' || getTab(brand.id) === model.device_category_id"
                              class="block bg-white border border-gray-200 hover:border-blue-500 hover:shadow-lg transition p-4 rounded-lg text-center group">

                            <!-- 顯示系列名稱 (如果有) - 這裡需要 Model 有關聯 deviceCategory -->
                            <!-- 若前端拿到的 JSON 沒有包含 category name，可以考慮後端 with('deviceCategory') -->
                            <span class="font-medium text-lg group-hover:text-blue-600">{{ model.name }}</span>
                        </Link>
                    </template>
                </div>

                <div v-if="brand.device_models.length === 0" class="text-center text-gray-500 py-4">
                    此品牌尚無型號。
                </div>
            </div>
        </div>
    </MainLayout>
</template>
