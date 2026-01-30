<!-- 小型商品卡片元件 (用於相關商品、加購區) -->
<script setup>
import { ref, computed } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({
    product: Object,
    showAction: {
        type: Boolean,
        default: true
    }
});

// 初始化狀態
// 預設選第一個有庫存的規格，如果都沒有就選第一個
const defaultVariant = props.product.variants.find(v => v.stock > 0) || props.product.variants[0] || {};
const selectedVariant = ref(defaultVariant);
const isLoading = ref(false);

// 使用 ref 並從 props 初始化收藏狀態
const isWishlisted = ref(props.product.is_wishlisted || false);

// 格式化圖片路徑
const formatImage = (path) => {
    if (!path) return null;
    if (path.startsWith('http') || path.startsWith('/storage/') || path.startsWith('data:')) return path;
    return `/storage/${path}`;
};

// 動態圖片邏輯
const displayImage = computed(() => {
    let rawPath = null;

    // 優先權 1: 使用後端計算好的智慧圖片 (包含規格圖、選項圖、主圖回退)
    if (selectedVariant.value && selectedVariant.value.smart_image) {
        rawPath = selectedVariant.value.smart_image;
    } else if (selectedVariant.value && selectedVariant.value.variant_image_url) {
        rawPath = selectedVariant.value.variant_image_url;
    } else if (selectedVariant.value && selectedVariant.value.image) {
        rawPath = selectedVariant.value.image;
    }
 else if (props.product.primary_image) {
        rawPath = props.product.primary_image;
    }

    return formatImage(rawPath);
});

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

// 加入購物車邏輯 (小卡片直接加入 1 個)
const addToCart = async () => {
    if (!props.product.is_sellable) {
        alert('贈品會在符合條件時自動加入購物車。');
        return;
    }

    if (!selectedVariant.value.id || selectedVariant.value.stock <= 0) return;

    isLoading.value = true;
    try {
        const response = await axios.post('/cart/add', {
            variant_id: selectedVariant.value.id,
            quantity: 1
        });

        // 通知 Navbar 更新紅點
        window.dispatchEvent(new CustomEvent('cart-updated', {
            detail: { count: response.data.cartCount }
        }));

        // 顯示詳細回饋
        window.dispatchEvent(new CustomEvent('show-cart-feedback', {
            detail: {
                product_name: props.product.name,
                variant_name: selectedVariant.value.name,
                quantity: 1,
                image: selectedVariant.value.smart_image || displayImage.value,
                price: selectedVariant.value.display_price || selectedVariant.value.price
            }
        }));

    } catch (error) {
        const msg = error.response?.data?.message || '加入失敗';
        alert(msg);
    } finally {
        isLoading.value = false;
    }
};

// 切換收藏
const toggleWishlist = async () => {
    // ... existing toggleWishlist code (simplified for target search)
};

</script>

<template>
    <div class="bg-white border rounded-xl overflow-hidden hover:shadow-md transition group flex flex-col h-full relative">
        
        <!-- 加入收藏按鈕 (絕對定位) -->
        <button @click.stop.prevent="toggleWishlist" 
                class="absolute top-2 right-2 z-10 w-8 h-8 flex items-center justify-center rounded-full bg-white/60 hover:bg-white text-gray-400 backdrop-blur-sm shadow-sm transition-all duration-300 group/wishlist"
                :class="{'text-red-500 bg-white': isWishlisted}">
            <svg xmlns="http://www.w3.org/2000/svg" 
                 :class="[isWishlisted ? 'fill-current' : 'fill-none stroke-current']"
                 class="w-4 h-4 transition-transform duration-300 group-hover/wishlist:scale-110" 
                 viewBox="0 0 24 24" 
                 stroke-width="2" 
                 stroke-linecap="round" 
                 stroke-linejoin="round">
                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
            </svg>
        </button>

        <!-- 圖片區 -->
        <Link :href="`/shop/product/${product.slug}`" class="block aspect-square bg-gray-50 overflow-hidden relative">
            <img v-if="displayImage"
                 :src="displayImage"
                 :alt="product.name"
                 class="w-full h-full object-cover group-hover:scale-105 transition duration-500">
            <div v-else class="flex items-center justify-center w-full h-full text-xs text-gray-300">無圖</div>
            
            <!-- 缺貨遮罩 -->
            <div v-if="selectedVariant.stock <= 0" class="absolute inset-0 bg-black/40 flex items-center justify-center text-white text-xs font-bold tracking-widest">
                補貨中
            </div>
        </Link>

        <!-- 資訊與操作區 -->
        <div class="p-3 flex flex-col flex-grow">
            <h3 class="font-bold text-sm text-gray-800 leading-tight line-clamp-2 h-9 mb-1 group-hover:text-blue-600 transition">
                <Link :href="`/shop/product/${product.slug}`">{{ product.name }}</Link>
            </h3>

            <div class="mt-auto flex items-center justify-between">
                <div class="flex flex-col">
                    <div class="text-red-600 font-bold text-sm">
                        NT$ {{ formatPrice(selectedVariant.display_price || product.display_price) }}
                    </div>
                    <div v-if="selectedVariant.has_discount || product.has_discount" class="text-gray-400 line-through text-[10px]">
                        NT$ {{ formatPrice(selectedVariant.price || product.price) }}
                    </div>
                </div>
                
                <!-- 迷你加入按鈕 -->
                <div v-if="showAction">
                    
                    <!-- 情況 A: 可販售商品 -> 顯示迷你購物車按鈕 -->
                    <button v-if="product.is_sellable"
                            @click.stop.prevent="addToCart"
                            :disabled="selectedVariant.stock <= 0 || isLoading"
                            class="w-8 h-8 flex items-center justify-center bg-gray-100 text-gray-600 rounded-lg hover:bg-gray-200 transition disabled:bg-gray-50 disabled:text-gray-300">
                        <svg v-if="isLoading" class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </button>

                    <!-- 情況 B: 非賣品 -> 顯示禮物圖示 (點擊進內頁) -->
                    <Link v-else 
                          :href="`/shop/product/${product.slug}`"
                          class="w-8 h-8 flex items-center justify-center bg-green-50 text-green-600 border border-green-200 rounded-lg hover:bg-green-100 transition"
                          title="只送不賣">
                        <span>🎁</span>
                    </Link>

                </div>
            </div>
        </div>
    </div>
</template>
