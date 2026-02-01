<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const props = defineProps({ data: Object });

const stores = ref([]);
const isLoading = ref(true);

onMounted(async () => {
    try {
        const response = await axios.get('/api/stores/block', {
            params: { limit: props.data.limit }
        });
        stores.value = response.data;
    } catch (e) {
        console.error('無法載入服務據點', e);
    } finally {
        isLoading.value = false;
    }
});
</script>

<template>
    <div :class="data.bg_color === 'gray' ? 'bg-gray-100' : 'bg-white'" class="py-16">
        <div class="container mx-auto px-4">
            
            <h2 class="text-3xl font-bold text-center mb-10">{{ data.heading }}</h2>
            
            <div v-if="isLoading" class="text-center text-gray-500 py-10">
                地圖資訊載入中...
            </div>

            <!-- 改為單欄列表，因為橫向卡片較寬 -->
            <div v-else-if="stores.length > 0" class="flex flex-col gap-8">
                <div v-for="store in stores" :key="store.id"  class="@container"> 
                    <div class="bg-white rounded-lg shadow-md border overflow-hidden hover:border-blue-400 transition flex flex-col @3xl:flex-row">
                        
                        <!-- 左側：文字資訊區 (桌機佔 1/2 或 5/12) -->
                        <!-- 使用 flex-col justify-between 確保內容撐開且按鈕在底部 -->
                        <div class="w-full @3xl:w-5/12 p-6 flex flex-col">
                            <div>
                                <h3 class="text-2xl font-bold mb-4 text-blue-800 flex items-center gap-2">
                                    <span>📍</span> {{ store.name }}
                                </h3>

                                <div class="space-y-3 pl-2 border-l-2 border-gray-200 ml-2 mb-6 text-base text-gray-700">
                                    <p class="flex items-start">
                                        <span class="font-bold w-20 shrink-0 text-gray-500">地址：</span>
                                        <span>{{ store.address }}</span>
                                    </p>
                                    <p class="flex items-start">
                                        <span class="font-bold w-20 shrink-0 text-gray-500">電話：</span>
                                        <a :href="`tel:${store.phone}`" class="text-blue-600 hover:underline font-bold">{{ store.phone }}</a>
                                    </p>
                                    <p class="flex items-start">
                                        <span class="font-bold w-20 shrink-0 text-gray-500">營業時間：</span>
                                        <span>{{ store.opening_hours }}</span>
                                    </p>
                                </div>
                            </div>

                            <!-- 按鈕區：放在文字區塊的底部 -->
                            <!-- mt-auto 確保如果文字內容較少，按鈕也會被推到區塊最下方對齊 -->
                            <div class="flex gap-4 mt-auto pt-4 border-t border-gray-100">
                                <a :href="`https://www.google.com/maps/dir//${store.address}`" target="_blank"
                                class="flex-1 bg-blue-600 text-white text-center py-2 rounded hover:bg-blue-700 font-bold transition text-sm flex items-center justify-center gap-1">
                                <!-- 稍微加個小 icon 增加質感 -->
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                導航前往
                                </a>
                                <a :href="`tel:${store.phone}`"
                                class="flex-1 border border-blue-600 text-blue-600 text-center py-2 rounded hover:bg-blue-50 font-bold transition text-sm flex items-center justify-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                撥打電話
                                </a>
                            </div>
                        </div>

                        <!-- 右側：地圖區 (桌機佔 1/2 或 7/12) -->
                        <!-- 手機版 h-64 固定高度，桌機版 h-auto 自動填滿高度 -->
                        <div v-if="store.map_url" class="w-full h-64 @3x1:w-7/12 @3x1:h-auto relative bg-gray-200">
                            <iframe :src="store.map_url" 
                                    class="absolute inset-0 w-full h-full" 
                                    style="border:0;" 
                                    allowfullscreen="" 
                                    loading="lazy">
                            </iframe>
                        </div>
                    </div>
                </div>
            </div>

            <div v-else class="text-center text-gray-500">
                目前沒有服務據點資訊。
            </div>

        </div>
    </div>
</template>
