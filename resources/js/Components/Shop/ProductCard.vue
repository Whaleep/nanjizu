<!-- 商品卡片元件 -->
<script setup>
import { ref, computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    product: Object
});

// 1. 初始化狀態
// 預設選第一個有庫存的規格，如果都沒有就選第一個
const defaultVariant = props.product.variants.find(v => v.stock > 0) || props.product.variants[0] || {};
const selectedVariant = ref(defaultVariant);
const quantity = ref(1);
const isLoading = ref(false);

// 動態圖片邏輯
const displayImage = computed(() => {
    // 優先權 1: 選中的規格有圖片
    if (selectedVariant.value && selectedVariant.value.image) {
        return `/storage/${selectedVariant.value.image}`;
    }
    // 優先權 2: 商品本身的主圖 (後端 Accessor 處理過的 primary_image)
    if (props.product.primary_image) {
        return `/storage/${props.product.primary_image}`;
    }
    // 優先權 3: 無圖
    return null;
});

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

// 2. 加入購物車邏輯
const addToCart = async () => {
    if (!selectedVariant.value.id || selectedVariant.value.stock <= 0) return;

    isLoading.value = true;
    try {
        const response = await axios.post('/cart/add', {
            variant_id: selectedVariant.value.id,
            quantity: quantity.value
        });

        // 通知 Navbar 更新紅點
        window.dispatchEvent(new CustomEvent('cart-updated', {
            detail: { count: response.data.cartCount }
        }));

        // 簡單的成功視覺回饋 (按鈕變綠一下)
        const btnText = document.getElementById(`btn-text-${props.product.id}`);
        if(btnText) {
            const originalText = btnText.innerText;
            btnText.innerText = '已加入';
            setTimeout(() => btnText.innerText = originalText, 1000);
        }

    } catch (error) {
        // 失敗邏輯: 抓取後端回傳的 message
        const msg = error.response?.data?.message || '加入失敗';
        alert(msg); // 彈出「庫存不足...」
    } finally {
        isLoading.value = false;
        // quantity.value = 1; // 建議：失敗時不要重置數量，讓使用者知道他剛剛填了多少
    }
};
</script>

<template>
    <div class="bg-white border rounded-xl overflow-hidden hover:shadow-lg transition group flex flex-col h-full">

        <!-- 圖片區 (點擊進詳情) -->
        <Link :href="`/shop/product/${product.slug}`" class="block aspect-square bg-gray-100 overflow-hidden relative">

            <!-- 使用 computed 的 displayImage -->
            <img v-if="displayImage"
                 :src="displayImage"
                 :alt="product.name"
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500">

            <div v-else class="flex items-center justify-center w-full h-full text-gray-400">無圖</div>

            <!-- 缺貨遮罩 -->
            <div v-if="selectedVariant.stock <= 0" class="absolute inset-0 bg-black/50 flex items-center justify-center text-white font-bold tracking-wider">
                補貨中
            </div>
        </Link>

        <!-- 資訊與操作區 -->
        <div class="p-4 flex flex-col flex-grow">
            <!-- 類別與標題 -->
            <div class="mb-3">
                <div class="text-xs text-gray-500 mb-1" v-if="product.category">
                    {{ product.category.name }}
                </div>
                <h3 class="font-bold text-base text-gray-800 leading-tight line-clamp-2 h-10 group-hover:text-blue-600 transition">
                    <Link :href="`/shop/product/${product.slug}`">{{ product.name }}</Link>
                </h3>
            </div>

            <div class="mt-auto space-y-3">

                <!-- A. 規格選擇 (下拉選單) -->
                <div v-if="product.variants.length > 1">
                    <select v-model="selectedVariant"
                            class="w-full text-sm border rounded-lg border-gray-200 focus:border-blue-500 focus:ring-1 focus:ring-blue-500 py-1 px-2">
                        <template v-for="variant in product.variants" :key="variant.id">
                            <option :value="variant" :disabled="variant.stock <= 0">
                                {{ variant.name }} {{ variant.stock <= 0 ? '(缺貨)' : '' }}
                            </option>
                        </template>
                    </select>
                </div>
                <!-- 單一規格顯示名稱 -->
                <div v-else class="text-sm text-gray-500 truncate">
                    規格：{{ selectedVariant.name || '單一規格' }}
                </div>

                <!-- B. 價格顯示 (單一價格) -->
                <div class="text-red-600 font-bold text-lg">
                    NT$ {{ formatPrice(selectedVariant.price || 0) }}
                </div>

                <!-- C. 數量與購買按鈕 -->
                <div class="flex gap-2">
                    <!-- 數量輸入 -->
                    <input type="number" v-model="quantity" min="1" :max="selectedVariant.stock"
                           class="w-16 border border-gray-200 rounded-lg text-center text-sm py-2 px-1 focus:ring-blue-500 focus:border-blue-500"
                           :disabled="selectedVariant.stock <= 0">

                    <!-- 加入按鈕 -->
                    <button @click="addToCart"
                            :disabled="selectedVariant.stock <= 0 || isLoading"
                            class="flex-1 bg-blue-600 text-white rounded-lg text-sm font-bold hover:bg-blue-700 transition disabled:bg-gray-300 disabled:cursor-not-allowed flex items-center justify-center gap-1">
                        <svg v-if="isLoading" class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span :id="`btn-text-${product.id}`">加入購物車</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
