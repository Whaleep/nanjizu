<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import MainLayout from '@/Layouts/MainLayout.vue';

const page = usePage();
const categories = page.props.shopCategories; // 從 Middleware 拿到的資料

// 手機版側邊欄開關
const mobileSidebarOpen = ref(false);

// 控制分類展開/收合的狀態 (用 ID 當 Key)
const expandedCategories = ref({});

const toggleCategory = (id) => {
    expandedCategories.value[id] = !expandedCategories.value[id];
};
</script>

<template>
    <MainLayout>
        <!-- === 第二層導航 (商店選單) - 讀取後端 menuItems === -->
        <div class="bg-white border-b hidden md:block shadow-sm z-40">
            <div class="container mx-auto px-4">
                <ul class="flex space-x-8 text-sm font-medium">
                    <li v-for="item in $page.props.menuItems" :key="item.id" class="py-3">
                        <Link :href="item.link" class="hover:text-blue-600 transition">
                            {{ item.name }}
                        </Link>
                    </li>
                </ul>
            </div>
        </div>

        <!-- 手機版：開啟側邊欄按鈕 -->
        <div class="md:hidden bg-white border-b p-3 sticky top-16 z-30 shadow-sm">
            <button @click="mobileSidebarOpen = true" class="flex items-center gap-2 text-gray-700 font-bold bg-gray-100 px-4 py-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                <span>商品分類</span>
            </button>
        </div>

        <div class="container mx-auto px-4 py-6 flex gap-8 items-start">

            <!-- === 左側邊欄 (Desktop) === -->
            <aside class="hidden md:block w-64 flex-shrink-0 bg-white rounded-lg shadow-sm border p-4 sticky top-24">
                <h3 class="font-bold text-lg mb-4 border-b pb-2 text-gray-800">商品分類</h3>
                <ul class="space-y-1">
                    <li v-for="cat in categories" :key="cat.id">
                        <div class="flex justify-between items-center cursor-pointer hover:bg-gray-50 p-2 rounded transition"
                             @click="toggleCategory(cat.id)">

                            <!-- 分類連結 -->
                            <Link :href="`/shop/category/${cat.slug}`" class="font-medium text-gray-700 hover:text-blue-600 flex-grow">
                                {{ cat.name }}
                            </Link>

                            <!-- 展開箭頭 -->
                            <button v-if="cat.children && cat.children.length > 0" class="p-1 text-gray-400 hover:text-gray-600">
                                <svg :class="expandedCategories[cat.id] ? 'rotate-180' : ''" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>

                        <!-- 子分類列表 -->
                        <ul v-if="cat.children && cat.children.length > 0"
                            v-show="expandedCategories[cat.id]"
                            class="pl-4 mt-1 space-y-1 text-sm border-l-2 border-gray-100 ml-2">
                            <li v-for="child in cat.children" :key="child.id">
                                <Link :href="`/shop/category/${child.slug}`" class="block py-1.5 px-2 text-gray-600 hover:text-blue-600 rounded hover:bg-gray-50">
                                    {{ child.name }}
                                </Link>
                            </li>
                        </ul>
                    </li>
                </ul>
            </aside>

            <!-- === 手機版抽屜 (Mobile Drawer) === -->
            <!-- 遮罩 -->
            <div v-show="mobileSidebarOpen" class="fixed inset-0 bg-black bg-opacity-50 z-50 md:hidden"
                 @click="mobileSidebarOpen = false"></div>

            <!-- 內容 -->
            <div v-show="mobileSidebarOpen" class="fixed inset-y-0 left-0 w-4/5 max-w-xs bg-white shadow-xl z-50 md:hidden overflow-y-auto p-4 space-y-4">
                <div class="flex justify-between items-center border-b pb-2">
                    <h2 class="font-bold text-lg">全部分類</h2>
                    <button @click="mobileSidebarOpen = false">✕</button>
                </div>
                <ul class="space-y-4">
                    <li v-for="cat in categories" :key="cat.id">
                        <div class="flex justify-between items-center text-base" @click="toggleCategory(cat.id)">
                            <span class="font-bold text-gray-800">{{ cat.name }}</span>
                            <span v-if="cat.children && cat.children.length > 0">▼</span>
                        </div>
                        <ul v-if="cat.children && cat.children.length > 0" v-show="expandedCategories[cat.id]" class="pl-4 mt-2 space-y-2 border-l-2 border-gray-200 ml-2">
                            <li><Link :href="`/shop/category/${cat.slug}`" class="text-blue-600 text-sm">查看全部 {{ cat.name }}</Link></li>
                            <li v-for="child in cat.children" :key="child.id">
                                <Link :href="`/shop/category/${child.slug}`" class="text-gray-600">{{ child.name }}</Link>
                            </li>
                        </ul>
                        <div v-else>
                            <Link :href="`/shop/category/${cat.slug}`" class="absolute inset-0"></Link>
                        </div>
                    </li>
                </ul>
            </div>

            <!-- === 右側內容區 === -->
            <main class="flex-grow w-full min-w-0">
                <slot />
            </main>

        </div>
    </MainLayout>
</template>
