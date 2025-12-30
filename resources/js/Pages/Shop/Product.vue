<script setup>
import { ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import axios from 'axios'; // 用 axios 做 AJAX 加入購物車

const props = defineProps({
    product: Object,
});

// 狀態管理
const selectedVariant = ref(props.product.variants[0] || {});
const quantity = ref(1);
const isLoading = ref(false);

// 計算屬性
const priceRange = computed(() => {
    const prices = props.product.variants.map(v => v.price);
    const min = Math.min(...prices);
    const max = Math.max(...prices);
    return min === max ? `NT$ ${min}` : `NT$ ${min} ~ ${max}`;
});

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

// 加入購物車 (使用 Axios 避免頁面重整)
const addToCart = async () => {
    if (selectedVariant.value.stock <= 0) return;

    isLoading.value = true;
    try {
        const response = await axios.post('/v1/cart/add', { // 注意：這裡是打 V1 的 API，因為邏輯通用
            variant_id: selectedVariant.value.id,
            quantity: quantity.value
        });

        // 手動觸發 Inertia 重新載入頁面資料 (為了更新 Navbar 購物車紅點)
        // 這裡用 { only: ['cartCount'] } 會更高效，但需要後端配合 partial reload
        // 簡單作法：直接 reload
        window.location.reload();
        // 或使用 Inertia.reload({ only: ['cartCount'] }) 如果我們有在 HandleInertiaRequests 設定 lazy loading

        alert('已加入購物車！');
    } catch (error) {
        alert('加入失敗');
    } finally {
        isLoading.value = false;
    }
};
</script>

<template>
    <Head :title="product.name" />

    <ShopLayout>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-10">
            <!-- 圖片 -->
            <div class="bg-gray-100 rounded-2xl overflow-hidden border aspect-square flex items-center justify-center">
                <img v-if="product.image" :src="`/storage/${product.image}`" class="w-full h-full object-cover">
                <span v-else class="text-gray-400">No Image</span>
            </div>

            <!-- 資訊 -->
            <div>
                <nav class="text-sm text-gray-500 mb-4">
                    <Link href="/shop" class="hover:underline">商店</Link> /
                    <Link :href="`/shop/category/${product.category.slug}`" class="hover:underline">{{ product.category.name }}</Link>
                </nav>

                <h1 class="text-3xl font-bold mb-2">{{ product.name }}</h1>
                <p class="text-gray-500 text-sm mb-4">全系列價格：{{ priceRange }}</p>

                <div class="text-3xl text-red-600 font-bold mb-6">
                    NT$ {{ formatPrice(selectedVariant.price) }}
                </div>

                <!-- 規格按鈕 -->
                <div class="mb-8">
                    <h3 class="text-sm font-bold text-gray-700 mb-3">規格</h3>
                    <div class="flex flex-wrap gap-3">
                        <button v-for="variant in product.variants" :key="variant.id"
                                @click="selectedVariant = variant"
                                class="px-4 py-2 border rounded-lg font-medium transition"
                                :class="selectedVariant.id === variant.id ? 'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600' : 'hover:border-gray-300 text-gray-700'"
                                :disabled="variant.stock <= 0">
                            {{ variant.name }}
                            <span v-if="variant.stock <= 0" class="text-xs text-red-500 ml-1">(缺貨)</span>
                        </button>
                    </div>
                </div>

                <!-- 購買區塊 -->
                <div class="flex gap-4">
                    <input type="number" v-model="quantity" min="1" :max="selectedVariant.stock" class="w-32 border rounded-lg px-4 py-3 text-center font-bold">
                    <button @click="addToCart"
                            :disabled="selectedVariant.stock <= 0 || isLoading"
                            class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition">
                        {{ isLoading ? '處理中...' : (selectedVariant.stock > 0 ? '加入購物車' : '暫無庫存') }}
                    </button>
                </div>

                <div class="mt-10 border-t pt-8 prose text-gray-600" v-html="product.description"></div>
            </div>
        </div>
    </ShopLayout>
</template>
