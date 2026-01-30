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

// 確保 menuItems 是從 middleware 傳來的
const menuItems = page.props.menuItems || [];
// === 修改：使用 Map 來記錄每個選單項目的展開狀態 ===
const expandedStates = ref({});
const toggleExpand = (id) => {
    // 切換 true/false
    expandedStates.value[id] = !expandedStates.value[id];
};

</script>

<template>
    <MainLayout>
        <!-- === 第二層導航 (商店選單) - 讀取後端 menuItems === -->
        <div class="bg-white border-b hidden lg:block shadow-sm top-16 z-40">
            <div class="container mx-auto px-4">
                <ul class="flex space-x-8 text-sm font-medium">
                    <li v-for="item in $page.props.menuItems" :key="item.id" class="py-3 relative group">
                        
                        <!-- 主連結 -->
                        <Link :href="item.link" 
                              class="transition flex items-center gap-1"
                              :class="item.is_promotion ? 'text-red-600 hover:text-red-700 font-bold' : 'hover:text-blue-600 text-gray-700'">
                            <span v-if="item.is_promotion">🔥</span>
                            {{ item.name }}
                        </Link>

                        <!-- 下拉選單 (Desktop Dropdown - 針對有子分類的項目) -->
                        <div v-if="item.children && item.children.length > 0" 
                             class="absolute left-0 top-full mt-0 w-48 bg-white border border-gray-100 shadow-lg rounded-lg py-2 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50 transform origin-top-left">
                            <Link v-for="child in item.children" :key="child.id"
                                  :href="child.link"
                                  class="block px-4 py-2 text-gray-600 hover:bg-gray-50 hover:text-blue-600">
                                {{ child.name }}
                            </Link>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <!-- 手機版：開啟側邊欄按鈕 -->
        <div class="lg:hidden bg-white border-b p-3 flex items-center justify-between sticky top-16 z-30 shadow-sm">
            <button @click="mobileSidebarOpen = true" class="flex items-center gap-2 text-gray-700 font-bold bg-gray-100 px-4 py-2 rounded-lg">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7"></path></svg>
                <span>商品分類</span>
            </button>

            <div class="text-sm text-gray-500">瀏覽商店</div>
        </div>

        <div class="container mx-auto px-4 py-6 flex gap-8 items-start">

            <!-- === 左側邊欄 (Desktop) === -->
            <aside class="hidden lg:block w-64 flex-shrink-0 bg-white rounded-lg shadow-sm border p-4 sticky top-24">
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
            <div v-show="mobileSidebarOpen" class="fixed inset-0 bg-black bg-opacity-50 z-50 lg:hidden"
                 @click="mobileSidebarOpen = false" x-cloak></div>

            <!-- 抽屜本體 -->
            <div class="fixed inset-y-0 left-0 w-4/5 max-w-xs bg-white shadow-xl z-50 lg:hidden overflow-y-auto transform transition-transform duration-300 ease-in-out"
                 :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full'">

                <div class="p-4 border-b flex justify-between items-center bg-gray-50">
                    <h2 class="font-bold text-lg text-gray-800">商店選單</h2>
                    <button @click="mobileSidebarOpen = false" class="text-gray-500 hover:text-gray-800 p-2">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <ul class="p-4 space-y-2">
                    <li v-for="item in menuItems" :key="item.id" class="border-b border-gray-100 last:border-0 pb-2">

                        <div class="flex justify-between items-center">
                            <!-- 連結 -->
                            <Link :href="item.link" 
                                  class="flex-grow py-2 text-base flex items-center gap-2"
                                  :class="item.is_promotion ? 'text-red-600 font-bold' : 'text-gray-800 font-bold'"
                                  @click="mobileSidebarOpen = false">
                                <span v-if="item.is_promotion">🔥</span>
                                {{ item.name }}
                            </Link>

                            <!-- 展開按鈕 (只有當 children 存在且不為空時顯示) -->
                            <button v-if="item.children && item.children.length > 0"
                                    @click.stop="toggleExpand(item.id)"
                                    class="p-3 text-gray-500 active:bg-gray-100 rounded-full transition-transform"
                                    :class="expandedStates[item.id] ? 'rotate-180 bg-gray-50' : ''">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>
                        </div>

                        <!-- 子選單 -->
                        <ul v-if="item.children && item.children.length > 0"
                            v-show="expandedStates[item.id]"
                            class="pl-4 mt-1 space-y-1 bg-gray-50 rounded-lg p-2 transition-all">
                            
                            <!-- 直接遍歷 children -->
                            <li v-for="child in item.children" :key="child.id">
                                <Link :href="child.link"
                                      class="block text-gray-600 text-sm py-2 px-2 rounded hover:bg-white hover:text-blue-600"
                                      @click="mobileSidebarOpen = false">
                                    {{ child.name }}
                                </Link>
                            </li>
                        </ul>
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
