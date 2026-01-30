<!-- 商品卡片元件 -->
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
const quantity = ref(1);
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

    // 優先權 2: 選項代表圖片
    if (!rawPath && selectedVariant.value && selectedVariant.value.attributes && props.product.options) {
        for (const [optName, optValue] of Object.entries(selectedVariant.value.attributes)) {
            const optionDef = props.product.options.find(o => o.name === optName);
            if (optionDef) {
                const valueDef = optionDef.values.find(v => v.value == optValue);
                if (valueDef && valueDef.image) {
                    rawPath = valueDef.image;
                    break;
                }
            }
        }
    }

    // 優先權 3: 商品主圖
    if (!rawPath && props.product.primary_image) {
        rawPath = props.product.primary_image;
    }

    return formatImage(rawPath);
});

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

// 加入購物車邏輯
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
            quantity: quantity.value
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
                quantity: quantity.value,
                image: selectedVariant.value.smart_image || displayImage.value,
                price: selectedVariant.value.display_price || selectedVariant.value.price
            }
        }));

        // 簡單的成功視覺回饋
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

// 切換收藏
const toggleWishlist = async () => {
    const page = usePage();
    const user = page.props.auth.user;

    if (!user) {
        if(confirm('收藏商品需要先登入會員，是否前往登入？')) {
            window.location.href = '/login';
        }
        return;
    }

    try {
        const response = await axios.post('/wishlist/toggle', { product_id: props.product.id });
        isWishlisted.value = response.data.is_wishlisted;
    } catch (error) {
        console.error(error);
        alert('操作失敗，請稍後再試');
    }
};
</script>

<template>
    <div class="bg-white border rounded-xl hover:shadow-lg transition group flex flex-col h-full">

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

            <!-- 加入收藏按鈕 -->
            <button @click.stop.prevent="toggleWishlist" 
                    class="absolute bottom-3 right-3 w-9 h-9 flex items-center justify-center rounded-full bg-white/80 hover:bg-white text-gray-400 backdrop-blur-sm shadow-sm transition-all duration-300 group/wishlist"
                    :class="{'text-red-500 bg-white': isWishlisted}">
                <svg xmlns="http://www.w3.org/2000/svg" 
                     :class="[isWishlisted ? 'fill-current' : 'fill-none stroke-current']"
                     class="w-5 h-5 transition-transform duration-300 group-hover/wishlist:scale-110" 
                     viewBox="0 0 24 24" 
                     stroke-width="2" 
                     stroke-linecap="round" 
                     stroke-linejoin="round">
                    <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                </svg>
            </button>
        </Link>

        <!-- 資訊與操作區 -->
        <div class="p-4 flex flex-col flex-grow">

            <!-- 活動標籤區 (Flex wrap) -->
            <div v-if="product.active_promotions && product.active_promotions.length > 0" class="mb-2 flex flex-wrap gap-1.5">
                <div v-for="promo in product.active_promotions" :key="promo.id" class="group/promo relative">
                    <!-- 標籤本體 (簡潔風格) -->
                    <component :is="promo.link ? Link : 'div'"
                         :href="promo.link"
                         class="flex items-center justify-center w-6 h-6 rounded-full border transition-all relative z-10"
                         :class="[
                            promo.action_type === 'gift' 
                                ? 'bg-green-50 text-green-600 border-green-200 hover:bg-green-100' 
                                : 'bg-red-50 text-red-600 border-red-200 hover:bg-red-100',
                            promo.link ? 'cursor-pointer hover:scale-110' : 'cursor-help'
                         ]">
                        <!-- 字體稍微放大一點，打折參考圖標🔖🏷️ -->
                        <span class="text-sm leading-none mt-0.5">
                            {{ promo.action_type === 'gift' ? '🎁' : '🏷️' }}
                        </span>
                    </component>

                    <!-- Tooltip (淺色卡片風格) -->
                    <div class="absolute bottom-full left-0 mb-2 w-56 p-3 bg-white text-gray-700 text-xs rounded-xl shadow-[0_4px_20px_-4px_rgba(0,0,0,0.15)] border border-gray-100 opacity-0 invisible group-hover/promo:opacity-100 group-hover/promo:visible transition-all duration-200 z-20 pointer-events-none transform origin-bottom-left scale-95 group-hover/promo:scale-100">
                        <div class="font-bold text-sm mb-1 text-gray-900 border-b border-gray-100 pb-1">
                            {{ promo.name }}
                        </div>
                        <div class="text-gray-500 leading-relaxed mt-1">
                            {{ promo.tooltip }}
                        </div>
                        <!-- 小箭頭 (純白) -->
                        <div class="absolute top-full left-2 -mt-[5px] border-8 border-transparent border-t-white drop-shadow-sm"></div>
                    </div>
                </div>
            </div>

            <!-- 類別與標題 -->
            <div class="mb-3">
                <div class="text-xs text-gray-500 mb-1" v-if="product.category">
                    <Link :href="`/shop/category/${product.category.slug}`" class="hover:text-blue-600 hover:underline">
                        {{ product.category.name }}
                    </Link>
                </div>
                <h3 class="font-bold text-base text-gray-800 leading-tight line-clamp-2 h-10 group-hover:text-blue-600 transition">
                    <Link :href="`/shop/product/${product.slug}`">{{ product.name }}</Link>
                </h3>
            </div>

            <div class="mt-auto space-y-3">

                <!-- 價格顯示 (任何模式都顯示) -->
                <div class="flex items-baseline gap-2">
                    <div class="text-red-600 font-bold text-lg">
                        NT$ {{ formatPrice(selectedVariant.display_price || product.display_price) }}
                    </div>
                    <div v-if="selectedVariant.has_discount || product.has_discount" class="text-gray-400 line-through text-xs">
                        NT$ {{ formatPrice(selectedVariant.price || product.price) }}
                    </div>
                </div>

                <!-- 操作區 (規格/數量/按鈕) - 受 showAction 控制 -->
                <div v-if="showAction" class="space-y-3">
                    
                    <!-- 情況 A: 單一維度規格 (一維) -> 顯示視覺化按鈕 (可滑動) -->
                    <div v-if="product.options && product.options.length === 1" class="w-full">
                         <!-- 增加 p-1 (padding) 讓 ring-offset 不會被 overflow 切掉 -->
                         <div class="flex gap-2 overflow-x-auto p-1 scrollbar-hide">
                            <button v-for="val in product.options[0].values" :key="val.value"
                                    @click.stop.prevent="() => {
                                        // 找出對應的 variant
                                        const found = product.variants.find(v => v.attributes && v.attributes[product.options[0].name] == val.value);
                                        if(found) selectedVariant = found;
                                    }"
                                    class="flex-shrink-0 transition-all focus:outline-none"
                                    :title="val.label">
                                
                                <!-- 顏色圓圈 -->
                                <span v-if="product.options[0].type === 'color'" 
                                      class="block w-6 h-6 rounded-full border shadow-sm ring-offset-1"
                                      :class="selectedVariant.attributes?.[product.options[0].name] == val.value ? 'ring-2 ring-blue-600' : 'hover:ring-2 hover:ring-gray-300'"
                                      :style="{ backgroundColor: val.value }">
                                </span>

                                <!-- 圖片方塊 -->
                                <span v-else-if="product.options[0].type === 'image'"
                                      class="block w-8 h-8 rounded border overflow-hidden bg-gray-50 ring-offset-1"
                                      :class="selectedVariant.attributes?.[product.options[0].name] == val.value ? 'ring-2 ring-blue-600' : 'hover:ring-2 hover:ring-gray-300'">
                                    <img v-if="val.image" :src="formatImage(val.image)" class="w-full h-full object-cover">
                                    <span v-else class="w-full h-full flex items-center justify-center text-[10px] text-gray-400">無圖</span>
                                </span>
                                
                                <!-- 文字方塊 -->
                                <span v-else 
                                      class="block px-2 py-1 border rounded text-xs font-medium whitespace-nowrap"
                                      :class="selectedVariant.attributes?.[product.options[0].name] == val.value ? 'bg-blue-50 border-blue-600 text-blue-700' : 'bg-white border-gray-200 text-gray-700'">
                                    {{ val.label }}
                                </span>
                            </button>
                         </div>
                         <!-- 顯示當前選取的名稱 -->
                         <div class="text-xs text-gray-500 mt-1">
                            {{ product.options[0].name }}: {{ selectedVariant.attributes?.[product.options[0].name] ? 
                                product.options[0].values.find(v => v.value == selectedVariant.attributes[product.options[0].name])?.label 
                                : '' }}
                         </div>
                    </div>

                    <!-- 情況 B: 多維度或無 Options 設定 -> 維持下拉選單 (但如果只有一個變體且沒 options 就不顯示下拉) -->
                    <div v-else-if="product.variants && product.variants.length > 1">
                        <select v-model="selectedVariant"
                                @click.stop.prevent
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

                    <!-- 數量與購買按鈕 -->
                    <div class="flex gap-2 w-full">
                        <!-- 情況 A: 可販售商品 -->
                        <template v-if="product.is_sellable">
                            <!-- 數量輸入 -->
                            <input type="number" v-model="quantity" min="1" :max="selectedVariant.stock"
                                class="basis-5/12 flex-shrink-0 w-0 border border-gray-200 rounded-lg text-center text-sm py-2 px-1 focus:ring-blue-500 focus:border-blue-500"
                                :disabled="selectedVariant.stock <= 0">
    
                            <!-- 加入按鈕 -->
                            <button @click.stop.prevent="addToCart"
                                    :disabled="selectedVariant.stock <= 0 || isLoading"
                                    class="basis-5/12 flex-grow bg-gray-100 text-gray-700 rounded-lg text-sm font-medium hover:bg-gray-200 transition disabled:bg-gray-300 disabled:text-gray-500 disabled:cursor-not-allowed flex items-center justify-center gap-1 shadow-sm">
                                <svg v-if="isLoading" class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span :id="`btn-text-${product.id}`">{{ selectedVariant.stock <= 0 ? '缺貨中' : '加入購物車' }}</span>
                            </button>
                        </template>
                        <!-- 情況 B: 只送不賣 / 非賣品 -->
                        <template v-else>
                            <Link :href="`/shop/product/${product.slug}`" 
                                  class="w-full bg-green-50 text-green-700 border border-green-200 rounded-lg text-sm font-bold py-2 hover:bg-green-100 transition text-center flex items-center justify-center gap-1">
                                <span>🎁</span>
                                <span>查看活動詳情</span>
                            </Link>
                        </template>
                        
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
