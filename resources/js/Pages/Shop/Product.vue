<script setup>
import { ref, watch, computed } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import ShopLayout from '@/Layouts/ShopLayout.vue';
import ProductGridLayout from '@/Components/Shop/ProductGridLayout.vue';
import axios from 'axios';

const props = defineProps({
    product: Object,
    relatedProducts: Array,
    isWishlisted: Boolean,
    canReview: Boolean,
    reviewStatus: String,
});

// 狀態
const page = usePage();
const selectedVariant = ref(props.product.variants?.[0] || {});
const isWishlisted = ref(props.isWishlisted);
const quantity = ref(1);
const isLoading = ref(false);

const formatPrice = (price) => new Intl.NumberFormat('zh-TW').format(price);

const priceRange = computed(() => {
    const variants = props.product.variants || [];
    if (variants.length === 0) return 'NT$ 0';
    
    const prices = variants.map(v => v.price);
    const min = Math.min(...prices);
    const max = Math.max(...prices);
    return min === max ? `NT$ ${formatPrice(min)}` : `NT$ ${formatPrice(min)} ~ ${formatPrice(max)}`;
});

// 加入購物車 (優化版)
const addToCart = async () => {
    if (selectedVariant.value.stock <= 0) return;

    isLoading.value = true;
    try {
        const response = await axios.post('/cart/add', {
            variant_id: selectedVariant.value.id,
            quantity: quantity.value
        });

        // 1. 更新 Navbar 紅點 (透過全域事件，不重整頁面)
        window.dispatchEvent(new CustomEvent('cart-updated', {
            detail: { count: response.data.cartCount }
        }));

        // 2. 顯示詳細回饋 (發送事件給 MainLayout)
        window.dispatchEvent(new CustomEvent('show-cart-feedback', {
            detail: {
                product_name: props.product.name,
                variant_name: selectedVariant.value.name,
                quantity: quantity.value,
                // 優先使用當前顯示的圖片 (可能是變體圖、選項圖或主圖)
                image: currentImage.value || props.product.primary_image,
                price: selectedVariant.value.price
            }
        }));

    } catch (error) {
        // 失敗邏輯: 抓取後端回傳的 message
        const msg = error.response?.data?.message || '加入失敗';
        alert(msg); // 彈出「庫存不足...」
    } finally {
        isLoading.value = false;
    }
};

// === 多圖畫廊邏輯 ===
// 確保 images 是陣列 (相容舊資料)
const galleryImages = computed(() => {
    let imgs = props.product.images || [];
    // 如果舊的單張 image 欄位有值且不在 images 裡，加進去 (過渡期處理)
    if (props.product.image && !imgs.includes(props.product.image)) {
        imgs.unshift(props.product.image);
    }
    return imgs.length > 0 ? imgs : [];
});

const currentImage = ref(galleryImages.value[0] || null);

// === 新增：視覺化規格選擇邏輯 ===
const options = computed(() => props.product.options || []);
const hasOptions = computed(() => options.value.length > 0);
const selectedOptions = ref({}); // 儲存使用者的選擇 { "Color": "Red", "Size": "M" }

// 當選中規格改變時，更新圖片
// 邏輯順序：
// 1. 變體專屬圖片 (Unique variant image)
// 2. 選項代表圖片 (Option image, e.g. "Color": "Red" 的圖片)
// 3. 原本的圖片 (不變)
watch(selectedVariant, (newVal) => {
    // 1. 優先檢查變體本身是否有圖
    if (newVal.image) {
        currentImage.value = newVal.image;
        return;
    }

    // 2. 檢查選項是否有圖 (從 attributes 反查 options)
    if (newVal.attributes && props.product.options) {
        // 遍歷所有選項，看有沒有哪個選項值是有設定圖片的
        for (const [optName, optValue] of Object.entries(newVal.attributes)) {
            const optionDef = props.product.options.find(o => o.name === optName);
            if (optionDef) {
                const valueDef = optionDef.values.find(v => v.value == optValue);
                if (valueDef && valueDef.image) {
                    currentImage.value = valueDef.image;
                    return; // 找到就停止，優先權取決於 attributes 順序 (通常 Color 在前)
                }
            }
        }
    }
    
    // 3. 如果都沒有，就不用特別切換，或者切回主圖？
    // 通常行為是維持現狀，或者切回主圖。這裡維持現狀可能比較好，或者切回第一張。
    // currentImage.value = galleryImages.value[0]; 
});

// 當選項改變時 (還沒算出 Variant 前)，也希望能即時預覽顏色
watch(selectedOptions, (newOptions) => {
    if(!props.product.options) return;
    
    // 遍歷新的選項，如果有圖就切換
    // 我們可以從 options 定義中找
    for (const [optName, optValue] of Object.entries(newOptions)) {
         const optionDef = props.product.options.find(o => o.name === optName);
         if (optionDef) {
             const valueDef = optionDef.values.find(v => v.value == optValue);
             if (valueDef && valueDef.image) {
                 currentImage.value = valueDef.image;
                 // 這裡不 return，讓後面的選項 (如果有的話) 覆蓋前面的？
                 // 通常 Color 在第一位，Size 在第二位。我們通常希望 Color 的圖優先。
                 // 所以如果 options 陣列順序是 Name, Color, Size... 
                 // 我們應該依據 options 的順序來決定優先權，而不是 selectedOptions 物件的迭代順序
             }
         }
    }
}, { deep: true });

// 初始化選取狀態
if (hasOptions.value) {
    // 預設選取第一個有庫存的變體
    const defaultVar = props.product.variants?.find(v => v.stock > 0) || props.product.variants?.[0];
    if (defaultVar && defaultVar.attributes) {
        selectedOptions.value = { ...defaultVar.attributes };
    } else {
        // 如果找不到對應屬性，就預設選取每一個選項的第一個值
        options.value.forEach(opt => {
            if (opt.values && opt.values.length > 0) {
                selectedOptions.value[opt.name] = opt.values[0].value; 
            }
        });
    }
}

// 根據選項找出對應的 Variant
const foundVariant = computed(() => {
    if (!hasOptions.value) return null;
    
    return props.product.variants.find(v => {
        const attrs = v.attributes || {};
        // 檢查每一個選項是否都匹配
        return options.value.every(opt => {
            const selectedVal = selectedOptions.value[opt.name];
            // 寬鬆比較
            return attrs[opt.name] == selectedVal;
        });
    });
});

// 當 user 改變選項時，更新 selectedVariant
watch(foundVariant, (newVar) => {
    if (newVar) {
        selectedVariant.value = newVar;
    } else {
        // 如果找不到對應的變體 (例如選了 Gray + M，但沒有這個組合)
        // 我們應該讓 selectedVariant 變成一個空物件或無效狀態，避免加入購物車
        // 或者保留上一個有效的是危險的，因為 user 以為選了新的。
        selectedVariant.value = { id: null, stock: 0, price: 0 }; 
    }
});

// 檢查某個選項值是否可選 (檢查庫存) - 進階功能 (Cross Availability)
// 邏輯修正：檢查在「已知其他已選選項」的情況下，嘗試選擇這個新值是否會有對應的變體
const isOptionValueAvailable = (optionName, newValue) => {
    // 取得目前「暫定」的選項組合
    const tentativeSelection = { ...selectedOptions.value, [optionName]: newValue };
    
    // 檢查是否有變體符合這個組合 (需完全符合所有已選的 key，但這裡檢查的是 tentativeSelection)
    // 注意：這裡的邏輯比較複雜。
    // 1. 我們要檢查的是 product.variants 中，是否有任何一個 variant 同時滿足：
    //    a) 該 variant 的屬性包含 optionName = newValue
    //    b) 該 variant 的其他屬性也符合目前已選的其他選項 (排除自己)
    
    return props.product.variants.some(v => {
        const attrs = v.attributes || {};
        
        // 必須符合目標值
        if (attrs[optionName] != newValue) return false;
        
        // 必須符合其他已選的值 (但如果其他選項還沒選，就不限制)
        // 這裡做一個寬鬆檢查：遍歷 tentativeSelection 中 *其他* 的 key
        // 但要注意，如果是切換 Dimension 1 (Color)，那麼 Dimension 2 (Size) 的限制是否仍然有效？
        // 通常 UI 行為是：
        // - 使用者改變 Color -> 檢查該 Color 下是否有目前選中的 Size？
        //   - 如果有 -> Size 保持可選，組合有效。
        //   - 如果沒有 -> 該 Color 雖然可選 (因為有別的 Size)，但選了之後 Size 應該要自動切換成可用的，或者 Size 變成不可選。
        // 
        // 這裡 isOptionValueAvailable 主要是用來 disable 按鈕。
        // 如果我點了 Gray (Color)，而目前 Size 是 M。
        // 假設 Gray 只有 S。
        // 那麼 Gray 這個按鈕應該 disable 嗎？不應該，因為 Gray 是有貨的 (只是沒有 M)。
        // 所以，通常第一維度 (Color) 永遠檢查「是否存在該 Color 的任意變體」。
        // 第二維度 (Size) 則檢查「在目前 Color 下，該 Size 是否存在」。
        
        // 實作策略：
        // 找出該選項在 options 陣列中的 index
        // 如果是第一個維度 (index 0) -> 只要該值存在於任意變體即可
        // 如果是後續維度 -> 必須符合前面維度的選擇
        
        const optionIndex = options.value.findIndex(o => o.name === optionName);
        if (optionIndex <= 0) {
            // 第一維度，或者找不到：只檢查是否有該屬性值的變體存在且有庫存(可選)
            return v.stock > 0; 
        } else {
            // 後續維度：檢查是否符合前面所有維度的選擇
            // 取得前面所有維度的名稱
            const prevOptionNames = options.value.slice(0, optionIndex).map(o => o.name);
            
            // 檢查這個 variant 是否符合前面維度的當前選擇
            const matchesPrev = prevOptionNames.every(prevName => {
                return attrs[prevName] == selectedOptions.value[prevName];
            });
            
            return matchesPrev && v.stock > 0;
        }
    });
};


// 切換收藏
const toggleWishlist = async () => {
    // 1. 使用 usePage() 獲取全域共享資料
    const page = usePage();
    const user = page.props.auth.user;

    // 2. 判斷 user 是否存在
    if (!user) {
        if(confirm('收藏商品需要先登入會員，是否前往登入？')) {
            window.location.href = '/login';
        }
        return;
    }

    // 3. 執行收藏邏輯 (保持不變)
    try {
        const response = await axios.post('/wishlist/toggle', { product_id: props.product.id });
        isWishlisted.value = response.data.is_wishlisted;
    } catch (error) {
        console.error(error);
        alert('操作失敗，請稍後再試');
    }
};

// 評價表單
const reviewForm = useForm({
    product_id: props.product.id,
    rating: 5,
    comment: '',
});

const submitReview = () => {
    reviewForm.post('/reviews', {
        preserveScroll: true,
        onSuccess: () => {
            // 關鍵修改：檢查後端是否有回傳 success flash
            if (page.props.flash.success) {
                reviewForm.reset('comment');
                alert(page.props.flash.success);
            }
            // 如果是 error flash (例如驗證失敗)，Inertia 雖然視為 onSuccess，但我們不彈出成功
            else if (page.props.flash.error) {
                alert(page.props.flash.error);
            }
        },
        onError: (errors) => {
            alert('提交失敗：' + Object.values(errors).join('\n'));
        }
    });
};

// 引入 Builder Components (如果要在商品頁用 Builder)
import blockComponents from '@/Components/Blocks';

const components = blockComponents;

// 準備 Schema.org 資料
const schemaData = {
    "@context": "https://schema.org/",
    "@type": "Product",
    "name": props.product.name,
    "image": props.product.primary_image ? `${window.location.origin}/storage/${props.product.primary_image}` : '',
    "description": props.product.excerpt || props.product.name,
    "sku": selectedVariant.value.sku || props.product.id,
    "offers": {
        "@type": "Offer",
        "url": window.location.href,
        "priceCurrency": "TWD",
        "price": selectedVariant.value.price,
        "availability": selectedVariant.value.stock > 0 ? "https://schema.org/InStock" : "https://schema.org/OutOfStock"
    }
};
</script>

<template>
    <Head :title="product.name">
        <!-- 插入 JSON-LD -->
        <component :is="'script'" type="application/ld+json">
            {{ JSON.stringify(schemaData) }}
        </component>
    </Head>

    <ShopLayout>

        <!-- 上半部：商品主要資訊區 (永遠保持左右分欄) -->
        <div class="grid grid-cols-1 md:grid-cols-12 gap-10">

            <!-- 左側：圖片區 (佔 5 等份，約 41%) -->
            <div class="md:col-span-5">

                <!-- 主圖 -->
                <div class="aspect-square bg-gray-100 rounded-2xl overflow-hidden border mb-4 flex items-center justify-center relative">
                    <img v-if="currentImage" :src="`/storage/${currentImage}`" class="w-full h-full object-cover transition-all duration-300">
                    <span v-else class="text-gray-400">No Image</span>
                </div>

                <!-- 縮圖列表 -->
                <div class="flex gap-2 overflow-x-auto pb-2" v-if="galleryImages.length > 1">
                    <button v-for="img in galleryImages" :key="img"
                            @click="currentImage = img"
                            class="w-20 h-20 rounded-lg overflow-hidden border-2 flex-shrink-0"
                            :class="currentImage === img ? 'border-blue-600' : 'border-transparent hover:border-gray-300'">
                        <img :src="`/storage/${img}`" class="w-full h-full object-cover">
                    </button>
                </div>
            </div>

            <!-- 右側：資訊區 (佔 7 等份 = 58%) -->
            <div class="md:col-span-7">
                <nav class="text-sm text-gray-500 mb-4">
                    <Link href="/shop" class="hover:underline">商店</Link> /
                    <Link :href="`/shop/category/${product.category.slug}`" class="hover:underline">{{ product.category.name }}</Link>
                </nav>

                <h1 class="text-3xl font-bold mb-2">{{ product.name }}</h1>

                <!-- 新增：摘要顯示 -->
                <div v-if="product.excerpt" class="text-gray-600 mb-4 font-medium leading-relaxed whitespace-pre-wrap">
                    {{ product.excerpt }}
                </div>

                <div class="flex items-center gap-2 mb-4">
                    <div class="flex text-yellow-400">
                        <template v-for="i in 5" :key="i">
                            <!-- 實心星星 / 空心星星 簡單判斷 -->
                            <svg v-if="i <= Math.round(product.average_rating)" class="w-5 h-5 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <svg v-else class="w-5 h-5 text-gray-300 fill-current" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        </template>
                    </div>
                    <span class="text-sm text-gray-500">({{ product.review_count }} 則評價)</span>
                </div>
                <p class="text-gray-500 text-sm mb-4">全系列價格：{{ priceRange }}</p>

                <!-- 當前選中規格的價格 -->
                <div class="mb-6">
                    <div class="text-3xl text-red-600 font-bold mb-2">
                        NT$ {{ formatPrice(selectedVariant.price) }}
                    </div>

                    <!-- 庫存顯示 -->
                    <div class="text-sm">
                        <span class="text-gray-500">庫存狀態：</span>
                        <span v-if="selectedVariant.stock > 10" class="text-green-600 font-bold">庫存充足</span>
                        <span v-else-if="selectedVariant.stock > 0" class="text-orange-500 font-bold">最後 {{ selectedVariant.stock }} 件</span>
                        <span v-else class="text-red-500 font-bold">已售完</span>
                    </div>
                </div>

                <!-- 規格選擇按鈕 -->
                <div class="mb-8">
                    
                    <!-- 模式 A: 視覺化選取 (Visual Options) -->
                    <div v-if="hasOptions" class="space-y-6">
                        <div v-for="option in options" :key="option.name">
                            <h3 class="text-sm font-bold text-gray-700 mb-2">
                                {{ option.name }}: <span class="font-normal text-gray-500">{{ option.values.find(v => v.value == selectedOptions[option.name])?.label }}</span>
                            </h3>
                            
                            <div class="flex flex-wrap gap-3">
                                <button v-for="val in option.values" :key="val.value"
                                        @click="selectedOptions[option.name] = val.value"
                                        :class="[
                                            selectedOptions[option.name] == val.value 
                                                ? 'ring-2 ring-blue-600 ring-offset-1' 
                                                : 'hover:ring-2 hover:ring-gray-300 hover:ring-offset-1',
                                            !isOptionValueAvailable(option.name, val.value) ? 'opacity-50 cursor-not-allowed' : ''
                                        ]"
                                        class="relative rounded-full transition-all focus:outline-none"
                                        :title="val.label">
                                        
                                    <!-- 類型 A: 顏色圓圈 -->
                                    <span v-if="option.type === 'color'" 
                                          class="block w-8 h-8 rounded-full border shadow-sm"
                                          :style="{ backgroundColor: val.value }">
                                    </span>

                                    <!-- 類型 B: 圖片方塊 (New) -->
                                    <span v-else-if="option.type === 'image'"
                                          class="block w-10 h-10 rounded-lg border overflow-hidden bg-gray-50">
                                        <img v-if="val.image" :src="`/storage/${val.image}`" class="w-full h-full object-cover">
                                        <span v-else class="w-full h-full flex items-center justify-center text-[10px] text-gray-400">無圖</span>
                                    </span>
                                    
                                    <!-- 類型 C: 文字方塊 -->
                                    <span v-else 
                                          class="block px-4 py-2 border rounded-lg text-sm font-medium transition-colors"
                                          :class="selectedOptions[option.name] == val.value ? 'bg-blue-50 border-blue-600 text-blue-700' : 'bg-white border-gray-200 text-gray-700'">
                                        {{ val.label }}
                                    </span>
                                </button>
                            </div>
                        </div>
                        
                        <!-- 提示：如果選擇組合無效 -->
                        <div v-if="hasOptions && !selectedVariant.id" class="text-red-500 text-sm mt-2">
                            ⚠️ 目前選擇的規格組合暫無販售，請嘗試其他搭配。
                        </div>
                    </div>

                    <!-- 模式 B: 傳統條列式 (Fallback) -->
                    <div v-else>
                        <h3 class="text-sm font-bold text-gray-700 mb-3">規格</h3>
                        <div class="flex flex-wrap gap-3">
                            <button v-for="variant in product.variants" :key="variant.id"
                                    @click="selectedVariant = variant"
                                    class="px-4 py-2 border rounded-lg font-medium transition flex items-center gap-2"
                                    :class="selectedVariant.id === variant.id ? 'border-blue-600 bg-blue-50 text-blue-700 ring-1 ring-blue-600' : 'hover:border-gray-300 text-gray-700'"
                                    :disabled="variant.stock <= 0">
                                <div class="flex items-center gap-2">
                                    <img v-if="variant.image" :src="`/storage/${variant.image}`" class="w-6 h-6 rounded-full object-cover border">
                                    {{ variant.name }}
                                </div>
                                <span v-if="variant.stock <= 0" class="text-xs text-red-500 ml-1">(缺貨)</span>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 購買區塊 -->
                <div class="flex gap-4 mb-8">
                    <input type="number" v-model="quantity" min="1" :max="selectedVariant.stock" class="w-32 border rounded-lg px-4 py-3 text-center font-bold">
                    <button @click="addToCart"
                            :disabled="selectedVariant.stock <= 0 || isLoading"
                            class="flex-1 bg-blue-600 text-white font-bold py-3 rounded-lg hover:bg-blue-700 disabled:bg-gray-400 transition shadow-lg flex items-center justify-center gap-2">
                        <svg v-if="isLoading" class="animate-spin h-5 w-5" viewBox="0 0 24 24"><path fill="currentColor" d="M12 2a10 10 0 1010 10A10 10 0 0012 2zm0 18a8 8 0 118-8 8 8 0 01-8 8z" opacity=".3"/><path fill="currentColor" d="M20.24 12.24a8 8 0 01-2.48 5.66" /></svg>
                        {{ isLoading ? '處理中...' : (selectedVariant.stock > 0 ? '加入購物車' : '暫無庫存') }}
                    </button>

                    <!-- 收藏按鈕 -->
                    <button @click="toggleWishlist"
                class="w-12 h-[50px] border rounded-lg flex items-center justify-center transition hover:border-red-400"
                :class="isWishlisted ? 'border-red-500 bg-red-50 text-red-500' : 'border-gray-300 text-gray-400'">
                        <!-- 實心愛心 (已收藏) / 空心愛心 (未收藏) -->
                        <svg v-if="isWishlisted" class="w-6 h-6 fill-current" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                        <svg v-else class="w-6 h-6 fill-none stroke-current stroke-2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path></svg>
                    </button>
                </div>

                <!-- 商品描述 (右側 Builder) -->
                <!-- 這裡原本是 v-html="product.description"，現在要改迴圈 -->
                <div v-if="product.description && product.description.length > 0" class="mt-10 border-t pt-8">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">商品介紹</h3>
                    <div v-for="(block, index) in product.description" :key="index">
                        <component :is="components[block.type]" v-if="components[block.type]" :data="block.data" />
                    </div>
                </div>
            </div>
        </div>

        <!-- 下方排版 (Builder) -->
        <div v-if="product.content && product.content.length > 0" class="mt-16 border-t pt-10">
            <div v-for="(block, index) in product.content" :key="index">
                <component :is="components[block.type]" v-if="components[block.type]" :data="block.data" />
            </div>
        </div>

        <!-- === 評價區塊 === -->
        <div class="mt-16 border-t pt-10">
            <h2 class="text-2xl font-bold mb-8">商品評價</h2>

            <!-- 1. 評價列表 -->
            <div v-if="product.reviews.length > 0" class="space-y-6 mb-12">
                <div v-for="review in product.reviews" :key="review.id" class="bg-gray-50 p-6 rounded-lg">
                    <div class="flex items-center justify-between mb-2">
                        <div class="font-bold text-gray-800">{{ review.user.name }}</div>
                        <div class="text-sm text-gray-500">{{ new Date(review.created_at).toLocaleDateString() }}</div>
                    </div>
                    <div class="flex text-yellow-400 mb-3">
                        <template v-for="i in 5">
                            <span v-if="i <= review.rating">★</span>
                            <span v-else class="text-gray-300">★</span>
                        </template>
                    </div>
                    <p class="text-gray-700">{{ review.comment }}</p>
                </div>
            </div>
            <div v-else class="text-gray-500 italic mb-10">目前尚無評價，歡迎分享您的使用心得！</div>

            <!-- 2. 撰寫評價表單 (只有符合資格者顯示) -->
            <div v-if="canReview" class="bg-white border rounded-xl p-6 shadow-sm max-w-2xl">
                <h3 class="text-lg font-bold mb-4">撰寫評價</h3>
                <form @submit.prevent="submitReview">
                    <!-- ... 表單內容保持不變 ... -->

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">整體評分</label>
                        <div class="flex gap-2">
                            <button type="button" v-for="i in 5" :key="i"
                                    @click="reviewForm.rating = i"
                                    class="text-2xl focus:outline-none transition transform hover:scale-110"
                                    :class="i <= reviewForm.rating ? 'text-yellow-400' : 'text-gray-300'">
                                ★
                            </button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="block text-sm font-bold text-gray-700 mb-2">心得分享</label>
                        <textarea v-model="reviewForm.comment" rows="4"
                                  class="w-full border rounded-lg px-3 py-2 focus:ring-2 focus:ring-blue-500 outline-none"
                                  placeholder="說說看這個商品哪裡好用..."></textarea>
                    </div>

                    <button type="submit" :disabled="reviewForm.processing"
                            class="bg-blue-600 text-white px-6 py-2 rounded-lg font-bold hover:bg-blue-700 transition disabled:opacity-50">
                        {{ reviewForm.processing ? '提交中...' : '送出評價' }}
                    </button>
                </form>
            </div>

            <!-- 狀態提示區 -->
            <div v-else class="text-sm p-4 rounded-lg inline-block">
                <!-- 狀態 A: 已經評過了 -->
                <div v-if="reviewStatus === 'reviewed'" class="text-green-600 bg-green-50 border border-green-200">
                    ✅ 您已評價過此商品，感謝您的回饋！
                </div>

                <!-- 狀態 B: 登入但沒買過 (或訂單未完成) -->
                <div v-else-if="reviewStatus === 'no-purchase'" class="text-gray-500 bg-gray-100 border border-gray-200">
                    💡 只有購買過此商品且訂單已完成的會員才能撰寫評價喔。
                </div>

                <!-- 狀態 C: 未登入 -->
                <div v-else-if="reviewStatus === 'guest'" class="text-gray-500 bg-gray-100 border border-gray-200">
                    💡 請先 <Link href="/login" class="text-blue-600 hover:underline">登入</Link> 以撰寫評價。
                </div>
            </div>
        </div>

        <!-- 關聯商品區塊 -->
        <div v-if="relatedProducts.length > 0" class="mt-16 border-t pt-10">
            <h2 class="text-2xl font-bold mb-8 text-gray-800">您可能也喜歡</h2>
            <ProductGridLayout :products="relatedProducts" variant="small" empty-message="暫無商品" />
        </div>

    </ShopLayout>
</template>
